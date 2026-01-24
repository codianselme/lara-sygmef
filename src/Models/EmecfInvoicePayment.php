<?php

namespace Codianselme\LaraSygmef\Models;

use Codianselme\LaraSygmef\Enums\PaymentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle représentant un paiement d'une facture e-MECeF.
 *
 * @property int $id
 * @property int $emecf_invoice_id
 * @property PaymentType $name
 * @property int $amount
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \Codianselme\LaraSygmef\Models\EmecfInvoice $invoice
 * @property-read string $payment_type_label
 * @property-read string $formatted_amount
 */
class EmecfInvoicePayment extends Model
{
    use HasFactory;

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'emecf_invoice_id',
        'name',
        'amount',
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'integer',
        'name' => PaymentType::class,
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
     * Obtenir le libellé du type de paiement.
     *
     * @return string
     */
    public function getPaymentTypeLabelAttribute(): string
    {
        return $this->name->label();
    }

    /**
     * Formater le montant.
     *
     * @return string
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 0, ',', ' ') . ' FCFA';
    }
}
