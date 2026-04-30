<?php

namespace App\Services;

class VpsPricingService
{
    public function getPlans(): array
    {
        return [
            'plan_mini'     => ['name' => 'Starter G1',  'desc' => 'Học tập, Web nhẹ',                    'cores' => 2, 'ram' => 8,  'disk' => 50,  'type' => 'e2-standard-2',     'price_per_month' => 45000],
            'plan_standard' => ['name' => 'Pro G2',       'desc' => 'Web, Tool MMO siêu nhẹ',               'cores' => 2, 'ram' => 12, 'disk' => 70,  'type' => 'e2-custom-2-12288', 'price_per_month' => 89000],
            'plan_advanced' => ['name' => 'Ultra G3',     'desc' => 'Lưu trữ, bot, dịch vụ nền',           'cores' => 4, 'ram' => 8,  'disk' => 80,  'type' => 'e2-custom-4-8192',  'price_per_month' => 145000],
            'plan_pro'      => ['name' => 'Titan G4',     'desc' => 'Cân bằng CPU & RAM tối đa',            'cores' => 4, 'ram' => 16, 'disk' => 80,  'type' => 'e2-standard-4',     'price_per_month' => 279000],
            'plan_pro_plus' => ['name' => 'Titan G4+',   'desc' => 'CPU mạnh, RAM cao cho đa nhiệm',       'cores' => 4, 'ram' => 32, 'disk' => 90,  'type' => 'e2-highmem-4',      'price_per_month' => 345000],
            'plan_extreme'  => ['name' => 'Extreme G5',  'desc' => 'Đa nhiệm cao, khối lượng lớn',         'cores' => 8, 'ram' => 32, 'disk' => 200, 'type' => 'e2-standard-8',     'price_per_month' => 390000],
            'plan_godlike'  => ['name' => 'Godlike G6',  'desc' => 'Siêu RAM chạy Node, giả lập',          'cores' => 8, 'ram' => 64, 'disk' => 400, 'type' => 'e2-highmem-8',      'price_per_month' => 888888],
            'plan_maximum'  => ['name' => 'Maximum G7',  'desc' => 'Goi max theo quota API 12 CPU',        'cores' => 12, 'ram' => 96, 'disk' => 600, 'type' => 'e2-custom-12-98304', 'price_per_month' => 1299000],
        ];
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
}
