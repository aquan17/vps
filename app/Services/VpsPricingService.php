<?php

namespace App\Services;

use App\Models\User;
use App\Models\VpsInstance;

class VpsPricingService
{
    public function getPlans(): array
    {
        return [
            'plan_mini'     => ['name' => 'Starter G1',  'desc' => 'Học tập, Web nhẹ',                    'cores' => 2, 'ram' => 8,  'disk' => 50,  'type' => 'c4d-standard-2',     'price_per_month' => 60000],
            'plan_standard' => ['name' => 'Pro G2',       'desc' => 'Web, Tool MMO siêu nhẹ',               'cores' => 2, 'ram' => 16, 'disk' => 70,  'type' => 'c4d-highmem-2', 'price_per_month' => 175000],
            'plan_advanced' => ['name' => 'Ultra G3',     'desc' => 'Lưu trữ, bot, dịch vụ nền',           'cores' => 4, 'ram' => 8,  'disk' => 80,  'type' => 'c4d-highcpu-4',  'price_per_month' => 255000],
            'plan_pro'      => ['name' => 'Titan G4',     'desc' => 'Cân bằng CPU & RAM tối đa',            'cores' => 4, 'ram' => 16, 'disk' => 80,  'type' => 'c4d-standard-4',     'price_per_month' => 295000],
            'plan_pro_plus' => ['name' => 'Titan G4+',   'desc' => 'CPU mạnh, RAM cao cho đa nhiệm',       'cores' => 4, 'ram' => 32, 'disk' => 90,  'type' => 'c4d-highmem-4',      'price_per_month' => 355000],
            'plan_balanced' => ['name' => 'Titan G5 ', 'desc' => '8 CPU, 16GB RAM cho nhu cầu cân bằng', 'cores' => 8, 'ram' => 16, 'disk' => 120, 'type' => 'c4d-highcpu-8',  'price_per_month' => 445000],
            'plan_extreme'  => ['name' => 'Extreme G5+',  'desc' => 'Đa nhiệm cao, khối lượng lớn',         'cores' => 8, 'ram' => 32, 'disk' => 200, 'type' => 'c4d-standard-8',     'price_per_month' => 550000],
            'plan_godlike'  => ['name' => 'Godlike G6',  'desc' => 'Siêu RAM chạy Node, giả lập',          'cores' => 8, 'ram' => 64, 'disk' => 400, 'type' => 'c4d-highmem-8',      'price_per_month' => 777777],
            // 'plan_maximum'  => ['name' => 'Maximum G7',  'desc' => 'Goi max theo quota API 12 CPU',        'cores' => 12, 'ram' => 96, 'disk' => 600, 'type' => 'e2-custom-12-98304', 'price_per_month' => 1299000],

            /**
             * Gói kín: users.is_admin (0 = thường, >0 = mức gán).
             * - admin_only: true | 1 | 2 | … → user phải có is_admin >= mức đó (true/1 = khác 0).
             * - admin_only: [1, 2] → chỉ is_admin là 1 hoặc 2 (không gồm 3+; khác kiểu “tối thiểu”).
             */
            'plan_admin_2c4g' => [
                'name' => 'AMD',
                'desc' => '2 CPU, 4GB RAM — gói nội bộ admin',
                'cores' => 2,
                'ram' => 4,
                'disk' => 40,
                'type' => 'c4d-highcpu-2',
                'price_per_month' => 40000,
                'admin_only' => [1, 2],
            ],
            ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $plans
     * @return array<string, array<string, mixed>>
     */
    public function plansVisibleToUser(User $user, array $plans): array
    {
        $userTier = (int) ($user->is_admin ?? 0);

        return array_filter($plans, function (array $p) use ($userTier) {
            if (!$this->planRequiresIsAdminTier($p)) {
                return true;
            }

            return $this->userMeetsIsAdminForPlan($userTier, $p);
        });
    }

    /** Có khóa theo cột is_admin hay không (0 = user thường, không thấy). */
    private function planRequiresIsAdminTier(array $plan): bool
    {
        if (!array_key_exists('admin_only', $plan)) {
            return false;
        }

        $v = $plan['admin_only'];
        if ($v === false || $v === null) {
            return false;
        }
        if ($v === true) {
            return true;
        }
        if (is_array($v)) {
            return $this->normalizedIsAdminWhitelist($v) !== [];
        }

        return is_numeric($v) && (int) $v > 0;
    }

    /** @return list<int> */
    private function normalizedIsAdminWhitelist(array $values): array
    {
        $ints = array_map(static fn ($x) => (int) $x, $values);
        $positive = array_values(array_unique(array_filter($ints, static fn (int $x) => $x > 0)));

        return $positive;
    }

    private function userMeetsIsAdminForPlan(int $userTier, array $plan): bool
    {
        $v = $plan['admin_only'];
        if (is_array($v)) {
            $allowed = $this->normalizedIsAdminWhitelist($v);

            return $allowed !== [] && in_array($userTier, $allowed, true);
        }

        return $userTier >= $this->minimumIsAdminTierForPlan($plan);
    }

    /** Mức tối thiểu của users.is_admin để chọn gói (1 = bất kỳ giá trị khác 0). */
    private function minimumIsAdminTierForPlan(array $plan): int
    {
        $v = $plan['admin_only'];

        return $v === true ? 1 : max(1, (int) $v);
    }

    /** Khóa gói hợp lệ khi tạo VPS / preview voucher (chặn gửi plan giả mạo từ client). */
    public function planKeysSelectableByUser(User $user): array
    {
        return array_keys($this->plansVisibleToUser($user, $this->getPlans()));
    }

    public function calculatePrice(array $plan, int $duration): int
    {
        $monthlyPrice = (int) ($plan['price_per_month'] ?? $plan['price_per_day'] ?? 0);
        $total = ($duration / 30) * $monthlyPrice;

        if ($duration === 365) {
            $total *= 0.85;
        }

        return (int) round($total);
    }

    public function findPlanByMachineType(?string $machineType): ?array
    {
        foreach ($this->getPlans() as $plan) {
            if ($plan['type'] === $machineType) {
                return $plan;
            }
        }

        return null;
    }

    /**
     * Billing plan for renew/upgrade: exact machine_type, then cpu/ram on record, then GCP custom type string.
     */
    public function resolvePlanForVps(VpsInstance $vps): ?array
    {
        $byType = $this->findPlanByMachineType($vps->machine_type);
        if ($byType !== null) {
            return $byType;
        }

        $cpu = (int) ($vps->cpu ?? 0);
        $ramGb = (int) ($vps->ram ?? 0);
        if ($cpu > 0 && $ramGb > 0) {
            foreach ($this->getPlans() as $plan) {
                if ((int) ($plan['cores'] ?? 0) === $cpu && (int) ($plan['ram'] ?? 0) === $ramGb) {
                    return $plan;
                }
            }
        }

        $parsed = $this->parseGcpCustomMachineType($vps->machine_type);
        if ($parsed !== null) {
            [$vcpu, $ramFromType] = $parsed;
            foreach ($this->getPlans() as $plan) {
                if ((int) ($plan['cores'] ?? 0) === $vcpu && (int) ($plan['ram'] ?? 0) === $ramFromType) {
                    return $plan;
                }
            }
        }

        return null;
    }

    /**
     * @return array{0: int, 1: int}|null [vcpu, ram_gb]
     */
    private function parseGcpCustomMachineType(?string $machineType): ?array
    {
        if ($machineType === null || $machineType === '') {
            return null;
        }

        if (!preg_match('/-custom-(\d+)-(\d+)$/', $machineType, $m)) {
            return null;
        }

        $vcpu = (int) $m[1];
        $memMb = (int) $m[2];
        if ($vcpu <= 0 || $memMb <= 0) {
            return null;
        }

        $ramGb = (int) round($memMb / 1024);

        return [$vcpu, $ramGb];
    }
}
