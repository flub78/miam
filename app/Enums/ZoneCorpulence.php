<?php

namespace App\Enums;

/**
 * Zones de corpulence de l'OMS selon l'IMC (FR-29). Bornes basses incluses :
 * un IMC de 25,0 est en surpoids.
 */
enum ZoneCorpulence: string
{
    case Maigreur = 'maigreur';
    case Normale = 'normale';
    case Surpoids = 'surpoids';
    case ObesiteModeree = 'obesite_moderee';
    case ObesiteSevere = 'obesite_severe';
    case ObesiteMorbide = 'obesite_morbide';

    public function libelle(): string
    {
        return match ($this) {
            self::Maigreur => 'Maigreur',
            self::Normale => 'Corpulence normale',
            self::Surpoids => 'Surpoids',
            self::ObesiteModeree => 'Obésité modérée',
            self::ObesiteSevere => 'Obésité sévère',
            self::ObesiteMorbide => 'Obésité morbide',
        };
    }

    /**
     * Borne basse incluse, `null` pour la première zone.
     */
    public function minimum(): ?float
    {
        return match ($this) {
            self::Maigreur => null,
            self::Normale => 18.5,
            self::Surpoids => 25.0,
            self::ObesiteModeree => 30.0,
            self::ObesiteSevere => 35.0,
            self::ObesiteMorbide => 40.0,
        };
    }

    /**
     * Borne haute exclue, `null` pour la dernière zone.
     */
    public function maximum(): ?float
    {
        return match ($this) {
            self::Maigreur => 18.5,
            self::Normale => 25.0,
            self::Surpoids => 30.0,
            self::ObesiteModeree => 35.0,
            self::ObesiteSevere => 40.0,
            self::ObesiteMorbide => null,
        };
    }

    /**
     * Couleur de la bande dans la courbe d'IMC (palette Okabe-Ito de la brique).
     */
    public function couleur(): string
    {
        return match ($this) {
            self::Maigreur => '#56B4E9',
            self::Normale => '#009E73',
            self::Surpoids => '#F0E442',
            self::ObesiteModeree => '#E69F00',
            self::ObesiteSevere => '#D55E00',
            self::ObesiteMorbide => '#CC79A7',
        };
    }

    public static function pour(float $imc): self
    {
        foreach (self::cases() as $zone) {
            if ($zone->maximum() === null || $imc < $zone->maximum()) {
                return $zone;
            }
        }

        return self::ObesiteMorbide;
    }
}
