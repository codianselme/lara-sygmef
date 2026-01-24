<?php

declare(strict_types=1);

namespace Codianselme\LaraSygmef\Enums;

/**
 * Enumération des groupes de taxation e-MECeF.
 */
enum TaxGroup: string
{
    case A = 'A';
    case B = 'B';
    case C = 'C';
    case D = 'D';
    case E = 'E';
    case F = 'F';

    /**
     * Récupère le taux de taxe par défaut pour le groupe.
     *
     * @return int
     */
    public function defaultRate(): int
    {
        return match ($this) {
            self::A => 0,
            self::B => 18,
            self::C => 0,
            self::D => 18,
            self::E => 0,
            self::F => 0,
        };
    }
}
