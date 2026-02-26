<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Quotation extends Model
{
    /** @use HasFactory<\Database\Factories\QuotationFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'quotation_number',
        'quotation_date',
        'valid_until',
        'status',
        'subtotal',
        'tax',
        'discount',
        'total',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quotation_date' => 'date',
            'valid_until' => 'date',
            'subtotal' => 'decimal:2',
            'tax' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['quotation_number', 'status', 'total', 'valid_until', 'customer.name'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Quotation {$eventName}");
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }
}
