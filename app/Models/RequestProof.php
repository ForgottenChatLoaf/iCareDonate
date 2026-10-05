<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestProof extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'description',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(ResourceRequest::class, 'request_id');
    }

    public function getUrlAttribute(): string
    {
        if (str_starts_with($this->file_path, 'http')) {
            return $this->file_path;
        }
        return asset('storage/' . $this->file_path);
    }
}
