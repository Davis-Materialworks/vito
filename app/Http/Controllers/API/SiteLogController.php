<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServerLogResource;
use App\Models\Project;
use App\Models\Server;
use App\Models\ServerLog;
use App\Models\Site;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('api/projects/{project}/servers/{server}/sites/{site}/logs')]
#[Middleware(['auth:sanctum', 'can-see-project'])]
class SiteLogController extends Controller
{
    #[Get('/', name: 'api.projects.servers.sites.logs', middleware: 'ability:read')]
    public function index(Project $project, Server $server, Site $site): ResourceCollection
    {
        $this->authorize('view', [$site, $server]);

        $this->validateRoute($project, $server, $site);

        return ServerLogResource::collection(
            ServerLog::query()
                ->where('server_id', $server->id)
                ->where('site_id', $site->id)
                ->orderByDesc('created_at')
                ->simplePaginate(25)
        );
    }

    private function validateRoute(Project $project, Server $server, ?Site $site = null): void
    {
        if ($project->id !== $server->project_id) {
            abort(404, 'Server not found in project');
        }

        if ($site && $site->server_id !== $server->id) {
            abort(404, 'Site not found in server');
        }
    }
}
