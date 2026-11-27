<?php

namespace App\Services;

use App\Models\SuspensionPeriod;
use App\Models\WeighIn;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Tendance du poids : poids journaliers, moyenne mobile 7 jours (FR-13) et
 * pente en kg par semaine (FR-14).
 *
 * Les pesées d'un jour de suspension sont conservées et restituées par
 * `poidsJournaliers()`, mais exclues de la moyenne mobile et de la pente
 * (FR-61) : le poids d'un voyage ne fausse pas la tendance.
 */
final class WeightTrend
{
    /**
     * Fenêtre de la moyenne mobile, en jours (le jour courant inclus).
     */
    public const FENETRE_JOURS = 7;

    /**
     * @var array<string, float> date Y-m-d => moyenne des pesées du jour
     */
    private array $poidsParJour;

    /**
     * @param  Collection<int, WeighIn>  $pesees
     * @param  Collection<int, SuspensionPeriod>  $suspensions
     */
    public function __construct(Collection $pesees, private Collection $suspensions)
    {
        $this->poidsParJour = $pesees
            ->groupBy(fn (WeighIn $pesee): string => $pesee->measured_on->toDateString())
            ->map(fn (Collection $duJour): float => round((float) $duJour->avg(fn (WeighIn $pesee): float => (float) $pesee->weight_kg), 2))
            ->sortKeys()
            ->all();
    }

    /**
     * Tendance de l'utilisateur connecté (le global scope des modèles restreint
     * les requêtes à ses lignes).
     */
    public static function utilisateurCourant(): self
    {
        return new self(WeighIn::query()->get(), SuspensionPeriod::query()->get());
    }

    /**
     * @return array<string, float> date Y-m-d => poids moyen du jour, tous jours confondus
     */
    public function poidsJournaliers(): array
    {
        return $this->poidsParJour;
    }

    public function premierJour(): ?CarbonImmutable
    {
        $premier = array_key_first($this->poidsParJour);

        return $premier === null ? null : CarbonImmutable::parse($premier);
    }

    /**
     * Poids du dernier jour pesé, `null` sans pesée.
     */
    public function dernierPoids(): ?float
    {
        return $this->poidsParJour === [] ? null : end($this->poidsParJour);
    }

    public function estSuspendu(CarbonInterface $jour): bool
    {
        return $this->suspensions->contains(fn (SuspensionPeriod $periode): bool => $periode->couvre($jour));
    }

    /**
     * Moyenne des jours pesés hors suspension sur les 7 jours finissant à
     * `$jour`, `null` si aucun.
     */
    public function moyenneMobile(CarbonInterface $jour): ?float
    {
        $valeurs = [];
        $fin = CarbonImmutable::instance($jour)->startOfDay();

        for ($decalage = 0; $decalage < self::FENETRE_JOURS; $decalage++) {
            $date = $fin->subDays($decalage);
            $poids = $this->poidsParJour[$date->toDateString()] ?? null;

            if ($poids !== null && ! $this->estSuspendu($date)) {
                $valeurs[] = $poids;
            }
        }

        return $valeurs === [] ? null : round(array_sum($valeurs) / count($valeurs), 2);
    }

    /**
     * Pente de la moyenne mobile, en kg par semaine (négative = perte), par
     * régression linéaire sur les jours pesés hors suspension de la période.
     * `null` s'il y a moins de deux jours exploitables.
     */
    public function pente(CarbonInterface $debut, CarbonInterface $fin): ?float
    {
        $origine = CarbonImmutable::instance($debut)->startOfDay();
        $points = [];

        foreach (array_keys($this->poidsParJour) as $date) {
            $jour = CarbonImmutable::parse($date);

            if ($date < $origine->toDateString() || $date > $fin->toDateString() || $this->estSuspendu($jour)) {
                continue;
            }

            $points[] = [(float) $origine->diffInDays($jour), (float) $this->moyenneMobile($jour)];
        }

        if (count($points) < 2) {
            return null;
        }

        $n = count($points);
        $moyenneX = array_sum(array_column($points, 0)) / $n;
        $moyenneY = array_sum(array_column($points, 1)) / $n;
        $covariance = 0.0;
        $variance = 0.0;

        foreach ($points as [$x, $y]) {
            $covariance += ($x - $moyenneX) * ($y - $moyenneY);
            $variance += ($x - $moyenneX) ** 2;
        }

        return round($covariance / $variance * 7, 2);
    }
}
