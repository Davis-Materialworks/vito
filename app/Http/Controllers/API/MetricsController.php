<?php

namespace App\Http\Controllers\API;

use App\Actions\Monitoring\GetMetrics;
use App\Http\Controllers\Controller;
use App\Models\Metric;
use App\Models\Project;
use App\Models\Server;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('api/projects/{project}/servers/{server}/metrics')]
#[Middleware(['auth:sanctum', 'can-see-project'])]
class MetricsController extends Controller
{
    #[Get('/', name: 'api.projects.servers.metrics', middleware: 'ability:read')]
    public function index(Request $request, Project $project, Server $server): JsonResponse
    {
        $this->authorize('viewAny', [Metric::class, $server]);

        $this->validateRoute($project, $server);

        $metrics = app(GetMetrics::class)->filter($server, $request->input());

        return response()->json($metrics);
    }

    private function validateRoute(Project $project, Server $server): void
    {
        if ($project->id !== $server->project_id) {
            abort(404, 'Server not found in project');
        }
    }
}
