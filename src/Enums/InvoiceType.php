<?php

declare(strict_types=1);

namespace Codianselme\LaraSygmef\Enums;

/**
 * Enumération des types de factures e-MECeF.
 */
enum InvoiceType: string
{
    case FV = 'FV'; // Facture de vente
    case EV = 'EV'; // Facture de vente à l'exportation
    case FA = 'FA'; // Facture d'avoir
    case EA = 'EA'; // Facture d'avoir à l'exportation

    /**
     * Récupère le libellé du type de facture.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::FV => 'Facture de vente',
            self::EV => 'Facture de vente à l\'exportation',
            self::FA => 'Facture d\'avoir',
            self::EA => 'Facture d\'avoir à l\'exportation',
        };
    }
}
