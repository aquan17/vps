<?php

namespace App\Services;

use App\Models\VpsInstance;
use Google\Cloud\Compute\V1\Client\FirewallsClient;
use Google\Cloud\Compute\V1\Client\InstancesClient;
use Google\Cloud\Compute\V1\DeleteFirewallRequest;
use Google\Cloud\Compute\V1\Firewall;
use Google\Cloud\Compute\V1\GetFirewallRequest;
use Google\Cloud\Compute\V1\InsertFirewallRequest;
use Google\Cloud\Compute\V1\InsertInstanceRequest;
use Google\Cloud\Compute\V1\Instance;
use Google\Cloud\Compute\V1\PatchFirewallRequest;
use Google\Cloud\Compute\V1\AttachedDisk;
use Google\Cloud\Compute\V1\AttachedDiskInitializeParams;
use Google\Cloud\Compute\V1\NetworkInterface;
use Google\Cloud\Compute\V1\AccessConfig;
use Google\Cloud\Compute\V1\Allowed;
use Google\Cloud\Compute\V1\GetInstanceRequest;
use Google\Cloud\Compute\V1\SetTagsInstanceRequest;
use Google\Cloud\Compute\V1\Tags;
use Google\ApiCore\OperationResponse;
use Illuminate\Support\Facades\Log;

class GcpVpsService
{
    private const WINDOWS_LOGIN_USER = 'rdp_access';
    private const WINDOWS_PASSWORD_KEY_TTL_MINUTES = 5;
    private const WINDOWS_PASSWORD_POLL_ATTEMPTS = 60;
    private const WINDOWS_PASSWORD_POLL_SLEEP_SECONDS = 2;

    protected ?string $projectId;
    protected ?string $credentialsPath;

    public function __construct()
    {
        $this->projectId = null;
        $this->credentialsPath = null;
    }

    public function getAvailableZones()
    {
        $zonesClient = new \Google\Cloud\Compute\V1\Client\ZonesClient([
            'credentials' => $this->credentialsPath
        ]);
        
        $request = new \Google\Cloud\Compute\V1\ListZonesRequest();
        $request->setProject($this->projectId);
        
        $response = $zonesClient->list($request);
        $zones = [];
        
        foreach ($response->iterateAllElements() as $zone) {
            if ($zone->getStatus() === 'UP') {
                $zones[] = $zone->getName();
            }
        }
        
        return $zones;
    }

    public function getWindowsImages()
    {
        $imagesClient = new \Google\Cloud\Compute\V1\Client\ImagesClient([
            'credentials' => $this->credentialsPath
        ]);
        
        $request = new \Google\Cloud\Compute\V1\ListImagesRequest();
        $request->setProject('windows-cloud');
        
        $response = $imagesClient->list($request);
        $images = [];
        
        foreach ($response->iterateAllElements() as $image) {
            $name = $image->getName();
            // Lọc chỉ lấy hệ điều hành chuẩn datacenter core
            if ((str_contains($name, 'windows-server-2019') || str_contains($name, 'windows-server-2022')) && !str_contains($name, 'core')) {
                $images[$image->getFamily()] = $image->getDescription() ? $image->getDescription() : $name;
            }
        }
        
        // Remove duplicates if any mapped by family
        return $images;
    }

    public function getUbuntuImages()
    {
        $imagesClient = new \Google\Cloud\Compute\V1\Client\ImagesClient([
            'credentials' => $this->credentialsPath
        ]);
        
        $request = new \Google\Cloud\Compute\V1\ListImagesRequest();
        $request->setProject('ubuntu-os-cloud');
        
        $response = $imagesClient->list($request);
        $images = [];
        
        foreach ($response->iterateAllElements() as $image) {
            $name = $image->getName();
            // Lọc các bản LTS phổ biến, loại bỏ bản ARM hay PRO
            if (str_contains($name, 'ubuntu-2004') || str_contains($name, 'ubuntu-2204') || str_contains($name, 'ubuntu-2404')) {
                if (!str_contains($name, 'arm64') && !str_contains($name, 'pro')) {
                    $family = $image->getFamily();
                    if ($family && !isset($images[$family])) {
                        // Tạo tên hiển thị đẹp từ tên family, VD: ubuntu-2204-lts -> Ubuntu 22.04 LTS
                        preg_match('/ubuntu-(\d{2})(\d{2})/', $family, $matches);
                        $ver = isset($matches[1]) ? $matches[1] . '.' . $matches[2] : 'OS';
                        $images[$family] = "Ubuntu {$ver} LTS";
                    }
                }
            }
        }
        
        return $images;
    }

    public function getWindowsImageOptions(): array
    {
        return $this->listImageFamilies('windows-cloud', function ($family, $name) {
            return str_starts_with($family, 'windows-')
                && !str_contains($family, 'core')
                && !str_contains($family, 'sql')
                && !str_contains($family, 'containers')
                && !str_contains($name, 'core')
                && !str_contains($name, 'sql')
                && !str_contains($name, 'containers');
        }, function ($family) {
            return 'Windows Server ' . strtoupper(str_replace(['windows-', '-'], ['', ' '], $family));
        });
    }

