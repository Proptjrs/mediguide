<?php

namespace Tests\Feature;

use App\Models\{Disponibilite, Medecin, Patient, Specialite, StructureMedicale, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Contrôle d'accès par rôle sur les plannings (mémoire, chap. 3) :
 * seul l'admin fixe le planning de base ; le médecin ne déclare que ses indisponibilités.
 */
class PlanningAccessTest extends TestCase
{
    use RefreshDatabase;

    private function medecin(string $emailSuffix = '1'): Medecin
    {
        $spec = Specialite::firstOrCreate(['nom' => 'Cardiologie']);
        $structure = StructureMedicale::firstOrCreate(['nom' => 'Hôpital Roi Baudouin'], [
            'adresse' => 'Guédiawaye', 'latitude' => 14.7758, 'longitude' => -17.4056, 'type' => 'hopital',
        ]);
        $u = User::create(['nom' => 'DIALLO', 'prenom' => 'Moussa', 'role' => 'medecin',
            'email' => "m{$emailSuffix}@t.sn", 'password' => 'password',
            'email_verified_at' => now()]);

        return Medecin::create(['utilisateur_id' => $u->id, 'structure_id' => $structure->id,
            'specialite_id' => $spec->id, 'num_ordre' => "SN-{$emailSuffix}", 'valide' => true]);
    }

    public function test_le_medecin_ne_peut_pas_gerer_le_planning_de_base(): void
    {
        $medecin = $this->medecin();

        $this->actingAs($medecin->utilisateur)
            ->get(route('admin.planning.edit', $medecin))
            ->assertForbidden();
    }

    public function test_le_patient_ne_peut_pas_gerer_le_planning_de_base(): void
    {
        $medecin = $this->medecin();
        $uPat = User::create(['nom' => 'GUEYE', 'prenom' => 'Samba', 'role' => 'patient',
            'email' => 'p@t.sn', 'password' => 'password', 'email_verified_at' => now()]);
        Patient::create(['utilisateur_id' => $uPat->id]);

        $this->actingAs($uPat)
            ->get(route('admin.planning.edit', $medecin))
            ->assertForbidden();
    }

    public function test_ladmin_peut_ajouter_une_plage_de_planning(): void
    {
        $medecin = $this->medecin();
        $admin = User::create(['nom' => 'ADMIN', 'prenom' => 'ISI', 'role' => 'admin',
            'email' => 'a@t.sn', 'password' => 'password', 'email_verified_at' => now()]);

        $this->actingAs($admin)
            ->post(route('admin.planning.store', $medecin), [
                'jour_semaine' => 1, 'heure_debut' => '08:00', 'heure_fin' => '12:00',
            ])->assertRedirect();

        $this->assertDatabaseHas('disponibilites', [
            'medecin_id' => $medecin->id, 'type' => 'BASE', 'jour_semaine' => 1,
        ]);
    }

    public function test_le_medecin_peut_declarer_sa_propre_indisponibilite(): void
    {
        $medecin = $this->medecin();

        $this->actingAs($medecin->utilisateur)
            ->post(route('medecin.indisponibilite.store'), [
                'date' => now()->addDay()->toDateString(), 'motif' => 'urgence',
            ])->assertRedirect();

        $this->assertDatabaseHas('disponibilites', [
            'medecin_id' => $medecin->id, 'type' => 'INDISPONIBILITE', 'motif' => 'urgence',
        ]);
    }

    public function test_un_medecin_ne_peut_pas_annuler_lindisponibilite_dun_autre(): void
    {
        $medecinA = $this->medecin('a');
        $medecinB = $this->medecin('b');
        $indispo = Disponibilite::create([
            'medecin_id' => $medecinA->id, 'type' => 'INDISPONIBILITE',
            'date' => now()->addDay()->toDateString(), 'motif' => 'conge',
        ]);

        $this->actingAs($medecinB->utilisateur)
            ->delete(route('medecin.indisponibilite.destroy', $indispo))
            ->assertForbidden();
    }
}
