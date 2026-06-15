<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('settings/mcp')]
#[Middleware(['auth'])]
class McpController extends Controller
{
    #[Get('/', name: 'settings.mcp')]
    public function index(): Response
    {
        return Inertia::render('mcp/index', [
            'apiUrl' => config('app.url'),
            'healthEndpoint' => config('app.url').'/api/health',
        ]);
    }
}
