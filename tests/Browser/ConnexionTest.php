<?php

namespace Tests\Browser;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Connexion par le formulaire pour les trois rôles, et cloisonnement des accès.
 *
 * Chaque rôle doit arriver sur son propre espace : le patient sur son suivi, le
 * médecin sur son agenda, l'administrateur sur la console d'administration.
 */
class ConnexionTest extends DuskTestCase
{
    /** Saisit e-mail et mot de passe puis valide, comme le ferait un utilisateur. */
    private function seConnecter(Browser $b, string $email): Browser
    {
        return $b->logout()
            ->visit('/connexion')
            ->waitForText('Bon retour')
            ->type('email', $email)
            ->type('password', config('mediguide.demo.mot_de_passe'))
            ->press('Se connecter')
            ->waitForLocation('/dashboard');
    }

    public function test_un_patient_se_connecte_et_voit_son_espace(): void
    {
        $this->browse(fn (Browser $b) => $this
            ->seConnecter($b, config('mediguide.demo.patient'))
            ->assertSee('Mon espace patient'));
    }

    public function test_un_medecin_se_connecte_et_voit_son_agenda(): void
    {
        $this->browse(fn (Browser $b) => $this
            ->seConnecter($b, config('mediguide.demo.medecin'))
            ->assertSee('Mon agenda'));
    }

    public function test_un_administrateur_se_connecte_et_voit_la_console(): void
    {
        $this->browse(fn (Browser $b) => $this
            ->seConnecter($b, config('mediguide.demo.admin'))
            ->assertSee('Administration'));
    }

    /** Un mot de passe erroné ne doit jamais ouvrir de session. */
    public function test_un_mauvais_mot_de_passe_est_refuse(): void
    {
        $this->browse(function (Browser $b) {
            $b->logout()
                ->visit('/connexion')
                ->waitForText('Bon retour')
                ->type('email', config('mediguide.demo.patient'))
                ->type('password', 'mauvais-mot-de-passe')
                ->press('Se connecter')
                ->waitForText('Identifiants incorrects')
                ->assertPathIs('/connexion');
        });
    }

    /**
     * La page de connexion ne doit divulguer aucun compte : afficher des
     * adresses réelles renseignerait un attaquant sur les comptes existants.
     */
    public function test_la_page_de_connexion_ne_divulgue_aucun_compte(): void
    {
        $this->browse(function (Browser $b) {
            $b->logout()
                ->visit('/connexion')
                ->waitForText('Bon retour')
                ->assertDontSee('@demo.sn')
                ->assertDontSee(config('mediguide.demo.patient'))
                ->assertDontSee(config('mediguide.demo.medecin'))
                ->assertDontSee(config('mediguide.demo.admin'));
        });
    }

    /** Cliquer son propre nom doit ouvrir le profil, pas déconnecter. */
    public function test_cliquer_son_nom_ouvre_le_profil(): void
    {
        $patient = User::where('email', config('mediguide.demo.patient'))->firstOrFail();

        $this->browse(function (Browser $b) use ($patient) {
            $b->loginAs($patient)
                ->visit('/dashboard')
                ->clickLink($patient->fullName())
                ->waitForLocation('/profil')
                ->assertSee('Mon profil')
                ->assertSee('Identité et contact');
        });
    }

    /**
     * Le questionnaire est ouvert aux visiteurs : la connexion n'est demandée
     * qu'au moment de réserver, comme sur les plateformes du même type.
     */
    public function test_un_invite_peut_repondre_au_questionnaire(): void
    {
        $this->browse(function (Browser $b) {
            $b->logout()
                ->visit('/orientation')
                ->waitForText('Questionnaire d\'orientation')
                ->assertPathIs('/orientation');
        });
    }

    /** En revanche, l'espace personnel reste fermé sans compte. */
    public function test_un_invite_ne_peut_pas_ouvrir_lespace_personnel(): void
    {
        $this->browse(function (Browser $b) {
            $b->logout()
                ->visit('/dashboard')
                ->waitForLocation('/connexion')
                ->assertSee('Bon retour');
        });
    }
}
