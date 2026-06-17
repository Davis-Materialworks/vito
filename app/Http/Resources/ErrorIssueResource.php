<?php

namespace App\Http\Resources;

use App\Models\ErrorIssue;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ErrorIssue */
class ErrorIssueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'server_id' => $this->server_id,
            'site_id' => $this->site_id,
            'fingerprint' => $this->fingerprint,
            'exception_class' => $this->exception_class,
            'normalized_message' => $this->normalized_message,
            'top_frame' => $this->top_frame,
            'status' => $this->status->getText(),
            'status_color' => $this->status->getColor(),
            'count' => $this->count,
            'affected_users' => $this->affected_users,
            'first_seen_at' => $this->first_seen_at,
            'last_seen_at' => $this->last_seen_at,
            'resolved_at' => $this->resolved_at,
            'events_count' => $this->events_count ?? $this->events()->count(),
            'site' => $this->whenLoaded('site', fn () => [
                'id' => $this->site->id,
                'domain' => $this->site->domain,
            ]),
            'latest_event' => $this->whenLoaded('latestEvent', fn () => new ErrorEventResource($this->latestEvent)),
            'first_release' => $this->whenLoaded('firstEvent', fn () => $this->firstEvent?->release),
            'last_release' => $this->whenLoaded('latestEvent', fn () => $this->latestEvent?->release),
            'created_at' => $this->created_at,
        ];
    }
}
