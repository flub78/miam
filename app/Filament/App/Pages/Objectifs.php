<?php

namespace App\Filament\App\Pages;

use App\Exceptions\DonneesInsuffisantes;
use App\Models\Goal;
use App\Models\Profile;
use App\Services\CalorieBudget;
use App\Services\DetailBudget;
use App\Services\WeightTrend;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Objectifs (FR-6 à FR-10) : le budget calorique proposé et son calcul
 * détaillé, la saisie de l'objectif (valeur proposée acceptée ou valeurs
 * manuelles) et l'historique. Chaque enregistrement crée un nouvel objectif,
 * l'ancien reste consultable (FR-9).
 *
 * @property-read Schema $form
 */
class Objectifs extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $slug = 'objectifs';

    protected static ?string $title = 'Objectifs';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static string|UnitEnum|null $navigationGroup = 'Suivi';

    protected static ?string $navigationLabel = 'Objectifs';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.app.pages.objectifs';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $courant = Goal::courant();
        $rythme = $courant !== null ? (float) $courant->target_rate_kg_per_week : CalorieBudget::RYTHME_PAR_DEFAUT;

        $this->form->fill([
            'effective_from' => now()->toDateString(),
            'target_weight_kg' => $courant?->target_weight_kg,
            'target_rate_kg_per_week' => $rythme,
            'daily_calorie_budget' => $courant !== null ? $courant->daily_calorie_budget : $this->proposition($rythme)?->budget,
            'protein_target_g' => $courant?->protein_target_g,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Budget calorique proposé')
                    ->key('proposition')
                    ->description('Estimation de départ (Mifflin-St Jeor × niveau d\'activité − déficit du rythme visé), à ajuster sur vos données réelles lors des bilans.')
                    ->columns(4)
                    ->afterHeader([
                        Action::make('utiliserBudgetPropose')
                            ->label('Utiliser ce budget')
                            ->disabled(fn (): bool => $this->propositionCourante() === null)
                            ->action(function (Set $set): void {
                                $set('daily_calorie_budget', $this->propositionCourante()?->budget);
                            }),
                    ])
                    ->schema([
                        TextEntry::make('metabolisme')
                            ->label('Métabolisme de base')
                            ->state(fn (): string => $this->kcal($this->propositionCourante()?->metabolismeDeBase)),
                        TextEntry::make('maintenance')
                            ->label('Maintenance estimée')
                            ->state(fn (): string => $this->kcal($this->propositionCourante()?->maintenance)),
                        TextEntry::make('deficit')
                            ->label('Déficit (rythme visé)')
                            ->state(fn (): string => $this->kcal($this->propositionCourante()?->deficit, signe: '− ')),
                        TextEntry::make('budget')
                            ->label('Budget proposé')
                            ->weight('bold')
                            ->state(fn (): string => $this->kcal($this->propositionCourante()?->budget))
                            ->helperText(fn (): ?string => $this->propositionCourante()?->plancherApplique
                                ? 'Relevé au minimum conseillé sans suivi médical.'
                                : null),
                        TextEntry::make('erreurCalcul')
                            ->hiddenLabel()
                            ->color('danger')
                            ->columnSpanFull()
                            ->visible(fn (): bool => $this->erreurCalcul() !== null)
                            ->state(fn (): ?string => $this->erreurCalcul()),
                    ]),
                Section::make('Nouvel objectif')
                    ->description('Acceptez le budget proposé ou saisissez vos propres valeurs. Enregistrer crée un nouvel objectif ; le précédent reste dans l\'historique.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('target_weight_kg')
                            ->label('Poids cible')
                            ->suffix('kg')
                            ->numeric()
                            ->minValue(30)
                            ->maxValue(300)
                            ->step(0.1)
                            ->required()
                            ->validationMessages(['required' => 'Indiquez votre poids cible, en kg.']),
                        TextInput::make('target_rate_kg_per_week')
                            ->label('Rythme visé')
                            ->suffix('kg / semaine')
                            ->helperText('Perte hebdomadaire visée ; 0 pour maintenir votre poids.')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(1.5)
                            ->step(0.05)
                            ->required()
                            ->live(onBlur: true)
                            ->validationMessages(['max' => 'Un rythme de plus de 1,5 kg par semaine n\'est pas raisonnable sans suivi médical.']),
                        TextInput::make('daily_calorie_budget')
                            ->label('Budget calorique quotidien')
                            ->suffix('kcal')
                            ->numeric()
                            ->integer()
                            ->minValue(800)
                            ->maxValue(6000)
                            ->required()
                            ->validationMessages(['required' => 'Indiquez votre budget quotidien, ou cliquez sur « Utiliser ce budget ».']),
                        TextInput::make('protein_target_g')
                            ->label('Apport protéique cible')
                            ->suffix('g / jour')
                            ->numeric()
                            ->integer()
                            ->minValue(10)
                            ->maxValue(400),
                        DatePicker::make('effective_from')
                            ->label('En vigueur à partir du')
                            ->maxDate(now())
                            ->required(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Goal::query()->orderByDesc('effective_from')->orderByDesc('id'))
            ->heading('Historique des objectifs')
            ->emptyStateHeading('Aucun objectif enregistré')
            ->emptyStateDescription('Votre premier objectif apparaîtra ici ; chaque changement ajoute une ligne.')
            ->columns([
                TextColumn::make('effective_from')->label('Depuis le')->date('d/m/Y'),
                TextColumn::make('target_weight_kg')->label('Poids cible')->suffix(' kg')->numeric(1, ',', ' '),
                TextColumn::make('target_rate_kg_per_week')->label('Rythme')->suffix(' kg/sem.')->numeric(2, ',', ' '),
                TextColumn::make('daily_calorie_budget')->label('Budget')->suffix(' kcal')->numeric(0, ',', ' '),
                TextColumn::make('protein_target_g')->label('Protéines')->suffix(' g')->placeholder('—'),
                TextColumn::make('indicative_target_date')
                    ->label('Date cible indicative')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->tooltip('Calculée à partir du rythme visé ; non contractuelle.'),
            ]);
    }

    public function enregistrer(): void
    {
        $donnees = $this->form->getState();
        $poids = $this->poidsActuel();

        $objectif = Goal::create([
            ...$donnees,
            'indicative_target_date' => $poids === null ? null : CalorieBudget::dateCibleIndicative(
                $poids,
                (float) $donnees['target_weight_kg'],
                (float) $donnees['target_rate_kg_per_week'],
            ),
        ]);

        Notification::make()
            ->success()
            ->title('Objectif enregistré')
            ->body($objectif->indicative_target_date === null
                ? null
                : 'Date cible indicative, non contractuelle : '.$objectif->indicative_target_date->format('d/m/Y').'.')
            ->send();

        $this->resetTable();
    }

    /**
     * Budget proposé pour le rythme saisi dans le formulaire.
     */
    public function propositionCourante(): ?DetailBudget
    {
        return $this->proposition((float) ($this->data['target_rate_kg_per_week'] ?? CalorieBudget::RYTHME_PAR_DEFAUT));
    }

    /**
     * Ce qui empêche le calcul (profil incomplet, aucun poids connu), `null` sinon.
     */
    public function erreurCalcul(): ?string
    {
        try {
            $this->calculer((float) ($this->data['target_rate_kg_per_week'] ?? CalorieBudget::RYTHME_PAR_DEFAUT));
        } catch (DonneesInsuffisantes $exception) {
            return $exception->getMessage();
        }

        return null;
    }

    private function proposition(float $rythme): ?DetailBudget
    {
        try {
            return $this->calculer($rythme);
        } catch (DonneesInsuffisantes) {
            return null;
        }
    }

    /**
     * @throws DonneesInsuffisantes
     */
    private function calculer(float $rythme): DetailBudget
    {
        $poids = $this->poidsActuel();

        if ($poids === null) {
            throw new DonneesInsuffisantes('Le budget calorique ne peut pas être calculé : saisissez une pesée dans « Pesées », ou votre poids de départ dans « Mon profil ».');
        }

        return CalorieBudget::calculer(Profile::query()->first(), $poids, $rythme);
    }

    /**
     * Dernier poids pesé, à défaut le poids de départ du profil.
     */
    private function poidsActuel(): ?float
    {
        $poids = once(fn (): ?float => WeightTrend::utilisateurCourant()->dernierPoids());
        $depart = Profile::query()->value('start_weight_kg');

        return $poids ?? ($depart === null ? null : (float) $depart);
    }

    private function kcal(?int $valeur, string $signe = ''): string
    {
        return $valeur === null ? '—' : $signe.number_format($valeur, 0, ',', ' ').' kcal';
    }
}
