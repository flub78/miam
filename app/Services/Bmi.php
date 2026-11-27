<?php

namespace App\Services;

use App\Enums\ZoneCorpulence;
use InvalidArgumentException;

/**
 * Indice de masse corporelle et zone de corpulence OMS (FR-29).
 */
final class Bmi
{
    /**
     * IMC (kg/m²) arrondi au dixième.
     */
    public static function calculer(float $poidsKg, int $tailleCm): float
    {
        if ($tailleCm <= 0) {
            throw new InvalidArgumentException("La taille doit être strictement positive pour calculer l'IMC (reçu : {$tailleCm} cm).");
        }

        return round($poidsKg / (($tailleCm / 100) ** 2), 1);
    }

    public static function zone(float $imc): ZoneCorpulence
    {
        return ZoneCorpulence::pour($imc);
    }
}
