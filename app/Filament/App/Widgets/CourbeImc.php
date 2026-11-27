<?php

namespace App\Filament\App\Widgets;

use App\Enums\ZoneCorpulence;
use App\Exceptions\DonneesInsuffisantes;
use App\Filament\App\Widgets\Concerns\JoursSuivis;
use App\Models\Goal;
use App\Models\Profile;
use App\Services\Bmi;
use App\Services\WeightTrend;
use Carbon\CarbonImmutable;
use Laraskel\Charts\Donnees\Bande;
use Laraskel\Charts\Donnees\JeuDeDonnees;
use Laraskel\Charts\Donnees\Serie;
use Laraskel\Charts\Palette;
use Laraskel\Charts\Periode;
use Laraskel\Charts\TypeGraphique;
use Laraskel\Charts\Widgets\Graphique;

/**
 * Courbe d'IMC (FR-29) sur les zones de corpulence de l'OMS, avec le repère de
 * l'IMC du poids cible.
 */
class CourbeImc extends Graphique
{
    use JoursSuivis;

    protected ?string $heading = 'IMC';

    protected ?string $description = 'Indice de masse corporelle sur les zones de corpulence de l\'OMS ; en pointillés, l\'IMC de votre poids cible.';

    protected bool $selecteurPeriode = true;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 3;

    protected ?string $emptyStateDescription = 'Saisissez votre première pesée dans « Pesées » pour voir apparaître la courbe.';

    protected function type(): TypeGraphique
    {
        return TypeGraphique::Courbe;
    }

    protected function jeuDeDonnees(?Periode $periode): JeuDeDonnees
    {
        $tendance = WeightTrend::utilisateurCourant();
        $jours = $this->joursAffiches($tendance, $periode);

        if (! $this->unJourPese($tendance, $jours)) {
            return JeuDeDonnees::vide();
        }

        $taille = Profile::query()->value('height_cm');

        if ($taille === null) {
            throw new DonneesInsuffisantes('Renseignez votre taille dans « Mon profil » pour afficher la courbe d\'IMC.');
        }

        $poids = $tendance->poidsJournaliers();
        $imc = array_map(
            fn (CarbonImmutable $jour): ?float => isset($poids[$jour->toDateString()]) ? Bmi::calculer($poids[$jour->toDateString()], (int) $taille) : null,
            $jours,
        );
        $series = [new Serie('IMC', $imc, couleursParPoint: $this->couleursDesPoints($tendance, $jours, Palette::couleur(0)), unite: 'kg/m²')];
        $valeurs = array_filter($imc, fn (?float $valeur): bool => $valeur !== null);

        $objectif = Goal::courant();

        if ($objectif !== null) {
            $imcCible = Bmi::calculer((float) $objectif->target_weight_kg, (int) $taille);
            $series[] = new Serie('IMC du poids cible', array_fill(0, count($jours), $imcCible), pointilles: true, unite: 'kg/m²');
            $valeurs[] = $imcCible;
        }

        return new JeuDeDonnees(
            $this->etiquettes($jours),
            $series,
            axeY: 'IMC (kg/m²)',
            minimumY: floor(min($valeurs)) - 2,
            maximumY: ceil(max($valeurs)) + 2,
            bandes: array_map(
                fn (ZoneCorpulence $zone): Bande => new Bande($zone->libelle(), $zone->minimum(), $zone->maximum(), $zone->couleur()),
                ZoneCorpulence::cases(),
            ),
        );
    }
}
