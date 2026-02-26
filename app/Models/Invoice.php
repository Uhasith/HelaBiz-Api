<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Invoice extends Model implements HasMedia
{
    /** @use HasFactory<\Database\Factories\InvoiceFactory> */
    use HasFactory, InteractsWithMedia, LogsActivity;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'order_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'status',
        'subtotal',
        'tax',
        'discount',
        'total',
        'notes',
    ];

    protected $appends = ['pdf_url'];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['invoice_number', 'status', 'total', 'due_date', 'customer.name'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Invoice {$eventName}");
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function getPdfUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('invoice_pdf') ?: null;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('invoice_pdf')
            ->singleFile();
    }
}
