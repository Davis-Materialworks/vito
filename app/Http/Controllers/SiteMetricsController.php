<?php

namespace App\Http\Controllers;

use App\Actions\Monitoring\GetSiteMetrics;
use App\Models\Server;
use App\Models\Site;
use App\Models\SiteMetric;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('servers/{server}/sites/{site}/metrics')]
#[Middleware(['auth', 'has-project'])]
class SiteMetricsController extends Controller
{
    #[Get('/json', name: 'site-metrics.json')]
    public function json(Request $request, Server $server, Site $site): JsonResponse
    {
        $this->authorize('view', [$site, $server]);

        $metrics = app(GetSiteMetrics::class)->filter($site, $request->input());

        return response()->json($metrics);
    }
}
