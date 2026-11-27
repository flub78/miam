<?php

namespace App\Enums;

/**
 * Niveau d'activité physique déclaré dans le profil (PRD §9).
 *
 * Barème Harris-Benedict classique à 5 niveaux (option retenue par défaut au
 * plan, Lot 1). Le profil stocke le niveau ; le facteur multiplie le
 * métabolisme de base dans le calcul du budget calorique (Lot 2).
 */
enum ActivityLevel: string
{
    case Sedentaire = 'sedentaire';
    case Leger = 'leger';
    case Modere = 'modere';
    case Soutenu = 'soutenu';
    case Intense = 'intense';

    public function libelle(): string
    {
        return match ($this) {
            self::Sedentaire => 'Sédentaire',
            self::Leger => 'Léger',
            self::Modere => 'Modéré',
            self::Soutenu => 'Soutenu',
            self::Intense => 'Intense',
        };
    }

    /**
     * Exemple d'activité correspondante, affiché pour aider le choix.
     */
    public function description(): string
    {
        return match ($this) {
            self::Sedentaire => 'peu ou pas d\'exercice, travail assis',
            self::Leger => 'exercice léger 1 à 3 jours par semaine',
            self::Modere => 'exercice modéré 3 à 5 jours par semaine',
            self::Soutenu => 'exercice soutenu 6 à 7 jours par semaine',
            self::Intense => 'exercice très intense ou travail physique quotidien',
        };
    }

    /**
     * Facteur multiplicateur du métabolisme de base.
     */
    public function facteur(): float
    {
        return match ($this) {
            self::Sedentaire => 1.2,
            self::Leger => 1.375,
            self::Modere => 1.55,
            self::Soutenu => 1.725,
            self::Intense => 1.9,
        };
    }

    /**
     * Options d'un sélecteur : valeur => « Libellé — description ».
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $niveau) {
            $options[$niveau->value] = "{$niveau->libelle()} — {$niveau->description()}";
        }

        return $options;
    }
}
