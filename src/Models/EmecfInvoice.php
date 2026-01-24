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
 * @property int $ta
 * @property int $tb
 * @property int $tc
 * @property int $td
 * @property int $taa
 * @property int $tab
 * @property int $tac
 * @property int $tad
 * @property int $tae
 * @property int $taf
 * @property int $hab
 * @property int $had
 * @property int $vab
 * @property int $vad
 * @property int $aib_amount
 * @property int $ts
 * @property int $total
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
        'ta' => 'integer',
        'tb' => 'integer',
        'tc' => 'integer',
        'td' => 'integer',
        'taa' => 'integer',
        'tab' => 'integer',
        'tac' => 'integer',
        'tad' => 'integer',
        'tae' => 'integer',
        'taf' => 'integer',
        'hab' => 'integer',
        'had' => 'integer',
        'vab' => 'integer',
        'vad' => 'integer',
        'aib_amount' => 'integer',
        'ts' => 'integer',
        'total' => 'integer',
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
