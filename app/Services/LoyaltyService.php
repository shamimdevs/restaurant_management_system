<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\LoyaltyProgram;

class LoyaltyService
{
    private function getProgram(int $branchId): ?LoyaltyProgram
    {
        $branch = \App\Models\Branch::find($branchId);
        return LoyaltyProgram::where('company_id', $branch->company_id)
                             ->where('is_active', true)
                             ->first();
    }

    public function earnPoints(int $customerId, float $orderAmount, int $orderId): int
    {
        $customer = Customer::find($customerId);
        if (! $customer) return 0;

        $program = $this->getProgram($customer->company_id);
        if (! $program) return 0;

        $points = $program->calculateEarnPoints($orderAmount);
        if ($points <= 0) return 0;

        $customer->addPoints($points, "Earned from order #{$orderId}", $orderId);
        return $points;
    }

    public function calculateRedeemValue(int $customerId, int $points, int $branchId): float
    {
        $program = $this->getProgram($branchId);
        if (! $program) return 0;

        $customer = Customer::find($customerId);
        if (! $customer || $customer->loyalty_points < $points) return 0;

        return $program->calculateRedeemValue($points);
    }

    public function redeemPoints(int $customerId, int $points, int $orderId): bool
    {
        $customer = Customer::find($customerId);
        return $customer ? $customer->redeemPoints($points, $orderId) : false;
    }
}
