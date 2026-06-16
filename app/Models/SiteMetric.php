<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteMetric extends Model
{
    protected $fillable = [
        'site_id',
        'server_id',
        'requests',
        'avg_response_ms',
        'p95_response_ms',
        'error_rate',
        'bytes',
        'status_2xx',
        'status_3xx',
        'status_4xx',
        'status_5xx',
        'window_start',
    ];

    protected $casts = [
        'site_id' => 'integer',
        'server_id' => 'integer',
        'requests' => 'integer',
        'avg_response_ms' => 'float',
        'p95_response_ms' => 'float',
        'error_rate' => 'float',
        'bytes' => 'integer',
        'status_2xx' => 'integer',
        'status_3xx' => 'integer',
        'status_4xx' => 'integer',
        'status_5xx' => 'integer',
        'window_start' => 'datetime',
    ];

    /**
     * @return BelongsTo<Site, covariant $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * @return BelongsTo<Server, covariant $this>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}
