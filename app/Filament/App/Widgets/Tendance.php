<?php

namespace App\Filament\App\Widgets;

use App\Models\Goal;
use App\Models\Profile;
use App\Services\Bmi;
use App\Services\WeightTrend;
use Carbon\CarbonImmutable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Laraskel\Charts\Widgets\Graphique;
use Livewire\Attributes\On;

/**
 * Chiffres clés de la tendance (CU-2) : moyenne mobile 7 jours, pente sur
 * 3 semaines comparée au rythme visé, IMC et distance au poids cible.
 */
class Tendance extends StatsOverviewWidget
{
    /**
     * Fenêtre de la pente affichée, en jours (CU-2 : « pente estimée sur 3 semaines »).
     */
    public const JOURS_PENTE = 21;

    protected static ?int $sort = 1;

    protected ?string $heading = 'Tendance';

    protected ?string $pollingInterval = null;

    /**
     * Recalcule après l'enregistrement d'une pesée sur la même page (FR-32).
     */
    #[On(Graphique::EVENEMENT_RAFRAICHIR)]
    public function rafraichir(): void {}

    /**
     * @return list<Stat>
     */
    protected function getStats(): array
    {
        $tendance = WeightTrend::utilisateurCourant();
        $aujourdhui = CarbonImmutable::today();
        $moyenne = $tendance->moyenneMobile($aujourdhui);
        $objectif = Goal::courant();

        if ($tendance->dernierPoids() === null) {
            return [Stat::make('Tendance', '—')->description('Saisissez une pesée dans « Pesées » pour suivre votre tendance.')];
        }

        $stats = [
            Stat::make('Moyenne 7 jours', $moyenne === null ? '—' : $this->kg($moyenne))
                ->description($moyenne === null ? 'Aucune pesée hors suspension sur les 7 derniers jours' : 'Dernière pesée : '.$this->kg((float) $tendance->dernierPoids())),
            $this->statPente($tendance->pente($aujourdhui->subDays(self::JOURS_PENTE - 1), $aujourdhui), $objectif),
        ];

        $taille = Profile::query()->value('height_cm');
        $poidsReference = $moyenne ?? (float) $tendance->dernierPoids();

        if ($taille !== null) {
            $imc = Bmi::calculer($poidsReference, (int) $taille);
            $stats[] = Stat::make('IMC', $this->nombre($imc, 1))->description(Bmi::zone($imc)->libelle());
        }

        if ($objectif !== null) {
            $ecart = $poidsReference - (float) $objectif->target_weight_kg;
            $stats[] = Stat::make('Poids cible', $this->kg((float) $objectif->target_weight_kg))
                ->description($ecart > 0 ? 'Encore '.$this->kg($ecart).' à perdre' : 'Atteint');
        }

        return $stats;
    }

    private function statPente(?float $pente, ?Goal $objectif): Stat
    {
        if ($pente === null) {
            return Stat::make('Pente sur 3 semaines', '—')
                ->description('Il faut au moins deux jours de pesée hors suspension sur les 3 dernières semaines.');
        }

        $stat = Stat::make('Pente sur 3 semaines', $this->nombre($pente, 2, signe: true).' kg/sem.');

        if ($objectif === null) {
            return $stat->description('Fixez un objectif pour comparer au rythme visé.');
        }

        $vise = -(float) $objectif->target_rate_kg_per_week;

        return $stat
            ->description('Rythme visé : '.$this->nombre($vise, 2, signe: true).' kg/sem.')
            ->color($pente <= $vise ? 'success' : 'warning');
    }

    private function kg(float $valeur): string
    {
        return $this->nombre($valeur, 1).' kg';
    }

    private function nombre(float $valeur, int $decimales, bool $signe = false): string
    {
        $texte = number_format($valeur, $decimales, ',', ' ');

        return $signe && $valeur > 0 ? '+'.$texte : $texte;
    }
}
