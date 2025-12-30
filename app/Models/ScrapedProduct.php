<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScrapedProduct extends Model
{
    /** @use HasFactory<\Database\Factories\ScrapedProductFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'source_url',
        'vendor_domain',
        'title',
        'sku',
        'nsn',
        'cage_code',
        'milspec',
        'price',
        'price_numeric',
        'html_cache_path',
        'raw_data',
        'status',
        'product_id',
        'imported_by',
        'imported_at',
        'import_notes',
    ];

    protected function casts(): array
    {
        return [
            'price_numeric' => 'decimal:2',
            'raw_data' => 'array',
            'imported_at' => 'datetime',
        ];
    }

    /**
     * Get the product that was created from this scraped data.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the admin who imported this product.
     */
    public function importer(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'imported_by');
    }

    /**
     * Scope to get pending (not yet imported) products.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get imported products.
     */
    public function scopeImported($query)
    {
        return $query->where('status', 'imported');
    }

    /**
     * Scope to get failed products.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope to get products from a specific vendor domain.
     */
    public function scopeFromVendor($query, string $domain)
    {
        return $query->where('vendor_domain', $domain);
    }

    /**
     * Mark this scraped product as imported.
     */
    public function markAsImported(Product $product, ?Admin $admin = null, ?string $notes = null): void
    {
        $this->update([
            'status' => 'imported',
            'product_id' => $product->id,
            'imported_by' => $admin?->id,
            'imported_at' => now(),
            'import_notes' => $notes,
        ]);
    }

    /**
     * Mark this scraped product as failed.
     */
    public function markAsFailed(?string $reason = null): void
    {
        $this->update([
            'status' => 'failed',
            'import_notes' => $reason,
        ]);
    }

    /**
     * Mark this scraped product as ignored.
     */
    public function markAsIgnored(?string $reason = null): void
    {
        $this->update([
            'status' => 'ignored',
            'import_notes' => $reason,
        ]);
    }

    /**
     * Check if this scraped product is pending import.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if this scraped product has been imported.
     */
    public function isImported(): bool
    {
        return $this->status === 'imported';
    }

    /**
     * Check if this scraped product failed to import.
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if this scraped product was ignored.
     */
    public function isIgnored(): bool
    {
        return $this->status === 'ignored';
    }

    /**
     * Get the vendor domain from the source URL.
     */
    public static function extractVendorDomain(string $url): string
    {
        $parsed = parse_url($url);

        return $parsed['host'] ?? 'unknown';
    }

    /**
     * Create a scraped product from tool result.
     */
    public static function createFromToolResult(string $url, array $result): self
    {
        $productData = $result['product'] ?? [];

        return self::create([
            'source_url' => $url,
            'vendor_domain' => self::extractVendorDomain($url),
            'title' => $productData['title'] ?? null,
            'sku' => $productData['sku'] ?? null,
            'nsn' => $productData['nsn'] ?? null,
            'cage_code' => $productData['cage_code'] ?? null,
            'milspec' => $productData['milspec'] ?? null,
            'price' => $productData['price'] ?? null,
            'price_numeric' => $productData['price_numeric'] ?? null,
            'html_cache_path' => $result['html_cache'] ?? null,
            'raw_data' => $result,
            'status' => 'pending',
        ]);
    }
}
