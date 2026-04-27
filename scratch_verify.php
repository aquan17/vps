<?php

// Mocking some Laravel helpers if needed, but since I'm running in the env, I'll just use a Tinker-like approach or a script that loads Laravel.
// Actually, I can use run_command with 'php artisan tinker' to verify.

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\GcpProject;
use App\Services\GcpVpsService;

echo "--- Testing GcpProject Model Attribute ---\n";

$legacyProject = new GcpProject(['credentials_file' => 'C:\\Old\\Path\\to\\file.json']);
echo "Legacy Absolute Path: " . $legacyProject->credentials_path . "\n";

$newProject = new GcpProject(['credentials_file' => 'new_credential.json']);
echo "New Filename Only Path: " . $newProject->credentials_path . "\n";

echo "\n--- Testing GcpVpsService setProjectSettings ---\n";
$service = new GcpVpsService();

// Mock a file if it doesn't exist for testing is_file checks
$testFile = storage_path('app/gcp_credentials/test_resolve.json');
if (!file_exists(dirname($testFile))) {
    mkdir(dirname($testFile), 0777, true);
}
file_put_contents($testFile, '{}');

$service->setProjectSettings('test-proj', 'test_resolve.json');

// Reflection to check protected property
$reflection = new ReflectionClass($service);
$prop = $reflection->getProperty('credentialsPath');
$prop->setAccessible(true);
$resolvedPath = $prop->getValue($service);

echo "Service Resolved Path: " . $resolvedPath . "\n";

if ($resolvedPath === $testFile) {
    echo "SUCCESS: Path resolved correctly to gcp_credentials folder.\n";
} else {
    echo "FAILURE: Path resolved to " . $resolvedPath . "\n";
}

// Clean up
unlink($testFile);
