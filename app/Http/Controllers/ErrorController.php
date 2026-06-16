<?php

namespace App\Http\Controllers;

use App\Actions\Error\IgnoreIssue;
use App\Actions\Error\ResolveIssue;
use App\Http\Resources\ErrorEventResource;
use App\Http\Resources\ErrorIssueResource;
use App\Http\Resources\ServerResource;
use App\Models\ErrorIssue;
use App\Models\Server;
use App\Models\Site;
use App\Tables\Servers\ErrorIssueTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('servers/{server}')]
#[Middleware(['auth', 'has-project'])]
class ErrorController extends Controller
{
    #[Get('/errors', name: 'errors')]
    public function index(Server $server, Request $request): Response
    {
        $this->authorize('viewAny', [ErrorIssue::class, $server]);

        $query = ErrorIssue::query()
            ->where('server_id', $server->id)
            ->when($request->input('site_id'), fn ($q, $id) => $q->where('site_id', $id))
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->input('environment'), function ($q, $env) {
                $q->whereHas('events', fn ($e) => $e->where('environment', $env));
            })
            ->when($request->input('release'), function ($q, $rel) {
                $q->whereHas('events', fn ($e) => $e->where('release', $rel));
            })
            ->with('latestEvent', 'site')
            ->withCount('events');

        return Inertia::render('error-monitoring/index', [
            'server' => new ServerResource($server),
            'issues' => ErrorIssueTable::make($query)->simplePaginate(),
            'sites' => Site::query()
                ->where('server_id', $server->id)
                ->orderBy('domain')
                ->get(['id', 'domain']),
        ]);
    }

    #[Get('/errors/{errorIssue}', name: 'errors.show')]
    public function show(Server $server, ErrorIssue $errorIssue, Request $request): Response
    {
        $this->authorize('view', [$errorIssue, $server]);

        $events = $errorIssue->events()
            ->when($request->input('environment'), fn ($q, $env) => $q->where('environment', $env))
            ->latest('occurred_at')
            ->simplePaginate(50);

        return Inertia::render('error-monitoring/show', [
            'server' => new ServerResource($server),
            'issue' => new ErrorIssueResource($errorIssue->load('site', 'firstEvent', 'latestEvent')),
            'events' => ErrorEventResource::collection($events),
        ]);
    }

    #[Post('/errors/{errorIssue}/resolve', name: 'errors.resolve')]
    public function resolve(Server $server, ErrorIssue $errorIssue): RedirectResponse
    {
        $this->authorize('update', [$errorIssue, $server]);

        app(ResolveIssue::class)->resolve($errorIssue);

        return back()->with('success', 'Issue resolved');
    }

    #[Post('/errors/{errorIssue}/ignore', name: 'errors.ignore')]
    public function ignore(Server $server, ErrorIssue $errorIssue): RedirectResponse
    {
        $this->authorize('update', [$errorIssue, $server]);

        app(IgnoreIssue::class)->ignore($errorIssue);

        return back()->with('success', 'Issue ignored');
    }

    #[Get('/errors-integration/{site}', name: 'errors.integration')]
    public function integration(Server $server, Site $site): JsonResponse
    {
        $this->authorize('manageIntegration', [ErrorIssue::class, $server]);

        abort_unless($site->server_id === $server->id, 404);

        return response()->json($this->integrationPayload($server, $site));
    }

    #[Post('/errors-integration/{site}/regenerate', name: 'errors.integration.regenerate')]
    public function regenerateIntegration(Server $server, Site $site): JsonResponse
    {
        $this->authorize('manageIntegration', [ErrorIssue::class, $server]);

        abort_unless($site->server_id === $server->id, 404);

        $site->regenerateIngestToken();

        return response()->json($this->integrationPayload($server, $site));
    }

    /**
     * @return array{endpoint: string, token: string}
     */
    private function integrationPayload(Server $server, Site $site): array
    {
        return [
            'endpoint' => route('api.errors.ingest', [
                'project' => $server->project_id,
                'server' => $server->id,
                'site' => $site->id,
            ]),
            'token' => $site->ingestToken(),
        ];
    }
}
