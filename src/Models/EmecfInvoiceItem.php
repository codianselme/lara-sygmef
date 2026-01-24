<?php

namespace Codianselme\LaraSygmef\Models;

use Codianselme\LaraSygmef\Enums\TaxGroup;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle représentant un article d'une facture e-MECeF.
 *
 * @property int $id
 * @property int $emecf_invoice_id
 * @property string|null $code
 * @property string $name
 * @property int $price
 * @property float $quantity
 * @property TaxGroup $tax_group
 * @property int|null $tax_specific
 * @property int|null $original_price
 * @property string|null $price_modification
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \Codianselme\LaraSygmef\Models\EmecfInvoice $invoice
 * @property-read int $total
 * @property-read float|null $discount_percentage
 */
class EmecfInvoiceItem extends Model
{
    use HasFactory;

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array<int, string>
     */
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

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'integer',
        'quantity' => 'decimal:3',
        'tax_specific' => 'integer',
        'original_price' => 'integer',
        'tax_group' => TaxGroup::class,
    ];

    /**
     * Relation avec la facture.
     *
     * @return BelongsTo
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(EmecfInvoice::class, 'emecf_invoice_id');
    }

    /**
     * Calculer le montant total pour cet article.
     *
     * @return int
     */
    public function getTotalAttribute(): int
    {
        return intval($this->price * $this->quantity);
    }

    /**
     * Vérifier si l'article a une modification de prix.
     *
     * @return bool
     */
    public function hasPriceModification(): bool
    {
        return !is_null($this->original_price) && $this->original_price !== $this->price;
    }

    /**
     * Obtenir le pourcentage de réduction.
     *
     * @return float|null
     */
    public function getDiscountPercentageAttribute(): ?float
    {
        if (!$this->hasPriceModification() || $this->original_price === 0) {
            return null;
        }

        return round((($this->original_price - $this->price) / $this->original_price) * 100, 2);
    }
}
