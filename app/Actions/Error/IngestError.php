<?php

namespace App\Actions\Error;

use App\Enums\ErrorIssueStatus;
use App\Models\ErrorEvent;
use App\Models\ErrorIssue;
use App\Models\Server;
use App\Models\Site;
use App\Notifications\ErrorIssueCreated;
use App\Notifications\ErrorIssueRegressed;
use Illuminate\Support\Facades\DB;

class IngestError
{
    public function ingest(Server $server, Site $site, array $input): ErrorEvent
    {
        $normalizedMessage = $this->normalizeMessage($input['message']);
        $topFrame = $this->topFrame($input['stack_trace']);

        $fingerprint = md5(
            $input['exception_class'] .
            $normalizedMessage .
            $topFrame
        );

        return DB::transaction(function () use ($server, $site, $input, $fingerprint, $normalizedMessage, $topFrame) {
            $issue = ErrorIssue::query()
                ->where('site_id', $site->id)
                ->where('fingerprint', $fingerprint)
                ->first();

            $isNew = ! $issue;

            if ($isNew) {
                $issue = new ErrorIssue([
                    'server_id' => $server->id,
                    'site_id' => $site->id,
                    'fingerprint' => $fingerprint,
                    'exception_class' => $input['exception_class'],
                    'normalized_message' => mb_substr($normalizedMessage, 0, 1024),
                    'top_frame' => $topFrame,
                    'status' => ErrorIssueStatus::UNRESOLVED,
                    'count' => 1,
                    'affected_users' => isset($input['user_id']) ? 1 : 0,
                    'first_seen_at' => now(),
                    'last_seen_at' => now(),
                ]);
                $issue->save();
            } else {
                $issue->last_seen_at = now();
                $issue->count++;

                if (isset($input['user_id'])) {
                    $existingUserIds = ErrorEvent::query()
                        ->where('error_issue_id', $issue->id)
                        ->whereNotNull('user_id')
                        ->pluck('user_id')
                        ->toArray();

                    if (! in_array($input['user_id'], $existingUserIds)) {
                        $issue->affected_users++;
                    }
                }

                if ($issue->status === ErrorIssueStatus::RESOLVED) {
                    $issue->status = ErrorIssueStatus::UNRESOLVED;
                    $issue->resolved_at = null;
                }

                $issue->save();
            }

            $event = new ErrorEvent([
                'error_issue_id' => $issue->id,
                'server_id' => $server->id,
                'site_id' => $site->id,
                'environment' => $input['environment'] ?? null,
                'release' => $input['release'] ?? null,
                'exception_class' => $input['exception_class'],
                'message' => $input['message'],
                'stack_trace' => $input['stack_trace'],
                'url' => $input['url'] ?? null,
                'request_method' => $input['request_method'] ?? null,
                'user_id' => $input['user_id'] ?? null,
                'user_email' => $input['user_email'] ?? null,
                'context' => $input['context'] ?? null,
                'occurred_at' => $input['occurred_at'] ?? now(),
            ]);
            $event->save();

            if ($isNew) {
                $this->notifyNewIssue($issue);
            } elseif ($issue->wasChanged('status') && $issue->getOriginal('status') === ErrorIssueStatus::RESOLVED->value) {
                $this->notifyRegressed($issue);
            }

            return $event;
        });
    }

    private function normalizeMessage(string $message): string
    {
        $message = preg_replace('/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/i', '<UUID>', $message);
        $message = preg_replace('/[0-9a-f]{32,}/i', '<HASH>', $message);
        $message = preg_replace('/\d+/', '<N>', $message);

        return $message;
    }

    private function topFrame(string $trace): ?string
    {
        $lines = explode("\n", $trace);

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if (empty($trimmed)) {
                continue;
            }

            if (str_starts_with($trimmed, '#')) {
                continue;
            }

            if (str_contains($trimmed, 'vendor/')) {
                continue;
            }

            if (preg_match('/[\/\\\\](app|src|config|routes)\//', $trimmed)) {
                return $trimmed;
            }
        }

        return trim($lines[0] ?? 'unknown');
    }

    private function notifyNewIssue(ErrorIssue $issue): void
    {
        try {
            $site = $issue->site;
            $project = $site->server->project;

            foreach ($project->notificationChannels as $channel) {
                $channel->notify(new ErrorIssueCreated($issue, $site));
            }
        } catch (\Throwable) {
            //
        }
    }

    private function notifyRegressed(ErrorIssue $issue): void
    {
        try {
            $site = $issue->site;
            $project = $site->server->project;

            foreach ($project->notificationChannels as $channel) {
                $channel->notify(new ErrorIssueRegressed($issue, $site));
            }
        } catch (\Throwable) {
            //
        }
    }
}
