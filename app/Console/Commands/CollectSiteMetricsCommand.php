<?php

namespace App\Console\Commands;

use App\Enums\ServerStatus;
use App\Enums\SiteStatus;
use App\Models\Server;
use App\Models\SiteMetric;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class CollectSiteMetricsCommand extends Command
{
    protected $signature = 'site-metrics:collect';

    protected $description = 'Collect request metrics from nginx access logs for all sites';

    public function handle(): void
    {
        $checkedMetrics = 0;

        Server::query()
            ->where('status', ServerStatus::READY)
            ->whereHas('services', function (Builder $query): void {
                $query->where('name', 'nginx');
            })
            ->chunk(10, function ($servers) use (&$checkedMetrics): void {
                /** @var Server $server */
                foreach ($servers as $server) {
                    $sites = $server->sites()
                        ->where('status', SiteStatus::READY)
                        ->get();

                    foreach ($sites as $site) {
                        try {
                            $data = $server->os()->siteMetrics($site);
                            if (! empty($data) && ($data['requests'] ?? 0) > 0) {
                                SiteMetric::create([
                                    'site_id' => $site->id,
                                    'server_id' => $server->id,
                                    'requests' => $data['requests'] ?? 0,
                                    'avg_response_ms' => $data['avg_response_ms'] ?? null,
                                    'p95_response_ms' => $data['p95_response_ms'] ?? null,
                                    'error_rate' => $data['error_rate'] ?? null,
                                    'bytes' => $data['bytes'] ?? 0,
                                    'status_2xx' => $data['status_2xx'] ?? 0,
                                    'status_3xx' => $data['status_3xx'] ?? 0,
                                    'status_4xx' => $data['status_4xx'] ?? 0,
                                    'status_5xx' => $data['status_5xx'] ?? 0,
                                    'window_start' => Carbon::now()->startOfMinute(),
                                ]);
                                $checkedMetrics++;
                            }
                        } catch (Throwable $e) {
                            Log::warning('Failed to collect metrics for site', [
                                'site_id' => $site->id,
                                'domain' => $site->domain,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }
            });

        $this->info("Checked $checkedMetrics site metrics");
    }
}
