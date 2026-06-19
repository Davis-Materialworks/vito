<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationChannelResource;
use App\Models\NotificationChannel;
use App\Models\Project;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('api/projects/{project}/notification-channels')]
#[Middleware(['auth:sanctum', 'can-see-project'])]
class NotificationChannelApiController extends Controller
{
    #[Get('/', name: 'api.projects.notification-channels', middleware: 'ability:read')]
    public function index(Project $project): ResourceCollection
    {
        $this->authorize('viewAny', [NotificationChannel::class]);

        $channels = NotificationChannel::getByProjectId($project->id, user())->get();

        return NotificationChannelResource::collection($channels);
    }
}
