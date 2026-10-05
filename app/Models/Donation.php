<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Donation extends Model
{
    use HasFactory;

    protected $fillable = [
        'donor_id',
        'category_id',
        'title',
        'description',
        'quantity_total',
        'quantity_reserved',
        'quantity_claimed',
        'unit',
        'condition_type',
        'expiration_date',
        'pickup_method',
        'pickup_address',
        'pickup_barangay',
        'pickup_city',
        'pickup_province',
        'pickup_notes',
        'pickup_schedule',
        'donor_name',
        'contact_number',
        'status',
        'admin_notes',
        'rejection_reason',
        'info_request',
        'submitted_at',
        'approved_at',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity_total' => 'integer',
            'quantity_reserved' => 'integer',
            'quantity_claimed' => 'integer',
            'expiration_date' => 'date',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function getQuantityAvailableAttribute(): int
    {
        $avail = $this->quantity_total - ($this->quantity_reserved + $this->quantity_claimed);
        return max(0, $avail);
    }

    public function donor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'donor_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(DonationImage::class);
    }

    public function primaryImage()
    {
        return $this->hasOne(DonationImage::class)->where('is_primary', true);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(ResourceRequest::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(ResourceMatch::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
