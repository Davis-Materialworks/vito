<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $error_issue_id
 * @property int $server_id
 * @property int $site_id
 * @property ?string $environment
 * @property ?string $release
 * @property string $exception_class
 * @property string $message
 * @property string $stack_trace
 * @property ?string $url
 * @property ?string $request_method
 * @property ?int $user_id
 * @property ?string $user_email
 * @property ?array $context
 * @property Carbon $occurred_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property ErrorIssue $issue
 * @property Server $server
 * @property Site $site
 */
/** @use HasFactory<\Database\Factories\ErrorEventFactory> */
class ErrorEvent extends AbstractModel
{
    use HasFactory;

    protected $fillable = [
        'error_issue_id',
        'server_id',
        'site_id',
        'environment',
        'release',
        'exception_class',
        'message',
        'stack_trace',
        'url',
        'request_method',
        'user_id',
        'user_email',
        'context',
        'occurred_at',
    ];

    protected $casts = [
        'error_issue_id' => 'integer',
        'server_id' => 'integer',
        'site_id' => 'integer',
        'user_id' => 'integer',
        'context' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function issue(): BelongsTo
    {
        return $this->belongsTo(ErrorIssue::class, 'error_issue_id');
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
