<?php

namespace Tests\Feature;

use App\Models\{Medecin, Patient, Specialite, StructureMedicale, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Contrôle de santé : aucune page de l'application ne doit rendre une erreur
 * serveur, et chaque espace doit rester fermé à qui n'y a pas droit.
 *
 * Le test parcourt les routes GET sans paramètre, pour chacun des trois rôles
 * ainsi que pour un visiteur non connecté.
 */
class SanteDesRoutesTest extends TestCase
{
    use RefreshDatabase;

    /** Un jeu minimal : un compte par rôle, une structure, une spécialité. */
    private function comptes(): array
    {
        $spec = Specialite::create(['nom' => 'Cardiologie']);
        $structure = StructureMedicale::create(['nom' => 'Hôpital Roi Baudouin',
            'adresse' => 'Guédiawaye', 'latitude' => 14.7758, 'longitude' => -17.4056,
            'type' => 'hopital']);

        $creer = fn (string $role, string $courriel) => User::create([
            'nom' => 'ESSAI', 'prenom' => ucfirst($role), 'role' => $role,
            'email' => $courriel, 'telephone' => '7700000' . random_int(10, 99),
            'password' => 'motdepasse', 'email_verified_at' => now(),
        ]);

        $up = $creer('patient', 'p@essai.sn');
        Patient::create(['utilisateur_id' => $up->id, 'date_naissance' => '1998-01-01',
            'sexe' => 'F']);

        $um = $creer('medecin', 'm@essai.sn');
        Medecin::create(['utilisateur_id' => $um->id, 'specialite_id' => $spec->id,
            'structure_id' => $structure->id, 'num_ordre' => 'SN-9001', 'valide' => true]);

        return ['visiteur' => null, 'patient' => $up, 'medecin' => $um,
                'admin' => $creer('admin', 'a@essai.sn')];
    }

    /** Routes GET publiques, sans paramètre dans l'URL. */
    private function routesSansParametre(): array
    {
        return collect(Route::getRoutes())
            ->filter(fn ($r) => in_array('GET', $r->methods()))
            ->map(fn ($r) => '/' . ltrim($r->uri(), '/'))
            ->reject(fn ($u) => str_contains($u, '{') || str_starts_with($u, '/_')
                             || str_starts_with($u, '/livewire') || $u === '/up')
            ->unique()->values()->all();
    }

    public function test_aucune_page_ne_renvoie_derreur_serveur(): void
    {
        $roles = $this->comptes();

        $fautes = [];
        foreach ($this->routesSansParametre() as $url) {
            foreach ($roles as $nom => $utilisateur) {
                $reponse = $utilisateur
                    ? $this->actingAs($utilisateur)->get($url)
                    : $this->get($url);

                if ($reponse->getStatusCode() >= 500) {
                    $fautes[] = sprintf('%s (%s) → %d', $url, $nom, $reponse->getStatusCode());
                }
            }
        }

        $this->assertSame([], $fautes,
            "Pages en erreur serveur :\n" . implode("\n", $fautes));
    }

    public function test_les_espaces_prives_sont_fermes_au_visiteur(): void
    {
        foreach (['/dashboard', '/profil', '/orientation', '/resultats',
                  '/admin/structures', '/admin/utilisateurs'] as $url) {
            $this->get($url)->assertRedirect();
        }
    }

    public function test_un_patient_natteint_pas_ladministration(): void
    {
        $patient = $this->comptes()['patient'];

        foreach (['/admin/structures', '/admin/utilisateurs'] as $url) {
            $reponse = $this->actingAs($patient)->get($url);
            $this->assertContains($reponse->getStatusCode(), [302, 403],
                "{$url} devrait être refusée au patient, reçu {$reponse->getStatusCode()}");
        }
    }

    /**
     * Les secours restent joignables sans compte.
     *
     * Tout le reste est fermé au visiteur, et c'est voulu. Cette page-là fait
     * exception : quelqu'un qui vit une urgence n'a pas à s'inscrire, confirmer
     * une adresse puis répondre à cinq écrans pour lire le numéro du SAMU. Elle
     * n'affiche aucune donnée personnelle — des numéros et des adresses.
     */
    public function test_les_secours_sont_ouverts_a_tous(): void
    {
        $reponse = $this->get('/urgence');

        $reponse->assertOk();
        $reponse->assertSee('1515');
    }
}
