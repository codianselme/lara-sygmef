<?php

declare(strict_types=1);

namespace Codianselme\LaraSygmef\Enums;

/**
 * Enumération des types de paiement e-MECeF.
 */
enum PaymentType: string
{
    case ESPECES = 'ESPECES';
    case VIREMENT = 'VIREMENT';
    case CARTEBANCAIRE = 'CARTEBANCAIRE';
    case MOBILEMONEY = 'MOBILEMONEY';
    case CHEQUES = 'CHEQUES';
    case CREDIT = 'CREDIT';
    case AUTRE = 'AUTRE';

    /**
     * Récupère le libellé du type de paiement.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::ESPECES => 'ESPECES',
            self::VIREMENT => 'VIREMENT',
            self::CARTEBANCAIRE => 'CARTE BANCAIRE',
            self::MOBILEMONEY => 'MOBILE MONEY',
            self::CHEQUES => 'CHEQUES',
            self::CREDIT => 'CREDIT',
            self::AUTRE => 'AUTRE',
        };
    }
}
