<?php

namespace Codianselme\LaraSygmef\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmecfInvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'emecf_invoice_id',
        'code',
        'name',
        'price',
        'quantity',
        'tax_group',
        'tax_specific',
        'original_price',
        'price_modification',
    ];

    protected $casts = [
        'price' => 'integer',
        'quantity' => 'decimal:3',
        'tax_specific' => 'integer',
        'original_price' => 'integer',
    ];

    /**
     * Groupes de taxation disponibles
     */
    public const TAX_GROUPS = ['A', 'B', 'C', 'D', 'E', 'F'];

    /**
     * Relation avec la facture
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(EmecfInvoice::class, 'emecf_invoice_id');
    }

    /**
     * Calculer le montant total pour cet article
     */
    public function getTotalAttribute(): int
    {
        return intval($this->price * $this->quantity);
    }

    /**
     * Obtenir le libellé du groupe de taxation
     */
    public function getTaxGroupLabelAttribute(): string
    {
        return $this->tax_group;
    }

    /**
     * Vérifier si l'article a une modification de prix
     */
    public function hasPriceModification(): bool
    {
        return !is_null($this->original_price) && $this->original_price !== $this->price;
    }

    /**
     * Obtenir le pourcentage de réduction
     */
    public function getDiscountPercentageAttribute(): ?float
    {
        if (!$this->hasPriceModification()) {
            return null;
        }

        return round((($this->original_price - $this->price) / $this->original_price) * 100, 2);
    }
}
