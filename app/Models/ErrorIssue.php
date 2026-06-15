<?php

namespace App\Models;

use App\Enums\ErrorIssueStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $server_id
 * @property int $site_id
 * @property string $fingerprint
 * @property string $exception_class
 * @property string $normalized_message
 * @property ?string $top_frame
 * @property ErrorIssueStatus $status
 * @property int $count
 * @property int $affected_users
 * @property \Illuminate\Support\Carbon $first_seen_at
 * @property \Illuminate\Support\Carbon $last_seen_at
 * @property ?\Illuminate\Support\Carbon $resolved_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \App\Models\Server $server
 * @property \App\Models\Site $site
 * @property \Illuminate\Database\Eloquent\Collection<ErrorEvent> $events
 * @property ?ErrorEvent $latestEvent
 * @property ?ErrorEvent $firstEvent
 */
/** @use HasFactory<\Database\Factories\ErrorIssueFactory> */
class ErrorIssue extends AbstractModel
{
    use HasFactory;

    protected $fillable = [
        'server_id',
        'site_id',
        'fingerprint',
        'exception_class',
        'normalized_message',
        'top_frame',
        'status',
        'count',
        'affected_users',
        'first_seen_at',
        'last_seen_at',
        'resolved_at',
    ];

    protected $casts = [
        'server_id' => 'integer',
        'site_id' => 'integer',
        'count' => 'integer',
        'affected_users' => 'integer',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'resolved_at' => 'datetime',
        'status' => ErrorIssueStatus::class,
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ErrorEvent::class)->latest('occurred_at');
    }

    public function latestEvent(): HasOne
    {
        return $this->hasOne(ErrorEvent::class)->latestOfMany('occurred_at');
    }

    public function firstEvent(): HasOne
    {
        return $this->hasOne(ErrorEvent::class)->oldestOfMany('occurred_at');
    }
}
