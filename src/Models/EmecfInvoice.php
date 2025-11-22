<?php

namespace Codianselme\LaraSygmef\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmecfInvoice extends Model
{
    use HasFactory;

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
    ];

    /**
     * Statuts possibles pour une facture
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_ERROR = 'error';

    /**
     * Types de factures
     */
    public const TYPES = [
        'FV' => 'Facture de vente',
        'EV' => 'Facture de vente à l\'exportation',
        'FA' => 'Facture d\'avoir',
        'EA' => 'Facture d\'avoir à l\'exportation'
    ];

    /**
     * Relation avec les articles de la facture
     */
    public function items(): HasMany
    {
        return $this->hasMany(EmecfInvoiceItem::class);
    }

    /**
     * Relation avec les paiements de la facture
     */
    public function payments(): HasMany
    {
        return $this->hasMany(EmecfInvoicePayment::class);
    }

    /**
     * Vérifier si la facture est en attente
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Vérifier si la facture est confirmée
     */
    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    /**
     * Vérifier si la facture est annulée
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Vérifier si la facture a une erreur
     */
    public function hasError(): bool
    {
        return $this->status === self::STATUS_ERROR;
    }

    /**
     * Obtenir le libellé du type de facture
     */
    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /**
     * Obtenir le libellé du statut
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'En attente',
            self::STATUS_CONFIRMED => 'Confirmée',
            self::STATUS_CANCELLED => 'Annulée',
            self::STATUS_ERROR => 'Erreur',
            default => $this->status
        };
    }

    /**
     * Marquer la facture comme confirmée
     */
    public function markAsConfirmed(array $securityElements): void
    {
        $this->update([
            'status' => self::STATUS_CONFIRMED,
            'code_mec_ef_dgi' => $securityElements['codeMECeFDGI'] ?? null,
            'qr_code' => $securityElements['qrCode'] ?? null,
            'date_time' => $securityElements['dateTime'] ?? null,
            'counters' => $securityElements['counters'] ?? null,
            'nim' => $securityElements['nim'] ?? null,
            'finalized_at' => now(),
        ]);
    }

    /**
     * Marquer la facture comme annulée
     */
    public function markAsCancelled(): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'finalized_at' => now(),
        ]);
    }

    /**
     * Marquer la facture comme ayant une erreur
     */
    public function markAsError(string $errorCode, string $errorDesc): void
    {
        $this->update([
            'status' => self::STATUS_ERROR,
            'error_code' => $errorCode,
            'error_desc' => $errorDesc,
        ]);
    }

    /**
     * Scope pour les factures en attente
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope pour les factures confirmées
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    /**
     * Scope pour les factures annulées
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    /**
     * Scope pour les factures avec erreur
     */
    public function scopeWithError($query)
    {
        return $query->where('status', self::STATUS_ERROR);
    }
}
