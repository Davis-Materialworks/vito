<?php

namespace App\Actions\Monitoring;

use App\Enums\DeploymentStatus;
use App\Models\Site;
use App\Models\SiteMetric;
use Carbon\Carbon;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use stdClass;

class GetSiteMetrics
{
    /**
     * @param  array<string, mixed>  $input
     * @return array{current: ?array<string, mixed>, history: Collection<int, stdClass>}
     */
    public function filter(Site $site, array $input): array
    {
        $input = array_merge(['period' => '10m'], $input);

        $this->validate($input);

        if (isset($input['from'])) {
            $input['from'] = Carbon::parse($input['from'])->format('Y-m-d').' 00:00:00';
        }

        if (isset($input['to'])) {
            $input['to'] = Carbon::parse($input['to'])->format('Y-m-d').' 23:59:59';
        }

        $from = $this->getFromDate($input);
        $to = $this->getToDate($input);
        $history = $this->metrics(
            site: $site,
            fromDate: $from,
            toDate: $to,
            interval: $this->getInterval($input)
        );

        return [
            'current' => $this->current($site),
            'history' => $history,
            'deploys' => $this->deployMarkers($site, $from, $to, $history),
        ];
    }

    /**
     * Finished deployments within the window, each snapped to the nearest
     * metrics bucket so the chart can draw a reference line that lines up with
     * the categorical x-axis.
     *
     * @param  Collection<int, stdClass>  $history
     * @return array<int, array{date: string, commit: string}>
     */
    private function deployMarkers(Site $site, Carbon $from, Carbon $to, Collection $history): array
    {
        if ($history->isEmpty()) {
            return [];
        }

        $deploys = $site->deployments()
            ->where('status', DeploymentStatus::FINISHED)
            ->whereBetween('created_at', [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')])
            ->orderBy('created_at')
            ->get(['commit_id', 'created_at']);

        if ($deploys->isEmpty()) {
            return [];
        }

        $buckets = [];
        foreach ($history as $row) {
            $buckets[$row->date] = Carbon::createFromFormat('Y-m-d H:i', $row->date)->getTimestamp();
        }

        return $deploys->map(function (object $deploy) use ($buckets): array {
            $target = $deploy->created_at->getTimestamp();
            $nearest = null;
            $bestDiff = null;
            foreach ($buckets as $date => $timestamp) {
                $diff = abs($timestamp - $target);
                if ($bestDiff === null || $diff < $bestDiff) {
                    $bestDiff = $diff;
                    $nearest = $date;
                }
            }

            return [
                'date' => (string) $nearest,
                'commit' => substr((string) $deploy->commit_id, 0, 8),
            ];
        })->all();
    }

    /**
     * @return ?array<string, mixed>
     */
    private function current(Site $site): ?array
    {
        /** @var ?SiteMetric $latest */
        $latest = $site->siteMetrics()->latest('id')->first();

        if (! $latest) {
            return null;
        }

        return [
            'date' => $latest->created_at->format('Y-m-d H:i:s'),
            'requests' => $latest->requests,
            'avg_response_ms' => $latest->avg_response_ms,
            'p95_response_ms' => $latest->p95_response_ms,
            'error_rate' => $latest->error_rate,
            'bytes' => $latest->bytes,
            'status_2xx' => $latest->status_2xx,
            'status_3xx' => $latest->status_3xx,
            'status_4xx' => $latest->status_4xx,
            'status_5xx' => $latest->status_5xx,
        ];
    }

    /**
     * @return Collection<int, stdClass>
     */
    private function metrics(
        Site $site,
        Carbon $fromDate,
        Carbon $toDate,
        ?Expression $interval = null
    ): Collection {
        return DB::table('site_metrics')
            ->where('site_id', $site->id)
            ->whereBetween('created_at', [$fromDate->format('Y-m-d H:i:s'), $toDate->format('Y-m-d H:i:s')])
            ->select(
                [
                    DB::raw('created_at as date'),
                    DB::raw('SUM(requests) as requests'),
                    DB::raw('ROUND(AVG(avg_response_ms), 2) as avg_response_ms'),
                    DB::raw('ROUND(MAX(p95_response_ms), 2) as p95_response_ms'),
                    DB::raw('ROUND(AVG(error_rate), 2) as error_rate'),
                    DB::raw('SUM(bytes) as bytes'),
                    DB::raw('SUM(status_2xx) as status_2xx'),
                    DB::raw('SUM(status_3xx) as status_3xx'),
                    DB::raw('SUM(status_4xx) as status_4xx'),
                    DB::raw('SUM(status_5xx) as status_5xx'),
                    $interval,
                ],
            )
            ->groupByRaw('date_interval')
            ->orderBy('date_interval')
            ->get()
            ->map(function ($item): stdClass {
                $intFields = ['requests', 'bytes', 'status_2xx', 'status_3xx', 'status_4xx', 'status_5xx'];
                foreach ($intFields as $key) {
                    $item->{$key} = $item->{$key} !== null ? (int) $item->{$key} : 0;
                }
                $floatFields = ['avg_response_ms', 'p95_response_ms', 'error_rate'];
                foreach ($floatFields as $key) {
                    $item->{$key} = $item->{$key} !== null ? (float) $item->{$key} : null;
                }
                $item->date = Carbon::parse($item->date)->format('Y-m-d H:i');
                unset($item->date_interval);

                return $item;
            });
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function getFromDate(array $input): Carbon
    {
        if ($input['period'] === 'custom') {
            return new Carbon($input['from']);
        }

        return Carbon::parse('-'.convert_time_format($input['period']));
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function getToDate(array $input): Carbon
    {
        if ($input['period'] === 'custom') {
            return new Carbon($input['to']);
        }

        return Carbon::now();
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function getInterval(array $input): Expression
    {
        if ($input['period'] === 'custom') {
            $from = new Carbon($input['from']);
            $to = new Carbon($input['to']);
            $periodInHours = $from->diffInHours($to);
        }

        if (! isset($periodInHours)) {
            $periodInHours = Carbon::parse(
                convert_time_format($input['period'])
            )->diffInHours();
        }

        if (abs($periodInHours) <= 1) {
            return DB::raw("strftime('%Y-%m-%d %H:%M:00', created_at) as date_interval");
        }

        if ($periodInHours <= 24) {
            return DB::raw("strftime('%Y-%m-%d %H:00:00', created_at) as date_interval");
        }

        return DB::raw("strftime('%Y-%m-%d 00:00:00', created_at) as date_interval");
    }

    private function validate(array $input): void
    {
        $isCustom = ($input['period'] ?? null) === 'custom';

        $rules = [
            'period' => [
                'required',
                Rule::in([
                    '10m',
                    '30m',
                    '1h',
                    '12h',
                    '1d',
                    '7d',
                    'custom',
                ]),
            ],
            'from' => array_filter([$isCustom ? 'required' : 'nullable', 'date', $isCustom ? 'before_or_equal:to' : null]),
            'to' => array_filter([$isCustom ? 'required' : 'nullable', 'date', $isCustom ? 'after_or_equal:from' : null]),
        ];

        Validator::make($input, $rules)->validate();
    }
}
