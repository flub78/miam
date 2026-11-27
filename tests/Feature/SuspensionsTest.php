<?php

namespace Tests\Feature;

use App\Filament\App\Resources\SuspensionPeriods\Pages\ManageSuspensionPeriods;
use App\Models\SuspensionPeriod;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Périodes de suspension (FR-59) et isolation (FR-5).
 */
class SuspensionsTest extends TestCase
{
    public function test_un_utilisateur_declare_corrige_et_supprime_une_suspension(): void
    {
        $utilisateur = User::factory()->create();
        $this->actingAs($utilisateur);

        $this->get('/app/suspensions')->assertOk()->assertSee('Périodes de suspension');

        Livewire::test(ManageSuspensionPeriods::class)
            ->callAction('create', data: ['starts_on' => '2026-12-20', 'ends_on' => '2026-12-27', 'reason' => 'Voyage en Italie'])
            ->assertHasNoFormErrors()
            ->assertNotified();

        $periode = SuspensionPeriod::sole();
        $this->assertSame($utilisateur->id, $periode->user_id);
        $this->assertSame('Voyage en Italie', $periode->reason);

        Livewire::test(ManageSuspensionPeriods::class)
            ->assertSee('8 j')
            ->callTableAction('edit', $periode, data: ['ends_on' => '2026-12-28'])
            ->assertHasNoTableActionErrors();
        $this->assertSame('2026-12-28', $periode->refresh()->ends_on->toDateString());

        Livewire::test(ManageSuspensionPeriods::class)->callTableAction('delete', $periode);
        $this->assertModelMissing($periode);
    }

    public function test_une_fin_avant_le_debut_ou_un_motif_absent_sont_refuses(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ManageSuspensionPeriods::class)
            ->callAction('create', data: ['starts_on' => '2026-12-20', 'ends_on' => '2026-12-10', 'reason' => null])
            ->assertHasFormErrors([
                'ends_on' => ['La fin de la suspension doit être le même jour que le début ou après.'],
                'reason' => 'required',
            ]);

        $this->assertSame(0, SuspensionPeriod::count());
    }

    public function test_les_suspensions_sont_isolees_entre_utilisateurs(): void
    {
        $moi = User::factory()->create();
        $autre = User::factory()->admin()->create();
        $mienne = SuspensionPeriod::factory()->for($moi)->create();
        $sienne = SuspensionPeriod::factory()->for($autre)->create();

        $this->actingAs($autre);
        Livewire::test(ManageSuspensionPeriods::class)
            ->assertCanSeeTableRecords([$sienne])
            ->assertCanNotSeeTableRecords([$mienne]);
    }
}
