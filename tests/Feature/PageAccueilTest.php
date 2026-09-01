<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Vérifie la page d'accueil publique : elle applique la charte graphique miam
 * et ne conserve aucun contenu de démonstration Laravel.
 */
class PageAccueilTest extends TestCase
{
    public function test_la_page_d_accueil_repond_et_affiche_la_charte_miam(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Miam', false);
        $response->assertSee('Suivez vos calories, atteignez vos objectifs', false);
        $response->assertSee('images/hero-aliments-frais.png', false);
    }

    public function test_la_page_d_accueil_ne_contient_plus_le_contenu_laravel_par_defaut(): void
    {
        $response = $this->get('/');

        $response->assertDontSee('Laravel');
        $response->assertDontSee('Laracasts');
        $response->assertDontSee("Let's get started", false);
    }
}