    public function getUbuntuImageOptions(): array
    {
        return $this->listImageFamilies('ubuntu-os-cloud', function ($family, $name) {
            return preg_match('/^ubuntu-\d{4}(-lts)?$/', $family)
                && !str_contains($family, 'pro')
                && !str_contains($family, 'minimal')
                && !str_contains($name, 'arm64');
        }, function ($family) {
            if (preg_match('/ubuntu-(\d{2})(\d{2})/', $family, $matches)) {
                $label = 'Ubuntu ' . $matches[1] . '.' . $matches[2];
                return str_contains($family, 'lts') ? $label . ' LTS' : $label;
            }

            return strtoupper(str_replace('-', ' ', $family));
        });
    }

    public function getLinuxImages(): array
    {
        $images = [];
        $projects = [
            'debian-cloud' => [
                'include' => function ($family) {
                    return preg_match('/^debian-\d+$/', $family);
                },
                'label' => function ($family) {
                    return 'Debian ' . str_replace('debian-', '', $family);
                },
            ],
            'rocky-linux-cloud' => [
                'include' => function ($family) {
                    return preg_match('/^rocky-linux-\d+$/', $family);
                },
                'label' => function ($family) {
                    return 'Rocky Linux ' . str_replace('rocky-linux-', '', $family);
                },
            ],
            'almalinux-cloud' => [
                'include' => function ($family) {
                    return preg_match('/^almalinux-\d+$/', $family);
                },
                'label' => function ($family) {
                    return 'AlmaLinux ' . str_replace('almalinux-', '', $family);
                },
            ],
            'centos-cloud' => [
                'include' => function ($family) {
                    return preg_match('/^centos-(stream-)?\d+$/', $family);
                },
                'label' => function ($family) {
                    return str_replace(['centos-stream-', 'centos-'], ['CentOS Stream ', 'CentOS '], $family);
                },
            ],
        ];

        foreach ($projects as $project => $config) {
            try {
                $images += $this->listImageFamilies($project, function ($family, $name) use ($config) {
                    return $config['include']($family)
                        && !str_contains($family, 'arm')
                        && !str_contains($name, 'arm64');
                }, $config['label']);
            } catch (\Exception $e) {
                // Ignore public image projects that are unavailable to this credential.
            }
        }

        asort($images);

        return $images;
    }

    private function listImageFamilies(string $project, callable $include, callable $label): array
    {
        $imagesClient = new \Google\Cloud\Compute\V1\Client\ImagesClient([
            'credentials' => $this->credentialsPath,
        ]);

        $request = new \Google\Cloud\Compute\V1\ListImagesRequest();
        $request->setProject($project);

        $response = $imagesClient->list($request);
        $images = [];

        foreach ($response->iterateAllElements() as $image) {
            $family = (string) $image->getFamily();
            $name = (string) $image->getName();
            $key = $project . ':' . $family;

            if ($family === '' || isset($images[$key]) || !$include($family, $name)) {
                continue;
            }

            $images[$key] = $label($family, $name);
        }

        asort($images);

        return $images;
    }

    public function getMachineTypeSpecs(string $zone, array $machineTypeNames)
    {
        $machineTypesClient = new \Google\Cloud\Compute\V1\Client\MachineTypesClient([
            'credentials' => $this->credentialsPath
        ]);
        
        $specs = [];
        foreach ($machineTypeNames as $name) {
            try {
                $request = new \Google\Cloud\Compute\V1\GetMachineTypeRequest();
                $request->setProject($this->projectId);
                $request->setZone($zone);
                $request->setMachineType($name);
                
                $machineType = $machineTypesClient->get($request);
                $specs[$name] = [
                    'guestCpus' => $machineType->getGuestCpus(),
                    'memoryMb' => $machineType->getMemoryMb()
                ];
            } catch (\Exception $e) {
                // Ignore if specific type is missing in zone
            }
        }
        
        return $specs;
    }

    /**
     * Nạp cấu hình từ Database (GcpProject) vào Service.
     */
    public function setProjectSettings(string $projectId, string $credentialsFile)
    {
        $this->projectId = $projectId;
        $credentialsFile = trim($credentialsFile);

        if ($this->isAbsolutePath($credentialsFile)) {
            $this->credentialsPath = $this->normalizePathSeparators($credentialsFile);
            return;
        }

        $filename = ltrim($this->normalizePathSeparators($credentialsFile), DIRECTORY_SEPARATOR);

        $candidates = [
            storage_path('app/gcp_credentials/' . $filename), // Ưu tiên hàng đầu
            storage_path('app/' . $filename),
            storage_path('app/google/' . $filename),
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                $this->credentialsPath = $path;
                return;
            }
        }

