<?php

namespace App\Http\Resources;

use App\Models\ErrorEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ErrorEvent */
class ErrorEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'error_issue_id' => $this->error_issue_id,
            'server_id' => $this->server_id,
            'site_id' => $this->site_id,
            'environment' => $this->environment,
            'release' => $this->release,
            'exception_class' => $this->exception_class,
            'message' => $this->message,
            'stack_trace' => $this->stack_trace,
            'url' => $this->url,
            'request_method' => $this->request_method,
            'user_id' => $this->user_id,
            'user_email' => $this->user_email,
            'context' => $this->context,
            'occurred_at' => $this->occurred_at,
            'created_at' => $this->created_at,
        ];
    }
}
