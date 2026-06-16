<?php

namespace Tests\Feature;

use App\Models\ErrorIssue;
use App\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ErrorIngestionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'exception_class' => 'RuntimeException',
            'message' => 'Something broke at id 42',
            'stack_trace' => "#0 /home/vito/app/Http/Controllers/FooController.php(10): bar()\n#1 {main}",
            'environment' => 'production',
            'release' => 'v1.0.0',
        ], $overrides);
    }

    private function url(?Server $server = null): string
    {
        $server ??= $this->server;

        return route('api.errors.ingest', [
            'project' => $server->project_id,
            'server' => $server->id,
            'site' => $this->site->id,
        ]);
    }

    public function test_it_ingests_an_error_with_a_valid_token(): void
    {
        $token = $this->site->ingestToken();

        $this->postJson($this->url(), $this->payload(), [
            'Authorization' => 'Bearer '.$token,
        ])->assertCreated();

        $this->assertDatabaseHas('error_issues', [
            'site_id' => $this->site->id,
            'exception_class' => 'RuntimeException',
        ]);
        $this->assertDatabaseHas('error_events', [
            'site_id' => $this->site->id,
            'message' => 'Something broke at id 42',
        ]);
    }

    public function test_it_rejects_an_invalid_token(): void
    {
        $this->site->ingestToken();

        $this->postJson($this->url(), $this->payload(), [
            'Authorization' => 'Bearer wrong-token',
        ])->assertUnauthorized();

        $this->assertDatabaseCount('error_events', 0);
    }

    public function test_it_rejects_when_no_token_is_configured(): void
    {
        $this->postJson($this->url(), $this->payload(), [
            'Authorization' => 'Bearer anything',
        ])->assertUnauthorized();
    }

    public function test_it_rejects_a_site_that_does_not_belong_to_the_server(): void
    {
        $token = $this->site->ingestToken();

        $otherServer = Server::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->server->project_id,
        ]);

        $this->postJson($this->url($otherServer), $this->payload(), [
            'Authorization' => 'Bearer '.$token,
        ])->assertNotFound();
    }

    public function test_it_stores_a_remote_user_id_without_a_foreign_key(): void
    {
        $token = $this->site->ingestToken();

        $this->postJson(
            $this->url(),
            $this->payload(['user_id' => 999999, 'user_email' => 'remote@example.com']),
            ['Authorization' => 'Bearer '.$token],
        )->assertCreated();

        $this->assertDatabaseHas('error_events', [
            'site_id' => $this->site->id,
            'user_id' => 999999,
        ]);
    }

    public function test_it_groups_repeated_errors_into_one_issue(): void
    {
        $token = $this->site->ingestToken();

        $this->postJson($this->url(), $this->payload(['message' => 'Broke at id 1']), ['Authorization' => 'Bearer '.$token])->assertCreated();
        $this->postJson($this->url(), $this->payload(['message' => 'Broke at id 2']), ['Authorization' => 'Bearer '.$token])->assertCreated();

        $this->assertDatabaseCount('error_issues', 1);
        $this->assertDatabaseCount('error_events', 2);
        $this->assertSame(2, ErrorIssue::query()->firstOrFail()->count);
    }
}
