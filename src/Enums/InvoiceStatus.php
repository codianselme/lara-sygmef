<?php

declare(strict_types=1);

namespace Codianselme\LaraSygmef\Enums;

/**
 * Enumération des statuts d'une facture e-MECeF.
 */
enum InvoiceStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case CANCELLED = 'cancelled';
    case ERROR = 'error';

    /**
     * Récupère le libellé du statut.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'En attente',
            self::CONFIRMED => 'Confirmée',
            self::CANCELLED => 'Annulée',
            self::ERROR => 'Erreur',
        };
    }
}
