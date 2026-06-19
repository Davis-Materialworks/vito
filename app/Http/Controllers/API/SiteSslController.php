<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\SslResource;
use App\Models\Project;
use App\Models\Server;
use App\Models\Site;
use App\Models\Ssl;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('api/projects/{project}/servers/{server}/sites/{site}/ssls')]
#[Middleware(['auth:sanctum', 'can-see-project'])]
class SiteSslController extends Controller
{
    #[Get('/', name: 'api.projects.servers.sites.ssls', middleware: 'ability:read')]
    public function index(Project $project, Server $server, Site $site): ResourceCollection
    {
        $this->authorize('viewAny', [Ssl::class, $server]);

        $this->validateRoute($project, $server, $site);

        return SslResource::collection(
            $server->ssls()->where('site_id', $site->id)->simplePaginate(25)
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
