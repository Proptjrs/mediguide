<?php

namespace Tests\Browser;

use App\Models\{DossierPatient, Medecin, StructureMedicale, User};
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Génère les captures d'écran destinées au mémoire.
 *
 * Chaque page est photographiée EN ENTIER : la fenêtre est agrandie à la hauteur
 * réelle du document avant la prise de vue, sinon seule la partie visible sans
 * défilement serait capturée et le bas des pages manquerait.
 *
 * Ce n'est pas un test de non-régression : il ne vérifie rien, il documente.
 * Les fichiers atterrissent dans tests/Browser/screenshots/.
 */
class CapturesMemoireTest extends DuskTestCase
{
    private const LARGEUR = 1440;
    private const HAUTEUR = 880;

    /**
     * Photographie l'écran tel que l'utilisateur le voit.
     *
     * On ne déroule pas la page entière : le pied de page et les mentions
     * secondaires n'apportent rien à un rapport et écrasent le contenu utile.
     */
    private function prendre(Browser $b, string $url, string $fichier, string $attendre): void
    {
        $b->resize(self::LARGEUR, self::HAUTEUR)
            ->visit($url)
            ->waitForText($attendre, 20)
            ->pause(1800)                    // laisse finir les animations d'apparition
            ->script('window.scrollTo(0, 0);');

        $b->pause(400)->screenshot($fichier);
    }

    /**
     * Photographie une zone située plus bas dans la page : le panneau visé est
     * amené en haut de la fenêtre avant la prise de vue.
     */
    private function prendreZone(Browser $b, string $url, string $fichier,
                                 string $attendre, string $ancre): void
    {
        $b->resize(self::LARGEUR, self::HAUTEUR)
            ->visit($url)
            ->waitForText($attendre, 20)
            ->pause(1800)
            ->script("document.evaluate(\"//h3[contains(., '{$ancre}')]\", document, null, 9, null)"
                   . ".singleNodeValue?.scrollIntoView({block:'start'}); window.scrollBy(0, -110);");

        $b->pause(600)->screenshot($fichier);
    }

    public function test_captures_pages_publiques(): void
    {
        $this->browse(function (Browser $b) {
            $b->logout();
            $this->prendre($b, '/', '01-accueil', 'quel médecin');
            $this->prendre($b, '/orientation', '02-questionnaire', 'Où êtes-vous');
            $this->prendre($b, '/resultats', '03-structures-carte', 'Structures proches');
            $this->prendre($b, '/urgence', '04-urgence-samu', 'SAMU');
            $this->prendre($b, '/connexion', '05-connexion', 'Bon retour');
            $this->prendre($b, '/inscription', '06-inscription-patient', 'Créer mon compte');
            $this->prendre($b, '/inscription-medecin', '07-inscription-medecin', 'Ordre');
            $this->prendre($b, '/mot-de-passe-oublie', '08-mot-de-passe-oublie', 'Mot de passe');
        });
    }

    public function test_captures_espace_patient(): void
    {
        $patient = User::where('email', config('mediguide.demo.patient'))->firstOrFail();
        $medecin = Medecin::where('valide', true)->firstOrFail();

        $this->browse(function (Browser $b) use ($patient, $medecin) {
            $b->loginAs($patient);
            $this->prendre($b, '/dashboard', '09-espace-patient', 'Mon espace patient');
            $this->prendre($b, "/medecin/{$medecin->id}/calendrier", '10-calendrier-creneaux', 'créneau');
            $this->prendre($b, '/profil', '11-profil-patient', 'Mon profil');
        });
    }

    public function test_captures_espace_medecin(): void
    {
        $medecin = User::where('email', config('mediguide.demo.medecin'))->firstOrFail();
        $patient = User::where('email', config('mediguide.demo.patient'))->firstOrFail();
        $dossier = DossierPatient::whereHas('patient', fn ($q) => $q->where('utilisateur_id', $patient->id))->first();

        $this->browse(function (Browser $b) use ($medecin, $dossier) {
            $b->loginAs($medecin);
            $this->prendre($b, '/dashboard', '12-agenda-medecin', 'Mon agenda');
            $this->prendre($b, '/profil', '13-profil-medecin', 'Mon profil');

            if ($dossier) {
                $this->prendre($b, "/medecin/dossier/{$dossier->id}", '14-dossier-patient', 'Informations médicales');
            }
        });
    }

    public function test_captures_administration(): void
    {
        $admin = User::where('email', config('mediguide.demo.admin'))->firstOrFail();
        $medecin = Medecin::firstOrFail();
        $structure = StructureMedicale::firstOrFail();
        $patient = User::where('role', 'patient')->firstOrFail();

        $this->browse(function (Browser $b) use ($admin, $medecin, $structure, $patient) {
            $b->loginAs($admin);
            $this->prendre($b, '/dashboard', '15-console-admin', 'Administration');
            $this->prendre($b, '/admin/structures', '16-admin-structures', 'Structures');
            $this->prendre($b, '/admin/structures/creer', '17-admin-structure-creer', 'tructure');
            $this->prendre($b, "/admin/structures/{$structure->id}/modifier", '18-admin-structure-modifier', 'tructure');
            $this->prendre($b, '/admin/utilisateurs', '19-admin-utilisateurs', 'Comptes utilisateurs');
            $this->prendre($b, '/admin/utilisateurs/creer', '20-admin-utilisateur-creer', 'ompte');
            $this->prendre($b, "/admin/utilisateurs/{$patient->id}/modifier", '21-admin-utilisateur-modifier', 'ompte');
            $this->prendre($b, "/admin/medecin/{$medecin->id}/planning", '22-admin-planning', 'Planning de consultation');
            $this->prendre($b, '/admin/dossiers', '23-admin-dossiers', 'dossier patient');
            $this->prendreZone($b, '/dashboard', '24-admin-pilotage', 'Administration', 'Pilotage');
            $this->prendreZone($b, '/dashboard', '25-admin-patients-risque', 'Administration',
                'Patients à rappeler');
        });
    }

    /**
     * Les deux dispositifs destinés aux patients du district : la lecture à voix
     * haute pour ceux qui lisent mal, et le bandeau de perte de connexion.
     */
    public function test_captures_accessibilite_et_hors_ligne(): void
    {
        $this->browse(function (Browser $b) {
            $b->logout();

            $b->resize(self::LARGEUR, self::HAUTEUR)
                ->visit('/orientation')
                ->waitForText('Où êtes-vous', 20)
                ->pause(1500)
                ->script('window.scrollTo(0, 0);');
            $b->pause(400)->screenshot('26-questionnaire-ecouter');

            // Perte de réseau simulée : le bandeau doit apparaître en bas.
            $b->script("window.dispatchEvent(new Event('offline'));"
                     . "document.body.classList.add('hors-ligne');");
            $b->pause(700)->screenshot('27-hors-ligne');

            $b->visit('/hors-ligne.html')->pause(900)->screenshot('28-page-hors-ligne');
        });
    }
}
