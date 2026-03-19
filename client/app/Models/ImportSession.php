<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ImportSession extends Model
{
    protected $fillable = [
        'uuid',
        'user_id',
        'disk',
        'path',
        'status',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            $model->uuid ??= Str::uuid();
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}