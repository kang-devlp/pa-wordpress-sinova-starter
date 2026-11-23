<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SinovaWebsiteDocument extends Model
{
    protected $table = 'sinova_website_documents';

    protected $fillable = [
        'page_slug',
        'year',
        'sub_group',
        'period_key',
        'period_order',
        'title',
        'source_type',
        'doc_url',
        'file_size_kb',
        'sync_status',
        'last_synced_at',
        'last_sync_error',
        'link_http_status',
        'link_health_note',
        'link_checked_at',
        'updated_by_user_id',
        'updated_by_name',
    ];

    protected $casts = [
        'year'             => 'integer',
        'period_order'     => 'integer',
        'file_size_kb'     => 'integer',
        'link_http_status' => 'integer',
        'last_synced_at'   => 'datetime',
        'link_checked_at'  => 'datetime',
    ];
}
