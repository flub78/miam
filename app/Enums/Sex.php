<?php

namespace App\Enums;

/**
 * Sexe déclaré dans le profil, utilisé par la formule de Mifflin-St Jeor (Lot 2).
 */
enum Sex: string
{
    case Femme = 'femme';
    case Homme = 'homme';

    public function libelle(): string
    {
        return match ($this) {
            self::Femme => 'Femme',
            self::Homme => 'Homme',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_combine(
            array_map(fn (self $sexe): string => $sexe->value, self::cases()),
            array_map(fn (self $sexe): string => $sexe->libelle(), self::cases()),
        );
    }
}
