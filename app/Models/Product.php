<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Product extends Model implements HasMedia
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;
    use InteractsWithMedia;

    protected $fillable = [
        'tenant_id',
        'name',
        'sku',
        'barcode',
        'description',
        'cost_price',
        'selling_price',
        'stock_quantity',
        'low_stock_alert',
    ];

    protected $appends = [
        'photo_url',
    ];

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'stock_quantity' => 'integer',
            'low_stock_alert' => 'integer',
        ];
    }

    public function getPhotoUrlAttribute(): string
    {
        return $this->getFirstMediaUrl('product_image');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('product_image')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png']);
    }

    public function isLowStock(): bool
    {
        return $this->stock_quantity <= ($this->low_stock_alert ?? 0);
    }
}
