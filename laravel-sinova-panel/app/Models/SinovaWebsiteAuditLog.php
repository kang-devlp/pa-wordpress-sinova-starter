<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SinovaWebsiteAuditLog extends Model
{
    public $timestamps = false;

    protected $table = 'sinova_website_audit_logs';

    protected $fillable = [
        'user_id',
        'user_name',
        'action',
        'page_slug',
        'year',
        'sub_group',
        'period_key',
        'source_type',
        'doc_url',
        'sync_ok',
        'message',
        'created_at',
    ];

    protected $casts = [
        'year'       => 'integer',
        'sync_ok'    => 'boolean',
        'created_at' => 'datetime',
    ];
}
