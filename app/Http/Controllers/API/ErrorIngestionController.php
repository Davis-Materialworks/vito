<?php

namespace App\Http\Controllers\API;

use App\Actions\Error\IngestError;
use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Models\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('api/projects/{project}/servers/{server}/sites/{site}')]
class ErrorIngestionController extends Controller
{
    #[Post('errors', name: 'api.errors.ingest', middleware: 'throttle:error-ingest')]
    public function ingest(Request $request, Server $server, Site $site): JsonResponse
    {
        if ($site->server_id !== $server->id) {
            abort(404);
        }

        $expected = $site->error_ingest_token;
        $token = $request->bearerToken();

        if (empty($expected) || empty($token) || ! hash_equals((string) $expected, (string) $token)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $input = Validator::make($request->all(), [
            'exception_class' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'stack_trace' => ['required', 'string'],
            'environment' => ['nullable', 'string', 'max:50'],
            'release' => ['nullable', 'string', 'max:255'],
            'url' => ['nullable', 'string', 'max:2048'],
            'request_method' => ['nullable', 'string', 'max:10'],
            'user_id' => ['nullable', 'integer'],
            'user_email' => ['nullable', 'email', 'max:255'],
            'context' => ['nullable', 'array'],
            'occurred_at' => ['nullable', 'date'],
        ])->validate();

        $event = app(IngestError::class)->ingest($server, $site, $input);

        return response()->json([
            'id' => $event->id,
            'issue_id' => $event->error_issue_id,
        ], 201);
    }
}
