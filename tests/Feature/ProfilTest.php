<?php

namespace Tests\Feature;

use App\Enums\ActivityLevel;
use App\Enums\Sex;
use App\Filament\App\Pages\MonProfil;
use App\Filament\App\Resources\AcquiredHabitChanges\Pages\CreateAcquiredHabitChange;
use App\Filament\App\Resources\AcquiredHabitChanges\Pages\EditAcquiredHabitChange;
use App\Filament\App\Resources\AcquiredHabitChanges\Pages\ListAcquiredHabitChanges;
use App\Models\AcquiredHabitChange;
use App\Models\Profile;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Profil (FR-3), changements d'habitudes acquis (FR-4) et isolation (FR-5).
 */
class ProfilTest extends TestCase
{
    public function test_un_utilisateur_cree_puis_modifie_son_profil(): void
    {
        $utilisateur = User::factory()->create();
        $this->actingAs($utilisateur);

        $this->get('/app/profil')->assertOk()->assertSee('Mon profil');

        Livewire::test(MonProfil::class)
            ->fillForm([
                'birthdate' => '1970-05-12',
                'height_cm' => 178,
                'sex' => Sex::Homme->value,
                'activity_level' => ActivityLevel::Leger->value,
                'start_weight_kg' => 92.4,
                'start_date' => '2026-09-01',
                'allergies' => 'arachide',
            ])
            ->call('enregistrer')
            ->assertHasNoFormErrors()
            ->assertNotified('Profil créé');

        $profil = Profile::sole();
        $this->assertSame($utilisateur->id, $profil->user_id);
        $this->assertSame($utilisateur->id, $profil->created_by);
        $this->assertSame(ActivityLevel::Leger, $profil->activity_level);
        $this->assertSame('92.4', $profil->start_weight_kg);

        Livewire::test(MonProfil::class)
            ->assertSchemaStateSet(['height_cm' => 178, 'allergies' => 'arachide'])
            ->fillForm(['height_cm' => 177, 'activity_level' => ActivityLevel::Modere->value])
            ->call('enregistrer')
            ->assertNotified('Profil enregistré');

        $this->assertSame(1, Profile::count());
        $this->assertSame(177, $profil->refresh()->height_cm);
        $this->assertSame(ActivityLevel::Modere, $profil->activity_level);
    }

    public function test_la_taille_et_la_date_de_naissance_sont_requises_avec_un_message_explicite(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(MonProfil::class)
            ->fillForm(['birthdate' => null, 'height_cm' => null, 'sex' => Sex::Femme->value])
            ->call('enregistrer')
            ->assertHasFormErrors(['birthdate' => 'required', 'height_cm' => 'required'])
            ->assertSee('La taille est requise pour calculer votre budget calorique');

        $this->assertSame(0, Profile::count());
    }

    public function test_une_taille_hors_bornes_est_refusee(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(MonProfil::class)
            ->fillForm(['birthdate' => '1980-01-01', 'height_cm' => 320])
            ->call('enregistrer')
            ->assertHasFormErrors(['height_cm' => 'max']);
    }

    public function test_un_utilisateur_ne_voit_jamais_le_profil_d_un_autre(): void
    {
        Profile::factory()->for(User::factory())->create(['allergies' => 'secret-de-alice']);

        $this->actingAs(User::factory()->create());

        Livewire::test(MonProfil::class)
            ->assertSchemaStateSet(['allergies' => null])
            ->assertDontSee('secret-de-alice');
    }

    public function test_un_utilisateur_enregistre_et_gere_ses_changements_d_habitudes(): void
    {
        $utilisateur = User::factory()->create();
        $this->actingAs($utilisateur);

        Livewire::test(CreateAcquiredHabitChange::class)
            ->fillForm(['changed_on' => '2026-03-01', 'description' => 'Sucre supprimé dans le café'])
            ->call('create')
            ->assertHasNoFormErrors();

        $changement = AcquiredHabitChange::sole();
        $this->assertSame($utilisateur->id, $changement->user_id);

        Livewire::test(ListAcquiredHabitChanges::class)
            ->assertCanSeeTableRecords([$changement])
            ->callTableAction('delete', $changement);

        $this->assertSame(0, AcquiredHabitChange::count());
    }

    public function test_un_changement_d_habitude_sans_description_est_refuse(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateAcquiredHabitChange::class)
            ->fillForm(['changed_on' => '2026-03-01', 'description' => ''])
            ->call('create')
            ->assertHasFormErrors(['description' => 'required']);
    }

    public function test_les_changements_d_habitudes_sont_isoles_y_compris_pour_l_administrateur(): void
    {
        $alice = User::factory()->create();
        $changementAlice = AcquiredHabitChange::factory()->for($alice)->create();

        foreach ([User::factory()->create(), User::factory()->admin()->create()] as $autre) {
            $this->actingAs($autre);
            $mien = AcquiredHabitChange::factory()->for($autre)->create();

            Livewire::test(ListAcquiredHabitChanges::class)
                ->assertCanSeeTableRecords([$mien])
                ->assertCanNotSeeTableRecords([$changementAlice]);

            $this->get(EditAcquiredHabitChange::getUrl(['record' => $changementAlice]))->assertNotFound();
        }

        $this->get('/admin')->assertOk()->assertDontSee('Habitudes acquises');
    }
}
