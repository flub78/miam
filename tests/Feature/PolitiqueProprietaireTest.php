<?php

namespace Tests\Feature;

use App\Models\User;
use App\Policies\PolitiqueProprietaire;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

/**
 * Politique de base {@see PolitiqueProprietaire} : un utilisateur agit sur ses
 * lignes, jamais sur celles d'un autre ; les actions non liées à une ligne
 * (`viewAny`, `create`) restent ouvertes.
 */
class PolitiqueProprietaireTest extends TestCase
{
    private function policy(): PolitiqueProprietaire
    {
        return new class extends PolitiqueProprietaire {};
    }

    private function ligneDe(int $userId): Model
    {
        $modele = new class extends Model
        {
            protected $guarded = [];
        };
        $modele->setAttribute('user_id', $userId);

        return $modele;
    }

    public function test_un_utilisateur_agit_sur_ses_propres_lignes(): void
    {
        $alice = User::factory()->create();
        $ligne = $this->ligneDe($alice->id);
        $policy = $this->policy();

        $this->assertTrue($policy->view($alice, $ligne));
        $this->assertTrue($policy->update($alice, $ligne));
        $this->assertTrue($policy->delete($alice, $ligne));
    }

    public function test_un_utilisateur_n_agit_pas_sur_les_lignes_d_un_autre(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $ligneAlice = $this->ligneDe($alice->id);
        $policy = $this->policy();

        $this->assertFalse($policy->view($bob, $ligneAlice));
        $this->assertFalse($policy->update($bob, $ligneAlice));
        $this->assertFalse($policy->delete($bob, $ligneAlice));
    }

    public function test_les_actions_sans_ligne_restent_ouvertes(): void
    {
        $alice = User::factory()->create();
        $policy = $this->policy();

        $this->assertTrue($policy->viewAny($alice));
        $this->assertTrue($policy->create($alice));
    }
}
