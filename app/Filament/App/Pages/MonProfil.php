<?php

namespace App\Filament\App\Pages;

use App\Enums\ActivityLevel;
use App\Enums\Sex;
use App\Models\Profile;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * Profil de l'utilisateur connecté (FR-3) : une page d'édition de l'unique
 * profil, créé au premier enregistrement. Taille et date de naissance sont
 * requises (budget calorique), le reste est facultatif.
 *
 * @property-read Schema $form
 */
class MonProfil extends Page
{
    protected static ?string $slug = 'profil';

    protected static ?string $title = 'Mon profil';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|UnitEnum|null $navigationGroup = 'Mon profil';

    protected static ?string $navigationLabel = 'Profil';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.app.pages.mon-profil';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $profil = $this->profil();

        $this->form->fill($profil?->attributesToArray() ?? []);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Données nécessaires au budget calorique')
                    ->columns(2)
                    ->schema([
                        DatePicker::make('birthdate')
                            ->label('Date de naissance')
                            ->required()
                            ->maxDate(now()->subYears(10))
                            ->minDate(now()->subYears(120))
                            ->validationMessages([
                                'required' => 'La date de naissance est requise pour calculer votre budget calorique.',
                            ]),
                        TextInput::make('height_cm')
                            ->label('Taille')
                            ->suffix('cm')
                            ->numeric()
                            ->integer()
                            ->minValue(100)
                            ->maxValue(250)
                            ->required()
                            ->validationMessages([
                                'required' => 'La taille est requise pour calculer votre budget calorique (en cm, entre 100 et 250).',
                            ]),
                        Select::make('sex')
                            ->label('Sexe')
                            ->options(Sex::options())
                            ->helperText('Utilisé par la formule de calcul du métabolisme de base.'),
                        Select::make('activity_level')
                            ->label('Niveau d\'activité physique')
                            ->options(ActivityLevel::options()),
                    ]),
                Section::make('Point de départ')
                    ->columns(2)
                    ->schema([
                        TextInput::make('start_weight_kg')
                            ->label('Poids de départ')
                            ->suffix('kg')
                            ->numeric()
                            ->minValue(20)
                            ->maxValue(400)
                            ->step(0.1),
                        DatePicker::make('start_date')
                            ->label('Date de départ')
                            ->maxDate(now()),
                    ]),
                Section::make('Santé et alimentation')
                    ->schema([
                        Textarea::make('medical_constraints')->label('Contraintes médicales')->rows(2)->maxLength(2000),
                        Textarea::make('allergies')->label('Allergies et intolérances')->rows(2)->maxLength(2000),
                        Textarea::make('food_preferences')->label('Préférences alimentaires')->rows(2)->maxLength(2000),
                    ]),
            ]);
    }

    public function enregistrer(): void
    {
        $donnees = $this->form->getState();

        $profil = $this->profil() ?? new Profile(['user_id' => Auth::id()]);
        $creation = ! $profil->exists;
        $profil->fill($donnees)->save();

        Notification::make()
            ->success()
            ->title($creation ? 'Profil créé' : 'Profil enregistré')
            ->send();
    }

    /**
     * Le profil de l'utilisateur connecté (le global scope du modèle restreint
     * déjà la requête à ses lignes).
     */
    private function profil(): ?Profile
    {
        return Profile::query()->first();
    }
}
