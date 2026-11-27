<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Laraskel\Users\Filament\Resources\Users\Pages\CreateUser;
use Laraskel\Users\Filament\Resources\Users\Pages\ListUsers;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Comptes (FR-1, FR-2) : création par l'administrateur, première connexion par
 * lien, désactivation / réactivation, accès au panel utilisateur.
 */
class ComptesTest extends TestCase
{
    private function commeAdministrateur(): User
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        return $admin;
    }

    public function test_un_compte_cree_par_l_administrateur_recoit_un_lien_puis_son_titulaire_se_connecte_au_panel(): void
    {
        Notification::fake();
        $this->commeAdministrateur();

        $roleUtilisateur = Role::findOrCreate(User::ROLE_PAR_DEFAUT, 'web');

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Camille Martin',
                'email' => 'camille@example.test',
                'locale' => 'fr',
                'is_active' => true,
                'roles' => [$roleUtilisateur->id],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $camille = User::where('email', 'camille@example.test')->firstOrFail();
        $this->assertTrue($camille->hasRole(User::ROLE_PAR_DEFAUT));

        $jeton = null;
        Notification::assertSentTo($camille, ResetPassword::class, function (ResetPassword $notification) use (&$jeton): bool {
            $jeton = $notification->token;

            return true;
        });

        auth()->logout();

        $this->post('/reset-password', [
            'token' => $jeton,
            'email' => 'camille@example.test',
            'password' => 'nouveau-secret-123',
            'password_confirmation' => 'nouveau-secret-123',
        ])->assertSessionHasNoErrors();

        $this->post('/login', ['email' => 'camille@example.test', 'password' => 'nouveau-secret-123'])
            ->assertRedirect('/app');

        $this->get('/app')->assertOk();
    }

    public function test_un_compte_desactive_est_refuse_avec_un_message_puis_retrouve_l_acces_une_fois_reactive(): void
    {
        $this->commeAdministrateur();
        $utilisateur = User::factory()->create(['password' => Hash::make('secret-de-test')]);

        Livewire::test(ListUsers::class)->callTableAction('desactiver', $utilisateur);
        $this->assertFalse($utilisateur->refresh()->is_active);
        auth()->logout();

        $this->post('/login', ['email' => $utilisateur->email, 'password' => 'secret-de-test'])
            ->assertSessionHasErrors(['email' => 'Ce compte est désactivé. Contactez un administrateur pour le réactiver.']);
        $this->assertGuest();

        $this->commeAdministrateur();
        Livewire::test(ListUsers::class)
            ->filterTable('is_active', false)
            ->callTableAction('activer', $utilisateur);
        auth()->logout();

        $this->post('/login', ['email' => $utilisateur->email, 'password' => 'secret-de-test'])->assertRedirect('/app');
    }

    public function test_un_compte_cree_hors_formulaire_recoit_le_role_utilisateur_par_defaut(): void
    {
        $utilisateur = User::factory()->create();

        $this->assertSame([User::ROLE_PAR_DEFAUT], $utilisateur->getRoleNames()->all());
    }

    public function test_un_compte_desactive_deja_connecte_perd_l_acces_au_panel(): void
    {
        $this->actingAs(User::factory()->inactive()->create())->get('/app')->assertForbidden();
    }

    public function test_un_visiteur_est_renvoye_vers_l_ecran_de_connexion(): void
    {
        $this->get('/app')->assertRedirect('/login');
    }

    public function test_l_inscription_libre_n_existe_pas(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_un_utilisateur_ordinaire_n_accede_pas_a_l_administration(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
    }

    public function test_un_lien_de_mot_de_passe_peut_etre_redemande_par_l_utilisateur(): void
    {
        Notification::fake();
        $utilisateur = User::factory()->create();

        $this->post('/forgot-password', ['email' => $utilisateur->email])->assertSessionHasNoErrors();

        Notification::assertSentTo($utilisateur, ResetPassword::class);
        $this->assertSame(Password::RESET_THROTTLED, Password::sendResetLink(['email' => $utilisateur->email]));
    }
}
