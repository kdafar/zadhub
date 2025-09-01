<?php

namespace App\Services\Automations;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class ConditionEvaluatorService
{
    public function allConditionsMet(?array $conditions, array $data): bool
    {
        if (empty($conditions)) {
            return true; // No conditions means the step should always run
        }

        foreach ($conditions as $condition) {
            if (! $this->isConditionMet($condition, $data)) {
                return false; // If any condition is not met, fail the whole set
            }
        }

        return true; // All conditions were met
    }

    private function isConditionMet(array $condition, array $data): bool
    {
        $key = $condition['data_key'] ?? null;
        $operator = $condition['operator'] ?? null;
        $expectedValue = $condition['value'] ?? null;

        if (! $key || ! $operator) {
            Log::warning('Invalid condition in automation step', ['condition' => $condition]);

            return false;
        }

        $actualValue = Arr::get($data, $key);

        switch ($operator) {
            case 'eq':
                return $actualValue == $expectedValue;
            case 'neq':
                return $actualValue != $expectedValue;
            case 'gt':
                return is_numeric($actualValue) && is_numeric($expectedValue) && $actualValue > $expectedValue;
            case 'lt':
                return is_numeric($actualValue) && is_numeric($expectedValue) && $actualValue < $expectedValue;
            case 'gte':
                return is_numeric($actualValue) && is_numeric($expectedValue) && $actualValue >= $expectedValue;
            case 'lte':
                return is_numeric($actualValue) && is_numeric($expectedValue) && $actualValue <= $expectedValue;
            case 'contains':
                if (is_array($actualValue)) {
                    return in_array($expectedValue, $actualValue);
                }

                return str_contains((string) $actualValue, (string) $expectedValue);
            case 'not_contains':
                if (is_array($actualValue)) {
                    return ! in_array($expectedValue, $actualValue);
                }

                return ! str_contains((string) $actualValue, (string) $expectedValue);
            default:
                return false;
        }
    }
}
