<?php

namespace Codianselme\LaraSygmef\Models;

use Codianselme\LaraSygmef\Enums\InvoiceStatus;
use Codianselme\LaraSygmef\Enums\InvoiceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modèle représentant une facture e-MECeF.
 *
 * @property int $id
 * @property string $uid
 * @property string $ifu
 * @property string|null $aib
 * @property InvoiceType $type
 * @property string|null $reference
 * @property string|null $operator_id
 * @property string $operator_name
 * @property string|null $client_ifu
 * @property string|null $client_name
 * @property string|null $client_contact
 * @property string|null $client_address
 * @property float $ta
 * @property float $tb
 * @property float $tc
 * @property float $td
 * @property float $taa
 * @property float $tab
 * @property float $tac
 * @property float $tad
 * @property float $tae
 * @property float $taf
 * @property float $hab
 * @property float $had
 * @property float $vab
 * @property float $vad
 * @property float $aib_amount
 * @property float $ts
 * @property float $total
 * @property InvoiceStatus $status
 * @property string|null $code_mec_ef_dgi
 * @property string|null $qr_code
 * @property string|null $date_time
 * @property string|null $counters
 * @property string|null $nim
 * @property string|null $error_code
 * @property string|null $error_desc
 * @property \Illuminate\Support\Carbon|null $submitted_at
 * @property \Illuminate\Support\Carbon|null $finalized_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \Illuminate\Database\Eloquent\Collection|\Codianselme\LaraSygmef\Models\EmecfInvoiceItem[] $items
 * @property-read \Illuminate\Database\Eloquent\Collection|\Codianselme\LaraSygmef\Models\EmecfInvoicePayment[] $payments
 */
class EmecfInvoice extends Model
{
    use HasFactory;

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uid',
        'ifu',
        'aib',
        'type',
        'reference',
        'operator_id',
        'operator_name',
        'client_ifu',
        'client_name',
        'client_contact',
        'client_address',
        'ta',
        'tb',
        'tc',
        'td',
        'taa',
        'tab',
        'tac',
        'tad',
        'tae',
        'taf',
        'hab',
        'had',
        'vab',
        'vad',
        'aib_amount',
        'ts',
        'total',
        'status',
        'code_mec_ef_dgi',
        'qr_code',
        'date_time',
        'counters',
        'nim',
        'error_code',
        'error_desc',
        'submitted_at',
        'finalized_at',
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'submitted_at' => 'datetime',
        'finalized_at' => 'datetime',
        'ta' => 'decimal:2',
        'tb' => 'decimal:2',
        'tc' => 'decimal:2',
        'td' => 'decimal:2',
        'taa' => 'decimal:2',
        'tab' => 'decimal:2',
        'tac' => 'decimal:2',
        'tad' => 'decimal:2',
        'tae' => 'decimal:2',
        'taf' => 'decimal:2',
        'hab' => 'decimal:2',
        'had' => 'decimal:2',
        'vab' => 'decimal:2',
        'vad' => 'decimal:2',
        'aib_amount' => 'decimal:2',
        'ts' => 'decimal:2',
        'total' => 'decimal:2',
        'status' => InvoiceStatus::class,
        'type' => InvoiceType::class,
    ];

    /**
     * Relation avec les articles de la facture.
     *
     * @return HasMany
     */
    public function items(): HasMany
    {
        return $this->hasMany(EmecfInvoiceItem::class);
    }

    /**
     * Relation avec les paiements de la facture.
     *
     * @return HasMany
     */
    public function payments(): HasMany
    {
        return $this->hasMany(EmecfInvoicePayment::class);
    }
}
