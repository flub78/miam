<?php

namespace App\Services;

use App\Enums\Sex;
use App\Exceptions\DonneesInsuffisantes;
use App\Models\Profile;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Budget calorique quotidien initial (FR-6) et date cible indicative (FR-10).
 *
 * Métabolisme de base de Mifflin-St Jeor × facteur d'activité du profil, moins
 * le déficit qui correspond au rythme de perte visé. C'est une estimation de
 * départ, à ajuster sur les données réelles (FR-7).
 */
final class CalorieBudget
{
    /**
     * Énergie d'un kilogramme de masse grasse, en kcal.
     */
    public const KCAL_PAR_KG = 7700;

    /**
     * Budget proposé minimal, en kcal par jour, selon le sexe : en dessous,
     * les apports deviennent difficiles à couvrir sans suivi médical.
     */
    public const PLANCHER_FEMME = 1200;

    public const PLANCHER_HOMME = 1500;

    /**
     * Rythme de perte proposé quand l'utilisateur n'en a pas encore choisi.
     */
    public const RYTHME_PAR_DEFAUT = 0.5;

    /**
     * @param  float  $poidsKg  Poids actuel.
     * @param  float  $rythmeKgParSemaine  Perte visée (0 = maintien).
     *
     * @throws DonneesInsuffisantes si le profil ne permet pas le calcul.
     */
    public static function calculer(?Profile $profil, float $poidsKg, float $rythmeKgParSemaine, ?CarbonInterface $aujourdhui = null): DetailBudget
    {
        $aujourdhui ??= CarbonImmutable::today();
        self::verifierProfil($profil);

        /** @var Profile $profil */
        $age = (int) $profil->birthdate->diffInYears($aujourdhui);
        $constanteSexe = $profil->sex === Sex::Homme ? 5 : -161;
        $metabolisme = 10 * $poidsKg + 6.25 * $profil->height_cm - 5 * $age + $constanteSexe;

        $maintenance = $metabolisme * $profil->activity_level->facteur();
        $deficit = max(0.0, $rythmeKgParSemaine) * self::KCAL_PAR_KG / 7;

        $plancher = $profil->sex === Sex::Homme ? self::PLANCHER_HOMME : self::PLANCHER_FEMME;
        $budget = self::arrondirA10($maintenance - $deficit);

        return new DetailBudget(
            metabolismeDeBase: (int) round($metabolisme),
            maintenance: (int) round($maintenance),
            deficit: (int) round($deficit),
            budget: max($budget, $plancher),
            plancherApplique: $budget < $plancher,
        );
    }

    /**
     * Date à laquelle le poids cible serait atteint au rythme visé, `null` si le
     * rythme est nul ou la cible déjà atteinte. Indicative, non contractuelle.
     */
    public static function dateCibleIndicative(float $poidsKg, float $poidsCibleKg, float $rythmeKgParSemaine, ?CarbonInterface $aujourdhui = null): ?CarbonImmutable
    {
        if ($rythmeKgParSemaine <= 0 || $poidsCibleKg >= $poidsKg) {
            return null;
        }

        $jours = (int) ceil(($poidsKg - $poidsCibleKg) / $rythmeKgParSemaine * 7);

        return CarbonImmutable::instance($aujourdhui ?? CarbonImmutable::today())->startOfDay()->addDays($jours);
    }

    /**
     * @throws DonneesInsuffisantes
     */
    private static function verifierProfil(?Profile $profil): void
    {
        $manquants = [];

        if ($profil?->birthdate === null) {
            $manquants[] = 'date de naissance';
        }

        if (($profil->height_cm ?? 0) <= 0) {
            $manquants[] = 'taille';
        }

        if ($profil?->sex === null) {
            $manquants[] = 'sexe';
        }

        if ($profil?->activity_level === null) {
            $manquants[] = 'niveau d\'activité physique';
        }

        if ($manquants !== []) {
            throw new DonneesInsuffisantes(sprintf(
                'Le budget calorique ne peut pas être calculé : renseignez dans « Mon profil » votre %s.',
                implode(', ', $manquants),
            ));
        }
    }

    private static function arrondirA10(float $kcal): int
    {
        return (int) (round($kcal / 10) * 10);
    }
}