        // Fallback về đường dẫn mặc định nếu không tìm thấy file thực tế
        $this->credentialsPath = $candidates[0];
    }

    private function normalizePathSeparators(string $path): string
    {
        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, DIRECTORY_SEPARATOR)
            || preg_match('/^[A-Za-z]:\\\\/', $path) === 1;
    }

    private function sourceImagePath(string $osType, ?string $osImage): string
    {
        if ($osImage && str_contains($osImage, ':')) {
            [$project, $family] = explode(':', $osImage, 2);
            return 'projects/' . $project . '/global/images/family/' . $family;
        }

        if ($osType === 'windows') {
            return 'projects/windows-cloud/global/images/family/' . ($osImage ?: 'windows-2022');
        }

        return 'projects/ubuntu-os-cloud/global/images/family/' . ($osImage ?: 'ubuntu-2204-lts');
    }

    private function linuxLoginUser(string $osType, ?string $osImage = null): string
    {
        if ($osType === 'ubuntu' || ($osImage && str_starts_with($osImage, 'ubuntu-os-cloud:'))) {
            return 'ubuntu';
        }

        if ($osType === 'debian' || ($osImage && str_starts_with($osImage, 'debian-cloud:'))) {
            return 'debian';
        }

        if ($osType === 'rocky' || ($osImage && str_starts_with($osImage, 'rocky-linux-cloud:'))) {
            return 'rocky';
        }

        if ($osType === 'almalinux' || ($osImage && str_starts_with($osImage, 'almalinux-cloud:'))) {
            return 'almalinux';
        }

        return 'cloud-user';
    }

    private function windowsStartupScript(string $password): string
    {
        $loginUser = self::WINDOWS_LOGIN_USER;

        return "net user {$loginUser} \"{$password}\" /add\r\n" .
            "net user {$loginUser} \"{$password}\" /active:yes\r\n" .
            "wmic Useraccount where Name='{$loginUser}' set PasswordExpires=false\r\n" .
            "net localgroup administrators {$loginUser} /add\r\n" .
            "reg add \"HKLM\\SYSTEM\\CurrentControlSet\\Control\\Terminal Server\" /v fDenyTSConnections /t REG_DWORD /d 0 /f\r\n" .
            "netsh advfirewall firewall set rule group=\"remote desktop\" new enable=Yes\r\n" .
            "netsh advfirewall firewall add rule name=\"CloudVPS RDP 3389\" dir=in action=allow protocol=TCP localport=3389\r\n" .
            "sc config TermService start= auto\r\n" .
            "net start TermService\r\n";
    }

    private function windowsLoginUser(): string
    {
        return self::WINDOWS_LOGIN_USER;
    }

    private function buildWindowsKeyPayload(string $username): array
    {
        $key = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        if (!$key) {
            throw new \RuntimeException('Unable to generate RSA key for Windows password reset.');
        }

        if (!openssl_pkey_export($key, $privateKeyPem)) {
            throw new \RuntimeException('Unable to export Windows RSA private key.');
        }

        $details = openssl_pkey_get_details($key);
        if (!$details || !isset($details['rsa']['n'], $details['rsa']['e'])) {
            throw new \RuntimeException('Unable to read RSA key details for Windows password reset.');
        }

        $modulus = ltrim($details['rsa']['n'], "\x00");
        $exponent = $details['rsa']['e'];

        $payload = [
            'userName' => $username,
            'modulus' => base64_encode($modulus),
            'exponent' => base64_encode($exponent),
            'expireOn' => now('UTC')->addMinutes(self::WINDOWS_PASSWORD_KEY_TTL_MINUTES)->format(DATE_RFC3339),
        ];

        return [$payload, $privateKeyPem];
    }

    private function appendWindowsKeyMetadata(InstancesClient $instancesClient, string $name, string $zone, array $payload): void
    {
        Log::info('Windows password reset: appending windows-keys metadata', [
            'project_id' => $this->projectId,
            'zone' => $zone,
            'instance' => $name,
            'username' => $payload['userName'] ?? null,
            'modulus_prefix' => isset($payload['modulus']) ? substr($payload['modulus'], 0, 12) : null,
        ]);

        $request = new GetInstanceRequest();
        $request->setProject($this->projectId);
        $request->setZone($zone);
        $request->setInstance($name);

        $instance = $instancesClient->get($request);
        $metadata = $instance->getMetadata();
        $items = $metadata->getItems();

        $payloadJson = json_encode($payload, JSON_UNESCAPED_SLASHES);
        if ($payloadJson === false) {
            throw new \RuntimeException('Unable to encode Windows key payload.');
        }

        $found = false;
        foreach ($items as $item) {
            if ($item->getKey() === 'windows-keys') {
                $current = trim((string) $item->getValue());
                $item->setValue($current === '' ? $payloadJson : $current . "\n" . $payloadJson);
                $found = true;
                break;
            }
        }

        if (!$found) {
            $newItem = new \Google\Cloud\Compute\V1\Items();
            $newItem->setKey('windows-keys');
            $newItem->setValue($payloadJson);
            $items[] = $newItem;
        }

        $metadata->setItems($items);

        $setMetadataReq = new \Google\Cloud\Compute\V1\SetMetadataInstanceRequest();
        $setMetadataReq->setProject($this->projectId);
        $setMetadataReq->setZone($zone);
        $setMetadataReq->setInstance($name);
        $setMetadataReq->setMetadataResource($metadata);

        $operation = $instancesClient->setMetadata($setMetadataReq);
        $this->waitForZoneOperation($operation);

        Log::info('Windows password reset: windows-keys metadata updated', [
            'project_id' => $this->projectId,
            'zone' => $zone,
            'instance' => $name,
        ]);
    }

    private function waitForWindowsEncryptedPassword(InstancesClient $instancesClient, string $name, string $zone, string $modulus): string
    {
        $request = new \Google\Cloud\Compute\V1\GetSerialPortOutputInstanceRequest();
        $request->setProject($this->projectId);
        $request->setZone($zone);
        $request->setInstance($name);
        $request->setPort(4);

        for ($attempt = 0; $attempt < self::WINDOWS_PASSWORD_POLL_ATTEMPTS; $attempt++) {
            if ($attempt === 0 || ($attempt + 1) % 10 === 0) {
                Log::info('Windows password reset: waiting for serial port output', [
                    'project_id' => $this->projectId,
                    'zone' => $zone,
                    'instance' => $name,
                    'attempt' => $attempt + 1,
                    'max_attempts' => self::WINDOWS_PASSWORD_POLL_ATTEMPTS,
                ]);
            }

            $output = $instancesClient->getSerialPortOutput($request);
            $contents = (string) $output->getContents();

            foreach (preg_split('/\r?\n/', $contents) as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }

                $decoded = json_decode($line, true);
                if (!is_array($decoded)) {
                    continue;
                }

                if (($decoded['modulus'] ?? null) !== $modulus) {
                    continue;
                }

                if (!empty($decoded['errorMessage'])) {
                    Log::warning('Windows password reset: agent returned error', [
                        'project_id' => $this->projectId,
                        'zone' => $zone,
                        'instance' => $name,
                        'error' => $decoded['errorMessage'],
                    ]);
                    throw new \RuntimeException('Windows password reset error: ' . $decoded['errorMessage']);
                }

                if (!empty($decoded['encryptedPassword'])) {
                    Log::info('Windows password reset: encrypted password received', [
                        'project_id' => $this->projectId,
                        'zone' => $zone,
                        'instance' => $name,
                    ]);
                    return $decoded['encryptedPassword'];
                }
            }

            sleep(self::WINDOWS_PASSWORD_POLL_SLEEP_SECONDS);
        }

        Log::warning('Windows password reset: timed out waiting for serial output', [
            'project_id' => $this->projectId,
            'zone' => $zone,
            'instance' => $name,
            'max_attempts' => self::WINDOWS_PASSWORD_POLL_ATTEMPTS,
        ]);

        throw new \RuntimeException('Timed out waiting for Windows password reset.');
    }

    private function decryptWindowsPassword(string $encryptedPassword, string $privateKeyPem): string
    {
        $privateKey = openssl_pkey_get_private($privateKeyPem);
        if (!$privateKey) {
            throw new \RuntimeException('Unable to load Windows private key for decryption.');
        }

        $encrypted = base64_decode($encryptedPassword, true);
        if ($encrypted === false) {
            throw new \RuntimeException('Unable to decode encrypted Windows password.');
        }

        $ok = openssl_private_decrypt($encrypted, $decrypted, $privateKey, OPENSSL_PKCS1_OAEP_PADDING);
        if (!$ok) {
            throw new \RuntimeException('Failed to decrypt Windows password.');
        }

        return (string) $decrypted;
    }

    public function resetWindowsPassword(string $name, string $zone, ?string $username = null): string
    {
        $instancesClient = new InstancesClient([
            'credentials' => $this->credentialsPath,
        ]);

        $username = $username ?: $this->windowsLoginUser();

        Log::info('Windows password reset: start', [
            'project_id' => $this->projectId,
            'zone' => $zone,
            'instance' => $name,
            'username' => $username,
        ]);

        try {
            [$payload, $privateKeyPem] = $this->buildWindowsKeyPayload($username);
            $this->appendWindowsKeyMetadata($instancesClient, $name, $zone, $payload);

            $encryptedPassword = $this->waitForWindowsEncryptedPassword(
                $instancesClient,
                $name,
                $zone,
                $payload['modulus']
            );

            $password = $this->decryptWindowsPassword($encryptedPassword, $privateKeyPem);

            Log::info('Windows password reset: success', [
                'project_id' => $this->projectId,
                'zone' => $zone,
                'instance' => $name,
                'username' => $username,
            ]);

            return $password;
        } catch (\Throwable $e) {
            Log::warning('Windows password reset: failed', [
                'project_id' => $this->projectId,
                'zone' => $zone,
                'instance' => $name,
                'username' => $username,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function linuxStartupScript(string $password, string $osType, ?string $osImage = null): string
    {
        $linuxUser = $this->linuxLoginUser($osType, $osImage);

        return "#!/bin/bash\n" .
            "set -e\n" .
            'CLOUDVPS_USER=' . escapeshellarg($linuxUser) . "\n" .
            'CLOUDVPS_PASSWORD=' . escapeshellarg($password) . "\n" .
            "id \"\$CLOUDVPS_USER\" >/dev/null 2>&1 || useradd -m -s /bin/bash \"\$CLOUDVPS_USER\"\n" .
            "printf '%s:%s\\n' \"\$CLOUDVPS_USER\" \"\$CLOUDVPS_PASSWORD\" | chpasswd\n" .
            "printf '%s:%s\\n' root \"\$CLOUDVPS_PASSWORD\" | chpasswd\n" .
            "usermod -aG sudo \"\$CLOUDVPS_USER\" 2>/dev/null || true\n" .
            "mkdir -p /etc/ssh/sshd_config.d\n" .
            "find /etc/ssh/sshd_config.d -type f -name '*.conf' -exec sed -i -E 's/^[[:space:]]*(PasswordAuthentication|KbdInteractiveAuthentication|ChallengeResponseAuthentication)[[:space:]].*/# &/g' {} \\; 2>/dev/null || true\n" .
            "cat > /etc/ssh/sshd_config.d/00-cloudvps-password-auth.conf <<'EOF'\n" .
            "PasswordAuthentication yes\n" .
            "KbdInteractiveAuthentication yes\n" .
            "ChallengeResponseAuthentication yes\n" .
            "PermitRootLogin yes\n" .
            "EOF\n" .
            "sed -i -E 's/^[[:space:]]*#?[[:space:]]*(PasswordAuthentication|KbdInteractiveAuthentication|ChallengeResponseAuthentication)[[:space:]].*/# &/g' /etc/ssh/sshd_config\n" .
            "mkdir -p /run/sshd\n" .
            "SSHD_BIN=\"\$(command -v sshd || echo /usr/sbin/sshd)\"\n" .
            "\"\$SSHD_BIN\" -t\n" .
            "systemctl restart ssh || systemctl restart sshd || service ssh restart\n";
    }

    private function remotePortForOs(?string $osType): int
    {
        return $osType === 'windows' ? 3389 : 22;
    }

    private function installingRemoteStatus(?string $osType): string
    {
        return $osType === 'windows' ? 'Đang cài RDP...' : 'Đang cài SSH...';
    }

    private function isRemotePortOpen(?string $ip, int $port, float $timeoutSeconds = 1.5): bool
    {
        if (!$ip) {
            return false;
        }

        $connection = @fsockopen($ip, $port, $errno, $errstr, $timeoutSeconds);
        if (!$connection) {
            return false;
        }

        fclose($connection);

        return true;
    }

    public function createInstance(string $name, string $zone, string $machineType, string $password = null, string $osType = 'ubuntu', int $diskSize = 50, string $osImage = null, array $networkTags = [])
    {
        $instancesClient = new InstancesClient([
            'credentials' => $this->credentialsPath
        ]);

        $instance = new Instance();
        $instance->setName($name);
        
        $machineTypeUrl = sprintf('zones/%s/machineTypes/%s', $zone, $machineType);
        $instance->setMachineType($machineTypeUrl);

        if (!empty($networkTags)) {
            $tags = new Tags();
            $tags->setItems(array_values(array_unique($networkTags)));
            $instance->setTags($tags);
        }

        $diskInitializeParams = new AttachedDiskInitializeParams();
        $diskInitializeParams->setSourceImage($this->sourceImagePath($osType, $osImage));
        
        $diskInitializeParams->setDiskSizeGb($diskSize);

        $disk = new AttachedDisk();
        $disk->setInitializeParams($diskInitializeParams);
        $disk->setAutoDelete(true);
        $disk->setBoot(true);
        $instance->setDisks([$disk]);

        // Default Network Intf & assigning Public IP (ONE_TO_ONE_NAT)
        $networkInterface = new NetworkInterface();
        $networkInterface->setNetwork('global/networks/default');
        
        $accessConfig = new AccessConfig();
        $accessConfig->setName('External NAT');
        $accessConfig->setType('ONE_TO_ONE_NAT');
        $accessConfig->setNetworkTier('PREMIUM');
        
        $networkInterface->setAccessConfigs([$accessConfig]);
        $instance->setNetworkInterfaces([$networkInterface]);

        if ($password && $osType !== 'windows') {
            $metadata = new \Google\Cloud\Compute\V1\Metadata();
            $item = new \Google\Cloud\Compute\V1\Items();

            $item->setKey('startup-script');
            $script = $this->linuxStartupScript($password, $osType, $osImage);
            $item->setValue($script);
            
            $metadata->setItems([$item]);
            $instance->setMetadata($metadata);
        }

        // Create the Request to GCP Compute
        $request = new InsertInstanceRequest();
        $request->setProject($this->projectId);
        $request->setZone($zone);
        $request->setInstanceResource($instance);

        $operation = $instancesClient->insert($request);
        $this->waitForZoneOperation($operation);

        return $operation;
    }

    private function waitForZoneOperation(OperationResponse $operation): void
    {
        $completed = $operation->pollUntilComplete([
            'initialPollDelayMillis' => 1000,
            'maxPollDelayMillis' => 5000,
            'totalPollTimeoutMillis' => 20000,
        ]);

        if (!$completed) {
            throw new \RuntimeException('GCP operation did not finish: ' . $operation->getName());
        }

        if (!$operation->operationFailed()) {
            return;
        }

        throw new \RuntimeException($this->formatOperationError($operation->getError()) ?: 'GCP operation failed.');
    }

    private function formatOperationError($error): string
    {
        if (!$error) {
            return '';
        }

        if (method_exists($error, 'getErrors')) {
            $messages = [];
            foreach ($error->getErrors() as $item) {
                $messages[] = trim($item->getCode() . ': ' . $item->getMessage());
            }

            return implode('; ', array_filter($messages));
        }

        if (method_exists($error, 'getMessage')) {
            return (string) $error->getMessage();
        }

        return (string) $error;
    }

    public function instanceExists(string $name, string $zone): bool
    {
        try {
            $instancesClient = new InstancesClient([
                'credentials' => $this->credentialsPath,
            ]);

            $request = new GetInstanceRequest();
            $request->setProject($this->projectId);
            $request->setZone($zone);
            $request->setInstance($name);

            $instancesClient->get($request);

            return true;
        } catch (\Google\ApiCore\ApiException $e) {
            $message = strtolower($e->getMessage());

            if (str_contains($message, 'not found') || str_contains($message, 'notfound') || str_contains($message, '404')) {
                return false;
            }

            throw $e;
        }
    }

    public function waitForInstanceVisibility(string $name, string $zone, int $maxAttempts = 10, int $sleepMicroseconds = 1000000): bool
    {
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            if ($this->instanceExists($name, $zone)) {
                return true;
            }

            usleep($sleepMicroseconds);
        }

        return false;
    }


    public static function firewallTargetTag(VpsInstance $vps): string
    {
        return 'cloudvps-vps-' . $vps->id;
    }

    public function syncFirewallRuleGroup(VpsInstance $vps, string $protocol, string $sourceRange, array $portRanges, string $ruleName): string
    {
        $targetTag = self::firewallTargetTag($vps);

        $this->ensureInstanceTag($vps, $targetTag);

        if (empty($portRanges)) {
            $this->deleteFirewallRule($ruleName);
            return $targetTag;
        }

        $allowed = new Allowed();
        $allowed->setIPProtocol($protocol);
        $allowed->setPorts(array_values($portRanges));

        $firewall = new Firewall();
        $firewall->setName($ruleName);
        $firewall->setDescription('CloudVPS grouped firewall rule for VPS #' . $vps->id);
        $firewall->setNetwork('global/networks/default');
        $firewall->setDirection('INGRESS');
        $firewall->setAllowed([$allowed]);
        $firewall->setSourceRanges([$sourceRange]);
        $firewall->setTargetTags([$targetTag]);

        $request = new InsertFirewallRequest();
        $request->setProject($this->projectId);
        $request->setFirewallResource($firewall);

        $client = new FirewallsClient([
            'credentials' => $this->credentialsPath,
        ]);

        if ($this->firewallRuleExists($client, $ruleName)) {
            $this->patchFirewallRule($client, $ruleName, $firewall);
            return $targetTag;
        }

        try {
            $client->insert($request);
        } catch (\Exception $e) {
            if (!str_contains(strtolower($e->getMessage()), 'already exists')) {
                throw $e;
            }

            $this->patchFirewallRule($client, $ruleName, $firewall);
        }

        return $targetTag;
    }

    private function firewallRuleExists(FirewallsClient $client, string $ruleName): bool
    {
        try {
            $request = new GetFirewallRequest();
            $request->setProject($this->projectId);
            $request->setFirewall($ruleName);

            $client->get($request);

            return true;
        } catch (\Exception $e) {
            $message = strtolower($e->getMessage());
            if (str_contains($message, 'not found') || str_contains($message, 'notfound') || str_contains($message, '404')) {
                return false;
            }

            throw $e;
        }
    }

    private function patchFirewallRule(FirewallsClient $client, string $ruleName, Firewall $firewall): void
    {
        $request = new PatchFirewallRequest();
        $request->setProject($this->projectId);
        $request->setFirewall($ruleName);
        $request->setFirewallResource($firewall);

        $client->patch($request);
    }

    public function deleteFirewallRule(string $ruleName): bool
    {
        try {
            $request = new DeleteFirewallRequest();
            $request->setProject($this->projectId);
            $request->setFirewall($ruleName);

            $client = new FirewallsClient([
                'credentials' => $this->credentialsPath,
            ]);

            $client->delete($request);

            return true;
        } catch (\Exception $e) {
            $message = strtolower($e->getMessage());
            if (str_contains($message, 'not found') || str_contains($message, 'notfound') || str_contains($message, '404')) {
                return true;
            }

            return false;
        }
    }

    public function ensureInstanceTag(VpsInstance $vps, string $targetTag): void
    {
        $instancesClient = new InstancesClient([
            'credentials' => $this->credentialsPath,
        ]);

        $getRequest = new GetInstanceRequest();
        $getRequest->setProject($this->projectId);
        $getRequest->setZone($vps->zone);
        $getRequest->setInstance($vps->name);

        $gcpInstance = $instancesClient->get($getRequest);
        $currentTags = $gcpInstance->getTags();
        $items = $currentTags ? iterator_to_array($currentTags->getItems()) : [];

        if (in_array($targetTag, $items, true)) {
            return;
        }

        $items[] = $targetTag;
        $items[] = 'cloudvps-managed';
        $items = array_values(array_unique(array_filter($items)));

        $tags = new Tags();
        $tags->setItems($items);
        if ($currentTags && $currentTags->getFingerprint()) {
            $tags->setFingerprint($currentTags->getFingerprint());
        }

        $setRequest = new SetTagsInstanceRequest();
        $setRequest->setProject($this->projectId);
        $setRequest->setZone($vps->zone);
        $setRequest->setInstance($vps->name);
        $setRequest->setTagsResource($tags);

        $instancesClient->setTags($setRequest);
    }

    public function syncInstanceStatus(\App\Models\VpsInstance $vps)
    {
        try {
            $instancesClient = new InstancesClient([
                'credentials' => $this->credentialsPath
            ]);
            
            $request = new \Google\Cloud\Compute\V1\GetInstanceRequest();
            $request->setProject($this->projectId);
            $request->setZone($vps->zone);
            $request->setInstance($vps->name);

            $gcpInstance = $instancesClient->get($request);
            $status = $gcpInstance->getStatus();
            $networkInterfaces = $gcpInstance->getNetworkInterfaces();

            if (count($networkInterfaces) > 0) {
                $accessConfigs = $networkInterfaces[0]->getAccessConfigs();
                if (count($accessConfigs) > 0 && $accessConfigs[0]->getNatIP()) {
                    $vps->public_ip = $accessConfigs[0]->getNatIP();
                }
            }
            
            if ($status === 'RUNNING') {
                $remotePort = $this->remotePortForOs($vps->os);
                $vps->status = $this->isRemotePortOpen($vps->public_ip, $remotePort)
                    ? 'Sẵn sàng'
                    : $this->installingRemoteStatus($vps->os);
            } elseif ($status === 'TERMINATED') {
                $vps->status = 'Đã tắt';
            } elseif ($status === 'PROVISIONING' || $status === 'STAGING') {
                // Vẫn đang config, giữ nguyên trạng thái
            } else {
                $vps->status = $status;
            }

            $vps->save();
            return true;
        } catch (\Google\ApiCore\ApiException $e) {
            // Lỗi API (ví dụ chưa tìm thấy do Google đang trễ init)
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function deleteInstance(string $name, string $zone)
    {
        try {
            $instancesClient = new InstancesClient([
                'credentials' => $this->credentialsPath
            ]);
            
            $request = new \Google\Cloud\Compute\V1\DeleteInstanceRequest();
            $request->setProject($this->projectId);
            $request->setZone($zone);
            $request->setInstance($name);

            $instancesClient->delete($request);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function rebootInstance(string $name, string $zone)
    {
        $instancesClient = new InstancesClient(['credentials' => $this->credentialsPath]);
        $request = new \Google\Cloud\Compute\V1\ResetInstanceRequest();
        $request->setProject($this->projectId);
        $request->setZone($zone);
        $request->setInstance($name);
        $instancesClient->reset($request);
        return true;
    }

    public function setInstancePassword(string $name, string $zone, string $newPassword, string $osType): string
    {
        $instancesClient = new InstancesClient(['credentials' => $this->credentialsPath]);

        if ($osType === 'windows') {
            return $this->resetWindowsPassword($name, $zone);
        }
        
        $request = new \Google\Cloud\Compute\V1\GetInstanceRequest();
        $request->setProject($this->projectId);
        $request->setZone($zone);
        $request->setInstance($name);

        $gcpInstance = $instancesClient->get($request);
        $metadata = $gcpInstance->getMetadata();
        
        $items = $metadata->getItems();
        $newItems = [];
        $keyToSet = $osType === 'windows' ? 'windows-startup-script-bat' : 'startup-script';
        
        $script = "";
        $script = $this->linuxStartupScript($newPassword, $osType);

        $newItem = new \Google\Cloud\Compute\V1\Items();
        $newItem->setKey($keyToSet);
        $newItem->setValue($script);

        foreach ($items as $item) {
            if ($item->getKey() !== $keyToSet) {
                $newItems[] = $item; // Keep other metadata
            }
        }
        $newItems[] = $newItem;
        
        $metadata->setItems($newItems);

        $setMetadataReq = new \Google\Cloud\Compute\V1\SetMetadataInstanceRequest();
        $setMetadataReq->setProject($this->projectId);
        $setMetadataReq->setZone($zone);
        $setMetadataReq->setInstance($name);
        $setMetadataReq->setMetadataResource($metadata);

        $instancesClient->setMetadata($setMetadataReq);
        
        // Reboot to apply
        $this->rebootInstance($name, $zone);
        return $newPassword;
    }

    public function upgradeMachineType(string $name, string $zone, string $newMachineTypeRaw)
    {
        $instancesClient = new InstancesClient(['credentials' => $this->credentialsPath]);

        // Tắt máy chủ
        $stopReq = new \Google\Cloud\Compute\V1\StopInstanceRequest();
        $stopReq->setProject($this->projectId);
        $stopReq->setZone($zone);
        $stopReq->setInstance($name);
        $instancesClient->stop($stopReq);

        // Vòng lặp chờ Google xả dữ liệu HDD an toàn (Tối đa 60 giây)
        for ($i = 0; $i < 12; $i++) {
            sleep(5);
            $getReq = new \Google\Cloud\Compute\V1\GetInstanceRequest();
            $getReq->setProject($this->projectId);
            $getReq->setZone($zone);
            $getReq->setInstance($name);
            $gcpInstance = $instancesClient->get($getReq);
            if ($gcpInstance->getStatus() === 'TERMINATED') {
                break;
            }
        }

        // Set Machine Type
        $machineTypeReq = new \Google\Cloud\Compute\V1\SetMachineTypeInstanceRequest();
        $machineTypeReq->setProject($this->projectId);
        $machineTypeReq->setZone($zone);
        $machineTypeReq->setInstance($name);
        
        $instancesSetMachineTypeRequestResource = new \Google\Cloud\Compute\V1\InstancesSetMachineTypeRequest();
        $machineTypeUrl = sprintf('zones/%s/machineTypes/%s', $zone, $newMachineTypeRaw);
        $instancesSetMachineTypeRequestResource->setMachineType($machineTypeUrl);
        $machineTypeReq->setInstancesSetMachineTypeRequestResource($instancesSetMachineTypeRequestResource);
        
        $instancesClient->setMachineType($machineTypeReq);

        // Đợi 5 giây cho Google lập chỉ mục phần cứng mới
        sleep(5);

        // Bật lại máy chủ
        $this->startInstance($name, $zone);

        return true;
    }

    public function startInstance(string $name, string $zone)
    {
        $instancesClient = new InstancesClient(['credentials' => $this->credentialsPath]);
        $request = new \Google\Cloud\Compute\V1\StartInstanceRequest();
        $request->setProject($this->projectId);
        $request->setZone($zone);
        $request->setInstance($name);
        $instancesClient->start($request);
        return true;
    }

    public function getRegionQuotas(string $regionName = 'asia-southeast1')
    {
        try {
            $regionsClient = new \Google\Cloud\Compute\V1\Client\RegionsClient([
                'credentials' => $this->credentialsPath
            ]);
            $request = new \Google\Cloud\Compute\V1\GetRegionRequest();
            $request->setProject($this->projectId);
            $request->setRegion($regionName);

            $region = $regionsClient->get($request);
            
            $result = [];
            foreach ($region->getQuotas() as $quota) {
                $metric = $quota->getMetric();
                // Chúng ta quan tâm tới 4 thông số dễ bị giới hạn nhất
                if (in_array($metric, ['CPUS', 'INSTANCES', 'IN_USE_ADDRESSES', 'DISKS_TOTAL_GB'])) {
                    $result[$metric] = [
                        'limit' => $quota->getLimit(),
                        'usage' => $quota->getUsage(),
                    ];
                }
            }
            return $result;
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getProjectQuotas()
    {
        try {
            $projectsClient = new \Google\Cloud\Compute\V1\Client\ProjectsClient([
                'credentials' => $this->credentialsPath
            ]);

            $request = new \Google\Cloud\Compute\V1\GetProjectRequest();
            $request->setProject($this->projectId);

            $project = $projectsClient->get($request);
            $result = [];

            foreach ($project->getQuotas() as $quota) {
                $metric = $quota->getMetric();
                if (in_array($metric, ['CPUS_ALL_REGIONS'])) {
                    $result[$metric] = [
                        'limit' => $quota->getLimit(),
                        'usage' => $quota->getUsage(),
                    ];
                }
            }

            return $result;
        } catch (\Exception $e) {
            return [];
        }
    }

    public function hasQuotaForPlan(string $regionName, int $cpu, int $diskGb = 0): array
    {
        $regionQuotas = $this->getRegionQuotas($regionName);
        $projectQuotas = $this->getProjectQuotas();

        $checks = [
            'CPUS_ALL_REGIONS' => [
                'label' => 'CPUs (all regions)',
                'needed' => $cpu,
                'quota' => $projectQuotas['CPUS_ALL_REGIONS'] ?? null,
            ],
            'CPUS' => [
                'label' => 'CPUs',
                'needed' => $cpu,
                'quota' => $regionQuotas['CPUS'] ?? null,
            ],
            'INSTANCES' => [
                'label' => 'VM instances',
                'needed' => 1,
                'quota' => $regionQuotas['INSTANCES'] ?? null,
            ],
            'IN_USE_ADDRESSES' => [
                'label' => 'In-use regional external IPv4 addresses',
                'needed' => 1,
                'quota' => $regionQuotas['IN_USE_ADDRESSES'] ?? null,
            ],
            'DISKS_TOTAL_GB' => [
                'label' => 'Persistent Disk Standard (GB)',
                'needed' => $diskGb,
                'quota' => $regionQuotas['DISKS_TOTAL_GB'] ?? null,
            ],
        ];

        foreach ($checks as $metric => $check) {
            if (!$check['quota']) {
                continue;
            }

            $available = $check['quota']['limit'] - $check['quota']['usage'];
            if ($available < $check['needed']) {
                return [
                    'ok' => false,
                    'metric' => $metric,
                    'label' => $check['label'],
                    'limit' => $check['quota']['limit'],
                    'usage' => $check['quota']['usage'],
                    'needed' => $check['needed'],
                    'available' => max(0, $available),
                ];
            }
        }

        return ['ok' => true];
    }
}
