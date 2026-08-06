<?php

namespace Tests\Browser;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/** Tests E2E Laravel Dusk (mémoire, chap. 4.6.3).
 *  Prérequis : php artisan dusk:install && php artisan dusk (cf. README). */
class ParcoursPatientTest extends DuskTestCase
{
    /**
     * Le questionnaire est réservé aux patients connectés : on s'appuie sur le
     * compte de démonstration créé par le seeder (php artisan migrate --seed).
     */
    private function patientDemo(): User
    {
        return User::where('email', config('mediguide.demo.patient'))->firstOrFail();
    }

    /** Parcours complet : questionnaire 5 étapes sans urgence -> résultats. */
    public function test_questionnaire_complet_oriente_vers_resultats(): void
    {
        $this->browse(function (Browser $b) {
            $b->loginAs($this->patientDemo())
                ->visit('/orientation')
                ->assertSee('Localisation')
                ->select('select', '14.7712,-17.4098')            // Golf Sud
                ->press('Continuer')->waitForText('Quel est votre profil')
                ->type('@age', '30')->press('Continuer')->waitForText('problème principal')
                ->press('Douleur')->press('Continuer')->waitForText('situe la gêne')
                ->press('Poitrine / cœur')->press('Continuer')->waitForText("niveau d'urgence")
                ->press('Voir mon orientation')
                ->waitForLocation('/resultats')
                ->assertQueryStringHas('spec', 'Cardiologie');    // arbre de décision F1
        });
    }

    /**
     * F2 : deux signes d'alarme => score >= 7 => redirection vers l'écran urgence.
     *
     * Détail du score (chap. 4.1) : niveau déclaré 3 par défaut -> round(3 × 0,7) = 2,
     * + douleur thoracique (3) + difficulté respiratoire (3) = 8, donc >= 7.
     */
    public function test_urgence_detectee_redirige_vers_samu(): void
    {
        $this->browse(function (Browser $b) {
            $b->loginAs($this->patientDemo())
                ->visit('/orientation')
                ->select('select', '14.7699,-17.4021')
                ->press('Continuer')->waitForText('Quel est votre profil')
                ->press('Continuer')->waitForText('problème principal')
                ->press('Douleur')->press('Continuer')->waitForText('situe la gêne')
                ->press('Poitrine / cœur')->press('Continuer')->waitForText("niveau d'urgence")
                ->check('#al-douleur_thoracique')
                ->check('#al-difficulte_respiratoire')
                ->press('Voir mon orientation')
                ->waitForText('Urgence détectée')
                ->assertSee('SAMU — 15');
        });
    }

    /**
     * Inscription patient (UC-P1) : l'adresse doit d'abord être confirmée, on
     * atterrit donc sur l'écran de confirmation et non sur le tableau de bord.
     */
    public function test_inscription_patient_demande_confirmation_email(): void
    {
        $this->browse(function (Browser $b) {
            // Les tests précédents laissent une session : /inscription est
            // protégée par le middleware "guest", il faut donc se déconnecter.
            $b->logout()
                ->visit('/inscription')
                ->type('prenom', 'Test')->type('nom', 'DUSK')
                ->type('email', 'dusk' . time() . '@gmail.com')
                ->type('password', 'password123')
                ->type('password_confirmation', 'password123')
                ->press('Créer mon compte')
                ->waitForLocation('/email/confirmation')
                ->assertSee('Confirmez votre adresse');
        });
    }
}
