<?php

namespace App\Services;

use App\Models\VpsFirewallRule;
use App\Models\VpsInstance;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class VpsFirewallPolicy
{
    public const MAX_RANGE_SIZE = 50;
    public const MAX_PORTS_PER_VPS = 20;

    private const PUBLIC_ALLOWED_PORTS = [80, 443, 8080, 8443];
    private const RESTRICTED_PORTS = [22, 3389, 3000, 5000, 8000, 9000];
    private const BLOCKED_PORTS = [25, 465, 587, 110, 143, 3306, 5432, 6379, 27017, 9200, 11211];
    private const PUBLIC_SOURCE_RANGES = ['0.0.0.0/0', '::/0'];

    public static function parsePortRange(string $value): array
    {
        $value = preg_replace('/\s+/', '', $value);

        if (!preg_match('/^\d{1,5}(-\d{1,5})?$/', $value)) {
            throw new InvalidArgumentException('Port khong hop le. Vi du: 80 hoac 3000-3010.');
        }

        [$start, $end] = array_pad(explode('-', $value, 2), 2, null);
        $start = (int) $start;
        $end = $end === null ? $start : (int) $end;

        if ($start < 1 || $end > 65535 || $start > $end) {
            throw new InvalidArgumentException('Port phai nam trong khoang 1-65535.');
        }

        return [$start, $end];
    }

    public static function normalizeSource(Request $request, string $sourceType, ?string $sourceRange): string
    {
        if ($sourceType === 'any') {
            return '0.0.0.0/0';
        }

        if ($sourceType === 'my_ip') {
            $ip = self::publicClientIp($request);
            if ($ip && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                return $ip . '/32';
            }
            if ($ip && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                return $ip . '/128';
            }

            throw new InvalidArgumentException('Khong xac dinh duoc IP public hien tai. Vui long chon IP/CIDR tuy chinh va nhap IP public cua ban.');
        }

        $sourceRange = trim((string) $sourceRange);
        if ($sourceRange === '') {
            throw new InvalidArgumentException('Vui long nhap IP hoac CIDR nguon.');
        }

        if (filter_var($sourceRange, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $sourceRange . '/32';
        }

        if (filter_var($sourceRange, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $sourceRange . '/128';
        }

        if (!preg_match('/^(.+)\/(\d{1,3})$/', $sourceRange, $matches)) {
            throw new InvalidArgumentException('CIDR nguon khong hop le. Vi du: 203.0.113.10 hoac 203.0.113.0/24.');
        }

        $ip = $matches[1];
        $prefix = (int) $matches[2];

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && $prefix >= 0 && $prefix <= 32) {
            return $ip . '/' . $prefix;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) && $prefix >= 0 && $prefix <= 128) {
            return $ip . '/' . $prefix;
        }

        throw new InvalidArgumentException('CIDR nguon khong hop le.');
    }

    private static function publicClientIp(Request $request): ?string
    {
        $candidates = [];

        foreach (['CF-Connecting-IP', 'X-Real-IP', 'X-Forwarded-For'] as $header) {
            $value = (string) $request->headers->get($header, '');
            foreach (explode(',', $value) as $ip) {
                $candidates[] = trim($ip);
            }
        }

        $candidates[] = $request->ip();

        foreach ($candidates as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }

        return null;
    }

    public static function assertFirewallPortAllowed(string $protocol, int $start, int $end, string $sourceRange): void
    {
        if (!in_array($protocol, ['tcp', 'udp'], true)) {
            throw new InvalidArgumentException('Protocol chi ho tro TCP hoac UDP.');
        }

        if ($start === 1 && $end === 65535) {
            throw new InvalidArgumentException('Khong duoc mo toan bo port.');
        }

        $rangeSize = $end - $start + 1;
        if ($rangeSize > self::MAX_RANGE_SIZE) {
            throw new InvalidArgumentException('Moi lan chi duoc mo toi da ' . self::MAX_RANGE_SIZE . ' port lien tiep.');
        }

        if (self::isPublicSource($sourceRange)) {
            if ($start !== $end) {
                throw new InvalidArgumentException('Public access khong duoc mo port range. Chi mo tung port rieng le.');
            }

            if (!in_array($start, self::PUBLIC_ALLOWED_PORTS, true)) {
                throw new InvalidArgumentException('Public access chi cho phep cac port: 80, 443, 8080, 8443.');
            }
        }
    }

    public static function assertVpsPortLimit(VpsInstance $vps, int $newStart, int $newEnd, ?int $ignoreRuleId = null): void
    {
        $rules = $vps->firewallRules()
            ->when($ignoreRuleId, function ($query) use ($ignoreRuleId) {
                return $query->where('id', '!=', $ignoreRuleId);
            })
            ->get(['port_start', 'port_end']);

        $total = $rules->sum(function ($rule) {
            return (int) $rule->port_end - (int) $rule->port_start + 1;
        });
        $total += $newEnd - $newStart + 1;

        if ($total > self::MAX_PORTS_PER_VPS) {
            throw new InvalidArgumentException('Moi VPS chi duoc mo toi da ' . self::MAX_PORTS_PER_VPS . ' port custom.');
        }
    }

    public static function isPublicSource(string $sourceRange): bool
    {
        return in_array($sourceRange, self::PUBLIC_SOURCE_RANGES, true);
    }

    public static function containsBlockedPort(int $start, int $end): bool
    {
        foreach (self::BLOCKED_PORTS as $port) {
            if ($start <= $port && $port <= $end) {
                return true;
            }
        }

        return false;
    }

    public static function containsRestrictedPort(int $start, int $end): bool
    {
        foreach (self::RESTRICTED_PORTS as $port) {
            if ($start <= $port && $port <= $end) {
                return true;
            }
        }

        return false;
    }

    public static function mergePortRanges(iterable $rules): array
    {
        $ranges = collect($rules)
            ->map(function ($rule) {
                return [
                    'start' => (int) (is_array($rule) ? $rule['port_start'] : $rule->port_start),
                    'end' => (int) (is_array($rule) ? $rule['port_end'] : $rule->port_end),
                ];
            })
            ->sortBy(['start', 'end'])
            ->values();

        $merged = [];
        foreach ($ranges as $range) {
            if (empty($merged) || $range['start'] > $merged[count($merged) - 1]['end'] + 1) {
                $merged[] = $range;
                continue;
            }

            $last = count($merged) - 1;
            $merged[$last]['end'] = max($merged[$last]['end'], $range['end']);
        }

        return array_map(function ($range) {
            return self::formatPortRange($range['start'], $range['end']);
        }, $merged);
    }

    public static function formatPortRange(int $start, int $end): string
    {
        return $start === $end ? (string) $start : $start . '-' . $end;
    }

    public static function entryRuleName(VpsInstance $vps, string $protocol, int $start, int $end, string $sourceRange): string
    {
        $port = str_replace('-', 'to', self::formatPortRange($start, $end));
        $hash = substr(sha1($vps->id . '|' . $protocol . '|' . $start . '|' . $end . '|' . $sourceRange), 0, 8);

        return 'cloudvps-v' . $vps->id . '-' . $protocol . '-' . $port . '-' . $hash;
    }

    public static function groupRuleName(VpsInstance $vps, string $protocol, string $sourceRange): string
    {
        $sourceKey = self::isPublicSource($sourceRange) ? 'public' : substr(sha1($sourceRange), 0, 8);

        return 'fw-vps-' . $vps->id . '-' . $protocol . '-' . $sourceKey;
    }
}

