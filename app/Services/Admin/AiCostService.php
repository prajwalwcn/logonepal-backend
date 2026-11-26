<?php

namespace App\Services\Admin;

use App\Models\AiGenerationLog;
use App\Models\AiModelPrice;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class AiCostService
{
    /**
     * @return array{cost: float, currency: string}
     */
    public function estimate(string $provider, ?string $model, int $tokensIn, int $tokensOut): array
    {
        $price = AiModelPrice::query()
            ->where('provider', $provider)
            ->where('model', $model ?? '')
            ->where('is_active', true)
            ->first();

        if ($price === null) {
            return ['cost' => 0.0, 'currency' => 'USD'];
        }

        $cost = ($tokensIn / 1_000_000) * (float) $price->input_per_million
            + ($tokensOut / 1_000_000) * (float) $price->output_per_million;

        return ['cost' => round($cost, 6), 'currency' => $price->currency];
    }

    /**
     * @return array{total: float, currency: string, requests: int, tokens_in: int, tokens_out: int, by_provider: Collection, by_purpose: Collection}
     */
    public function summary(?CarbonInterface $from = null, ?CarbonInterface $to = null): array
    {
        $query = AiGenerationLog::query();
        if ($from !== null && $to !== null) {
            $query->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()]);
        }

        $logs = $query->get(['provider', 'model', 'purpose', 'tokens_in', 'tokens_out', 'estimated_cost', 'currency']);
        $prices = AiModelPrice::query()
            ->where('is_active', true)
            ->get()
            ->keyBy(fn (AiModelPrice $price) => $price->provider.'|'.$price->model);

        $total = 0.0;
        $currency = 'USD';
        $byProvider = [];
        $byPurpose = [];

        foreach ($logs as $log) {
            $cost = (float) $log->estimated_cost;
            if ($cost <= 0) {
                $price = $prices->get(($log->provider ?? 'unknown').'|'.($log->model ?? ''));
                if ($price !== null) {
                    $cost = (($log->tokens_in ?? 0) / 1_000_000) * (float) $price->input_per_million
                        + (($log->tokens_out ?? 0) / 1_000_000) * (float) $price->output_per_million;
                    $currency = $price->currency ?: $currency;
                }
            }

            $total += $cost;
            $provider = $log->provider ?: 'unknown';
            $purpose = $log->purpose ?: 'unknown';
            $byProvider[$provider] = ($byProvider[$provider] ?? 0) + $cost;
            $byPurpose[$purpose] = ($byPurpose[$purpose] ?? 0) + $cost;
        }

        return [
            'total' => round($total, 6),
            'currency' => $currency,
            'requests' => $logs->count(),
            'tokens_in' => (int) $logs->sum('tokens_in'),
            'tokens_out' => (int) $logs->sum('tokens_out'),
            'by_provider' => collect($byProvider)->map(fn ($cost, $provider) => ['provider' => $provider, 'cost' => round($cost, 6)])->values(),
            'by_purpose' => collect($byPurpose)->map(fn ($cost, $purpose) => ['purpose' => $purpose, 'cost' => round($cost, 6)])->values(),
        ];
    }
}
