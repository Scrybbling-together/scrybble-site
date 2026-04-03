<?php

namespace App\Console\Commands;

use App\Models\GumroadSale;
use App\Models\GumroadSubscriber;
use App\Services\GumroadApi;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SyncGumroadMetrics extends Command
{
    protected $signature = 'gumroad:sync-metrics {--debug : Dump raw API responses and exit}';
    protected $description = 'Sync subscriber and sales data from Gumroad into the local database';

    public function handle(GumroadApi $api): int
    {
        $productId = config('services.gumroad.product_id');

        if (!$productId) {
            $this->error('GUMROAD_PRODUCT_ID is not set.');
            return 1;
        }

        if ($this->option('debug')) {
            return $this->debug($api, $productId);
        }

        $this->syncSubscribers($api, $productId);
        $this->syncSales($api);
        $this->backfillChargeOccurrenceCounts();

        return 0;
    }

    private function syncSubscribers(GumroadApi $api, string $productId): void
    {
        $this->info('Syncing subscribers...');

        $response = $api->getSubscribers($productId);
        $subscribers = $response['subscribers'] ?? [];

        $rows = array_map(fn(array $sub) => [
            'subscriber_id' => $sub['id'],
            'customer_hash' => hash('sha256', $sub['email']),
            'product_id' => $sub['product_id'],
            'recurrence' => $sub['recurrence'],
            'status' => $sub['status'],
            'created_at' => $this->parseTimestamp($sub['created_at']),
            'cancelled_at' => $this->parseTimestamp($sub['cancelled_at'] ?? null),
            'ended_at' => $this->parseTimestamp($sub['ended_at'] ?? null),
            'failed_at' => $this->parseTimestamp($sub['failed_at'] ?? null),
            'free_trial_ends_at' => $this->parseTimestamp($sub['free_trial_ends_at'] ?? null),
            'charge_occurrence_count' => $sub['charge_occurrence_count'] ?? 0,
            'purchase_ids' => json_encode($sub['purchase_ids'] ?? []),
            'updated_at' => now(),
        ], $subscribers);

        foreach (array_chunk($rows, 100) as $chunk) {
            GumroadSubscriber::upsert($chunk, ['subscriber_id'], [
                'customer_hash', 'status', 'cancelled_at', 'ended_at', 'failed_at',
                'free_trial_ends_at', 'charge_occurrence_count', 'purchase_ids', 'updated_at',
            ]);
        }

        $this->info("Synced " . count($rows) . " subscribers.");
    }

    private function syncSales(GumroadApi $api): void
    {
        $this->info('Syncing sales...');

        $total = 0;
        $pageKey = null;

        do {
            $response = $api->getSales($pageKey);
            $sales = $response['sales'] ?? [];

            $rows = array_map(fn(array $sale) => [
                'sale_id' => $sale['id'],
                'subscription_id' => $sale['subscription_id'] ?? null,
                'created_at' => $this->parseTimestamp($sale['created_at']),
                'price' => $sale['price'],
                'subscription_duration' => $sale['subscription_duration'] ?? null,
                'variants' => json_encode($sale['variants'] ?? null),
                'referrer' => $sale['referrer'] ?? null,
                'paid' => $sale['paid'] ?? false,
                'cancelled' => $sale['cancelled'] ?? false,
                'refunded' => $sale['refunded'] ?? false,
                'partially_refunded' => $sale['partially_refunded'] ?? false,
                'updated_at' => now(),
            ], $sales);

            foreach (array_chunk($rows, 100) as $chunk) {
                GumroadSale::upsert($chunk, ['sale_id'], [
                    'subscription_id', 'price', 'cancelled', 'refunded',
                    'partially_refunded', 'updated_at',
                ]);
            }

            $total += count($sales);
            $pageKey = $response['next_page_key'] ?? null;

            if ($pageKey) {
                $this->line("  Fetched $total sales so far...");
            }
        } while ($pageKey);

        $this->info("Synced $total sales.");
    }

    private function backfillChargeOccurrenceCounts(): void
    {
        $this->info('Backfilling charge_occurrence_count from sales...');

        DB::statement("
            UPDATE gumroad_subscribers gs
            SET charge_occurrence_count = (
                SELECT COUNT(*)
                FROM gumroad_sales
                WHERE subscription_id = gs.subscriber_id AND paid = 1
            )
        ");

        $this->info('Done.');
    }

    private function parseTimestamp(?string $value): ?string
    {
        return $value ? Carbon::parse($value)->format('Y-m-d H:i:s') : null;
    }

    private function debug(GumroadApi $api, string $productId): int
    {
        $this->info('Fetching subscribers sample...');
        $subscribersResponse = $api->getSubscribers($productId);
        $this->line(json_encode($subscribersResponse, JSON_PRETTY_PRINT));

        $this->newLine();
        $this->info('Fetching sales sample...');
        $salesResponse = $api->getSales();
        $this->line(json_encode($salesResponse, JSON_PRETTY_PRINT));

        return 0;
    }
}