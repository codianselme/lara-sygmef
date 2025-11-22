<?php

namespace Codianselme\LaraSygmef\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmecfInvoicePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'emecf_invoice_id',
        'name',
        'amount',
    ];

    protected $casts = [
        'amount' => 'integer',
    ];

    /**
     * Types de paiement disponibles
     */
    public const PAYMENT_TYPES = [
        'ESPECES' => 'ESPECES',
        'VIREMENT' => 'VIREMENT',
        'CARTEBANCAIRE' => 'CARTE BANCAIRE',
        'MOBILEMONEY' => 'MOBILE MONEY',
        'CHEQUES' => 'CHEQUES',
        'CREDIT' => 'CREDIT',
        'AUTRE' => 'AUTRE'
    ];

    /**
     * Relation avec la facture
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(EmecfInvoice::class, 'emecf_invoice_id');
    }

    /**
     * Obtenir le libellé du type de paiement
     */
    public function getPaymentTypeLabelAttribute(): string
    {
        return self::PAYMENT_TYPES[$this->name] ?? $this->name;
    }

    /**
     * Formater le montant
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 0, ',', ' ') . ' FCFA';
    }
}
