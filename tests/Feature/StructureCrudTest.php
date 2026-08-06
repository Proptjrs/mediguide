<?php

namespace Tests\Feature;

use App\Models\{Medecin, Specialite, StructureMedicale, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** CRUD des structures médicales par l'admin (chap. 2 : « Gérer les structures référencées »). */
class StructureCrudTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create(['nom' => 'ADMIN', 'prenom' => 'ISI', 'role' => 'admin',
            'email' => 'a@t.sn', 'password' => 'password', 'email_verified_at' => now()]);
    }

    public function test_ladmin_peut_creer_une_structure_avec_coordonnees_explicites(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.structures.store'), [
                'nom' => 'Poste de Santé Test', 'adresse' => 'Golf Sud, Guédiawaye',
                'type' => 'poste_sante', 'latitude' => 14.7712, 'longitude' => -17.4098,
            ])->assertRedirect(route('admin.structures.index'));

        $this->assertDatabaseHas('structures_medicales', [
            'nom' => 'Poste de Santé Test', 'type' => 'poste_sante',
        ]);
    }

    /** Sans coordonnées saisies, elles sont déduites de l'adresse par Nominatim (chap. 4.2.4). */
    public function test_les_coordonnees_absentes_sont_geocodees(): void
    {
        Http::fake([
            '*/search*' => Http::response([['lat' => '14.7825', 'lon' => '-17.4010']]),
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.structures.store'), [
                'nom' => 'Centre Géocodé', 'adresse' => 'Wakhinane Nimzatt',
                'type' => 'centre_sante',
            ])->assertRedirect();

        $s = StructureMedicale::where('nom', 'Centre Géocodé')->first();
        $this->assertSame(14.7825, $s->latitude);
        $this->assertSame(-17.4010, $s->longitude);
    }

    public function test_une_structure_avec_medecins_ne_peut_pas_etre_supprimee(): void
    {
        $structure = StructureMedicale::create(['nom' => 'Hôpital Roi Baudouin',
            'adresse' => 'Guédiawaye', 'latitude' => 14.7758, 'longitude' => -17.4056, 'type' => 'hopital']);
        $spec = Specialite::create(['nom' => 'Cardiologie']);
        $u = User::create(['nom' => 'DIALLO', 'prenom' => 'Moussa', 'role' => 'medecin',
            'email' => 'm@t.sn', 'password' => 'password', 'email_verified_at' => now()]);
        Medecin::create(['utilisateur_id' => $u->id, 'structure_id' => $structure->id,
            'specialite_id' => $spec->id, 'num_ordre' => 'SN-1', 'valide' => true]);

        $this->actingAs($this->admin())
            ->delete(route('admin.structures.destroy', $structure))
            ->assertRedirect();

        $this->assertDatabaseHas('structures_medicales', ['id' => $structure->id]);
    }

    public function test_un_patient_ne_peut_pas_gerer_les_structures(): void
    {
        $u = User::create(['nom' => 'GUEYE', 'prenom' => 'Samba', 'role' => 'patient',
            'email' => 'p@t.sn', 'password' => 'password', 'email_verified_at' => now()]);

        $this->actingAs($u)->get(route('admin.structures.index'))->assertForbidden();
    }
}
