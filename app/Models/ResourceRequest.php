<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResourceRequest extends Model
{
    use HasFactory;

    protected $table = 'requests';

    protected $fillable = [
        'requester_id',
        'donation_id',
        'category_id',
        'item_name',
        'quantity_requested',
        'quantity_matched',
        'purpose',
        'urgency_level',
        'status',
        'contact_number',
        'delivery_address',
        'interview_required',
        'interview_reason',
        'admin_notes',
        'rejection_reason',
        'info_request',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity_requested' => 'integer',
            'quantity_matched' => 'integer',
            'interview_required' => 'boolean',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function donation(): BelongsTo
    {
        return $this->belongsTo(Donation::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function proofs(): HasMany
    {
        return $this->hasMany(RequestProof::class, 'request_id');
    }

    public function matches(): HasMany
    {
        return $this->hasMany(ResourceMatch::class, 'request_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'request_id');
    }

    public function interview()
    {
        return $this->hasOne(Interview::class, 'request_id');
    }
}
