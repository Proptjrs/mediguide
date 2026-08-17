<?php

namespace Tests\Feature;

use App\Models\{Disponibilite, Medecin, Secretaire, Specialite, StructureMedicale, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La secrétaire médicale (mémoire, chap. 2).
 *
 * Elle tient l'agenda du médecin qu'elle assiste — et rien d'autre. Les deux
 * contrôles qui comptent : elle agit sur cet agenda, et elle ne peut pas agir
 * sur celui d'un autre médecin ni sur les comptes.
 */
class SecretaireAgendaTest extends TestCase
{
    use RefreshDatabase;

    private function medecin(string $suffixe = '1'): Medecin
    {
        $spec = Specialite::firstOrCreate(['nom' => 'Cardiologie']);
        $structure = StructureMedicale::firstOrCreate(['nom' => 'Hôpital Roi Baudouin'], [
            'adresse' => 'Guédiawaye', 'latitude' => 14.7758, 'longitude' => -17.4056, 'type' => 'hopital',
        ]);
        $u = User::create(['nom' => 'DIALLO', 'prenom' => 'Moussa', 'role' => 'medecin',
            'email' => "m{$suffixe}@t.sn", 'password' => 'password', 'email_verified_at' => now()]);

        return Medecin::create(['utilisateur_id' => $u->id, 'structure_id' => $structure->id,
            'specialite_id' => $spec->id, 'num_ordre' => "SN-{$suffixe}", 'valide' => true]);
    }

    private function secretaireDe(Medecin $medecin, string $suffixe = '1'): User
    {
        $u = User::create(['nom' => 'SARR', 'prenom' => 'Fatou', 'role' => 'secretaire',
            'email' => "s{$suffixe}@t.sn", 'password' => 'password', 'email_verified_at' => now()]);
        Secretaire::create(['utilisateur_id' => $u->id, 'medecin_id' => $medecin->id]);

        return $u;
    }

    public function test_la_secretaire_voit_l_agenda_du_medecin_qu_elle_assiste(): void
    {
        $medecin = $this->medecin();

        $this->actingAs($this->secretaireDe($medecin))
            ->get(route('secretaire.agenda'))
            ->assertOk()
            ->assertSee('Moussa', false);
    }

    public function test_elle_declare_une_absence_qui_ferme_le_creneau(): void
    {
        $medecin = $this->medecin();

        $this->actingAs($this->secretaireDe($medecin))
            ->post(route('secretaire.indisponibilite.store'), [
                'date' => now()->addDays(3)->toDateString(),
                'motif' => 'mission',
            ])->assertRedirect();

        $this->assertSame(1, Disponibilite::where('medecin_id', $medecin->id)
            ->where('type', 'INDISPONIBILITE')->count());
    }

    public function test_elle_ne_touche_pas_a_l_agenda_d_un_autre_medecin(): void
    {
        $sien = $this->medecin('1');
        $autre = $this->medecin('2');
        $absenceDeLAutre = Disponibilite::create([
            'medecin_id' => $autre->id, 'type' => 'INDISPONIBILITE',
            'date' => now()->addWeek()->toDateString(), 'motif' => 'conge',
        ]);

        $this->actingAs($this->secretaireDe($sien))
            ->delete(route('secretaire.indisponibilite.destroy', $absenceDeLAutre))
            ->assertForbidden();

        $this->assertDatabaseHas('disponibilites', ['id' => $absenceDeLAutre->id]);
    }

    public function test_elle_n_accede_ni_aux_comptes_ni_aux_structures(): void
    {
        $secretaire = $this->secretaireDe($this->medecin());

        $this->actingAs($secretaire)->get('/admin/utilisateurs')->assertForbidden();
        $this->actingAs($secretaire)->get('/admin/structures')->assertForbidden();
    }

    public function test_un_medecin_n_entre_pas_dans_l_espace_du_secretariat(): void
    {
        $medecin = $this->medecin();

        $this->actingAs($medecin->utilisateur)
            ->get(route('secretaire.agenda'))
            ->assertForbidden();
    }

    public function test_un_compte_secretaire_sans_medecin_est_refuse(): void
    {
        $orpheline = User::create(['nom' => 'NDIAYE', 'prenom' => 'Awa', 'role' => 'secretaire',
            'email' => 'orpheline@t.sn', 'password' => 'password', 'email_verified_at' => now()]);

        $this->actingAs($orpheline)->get(route('secretaire.agenda'))->assertForbidden();
    }

    public function test_l_administrateur_cree_une_secretaire_rattachee_a_un_medecin(): void
    {
        $medecin = $this->medecin();
        $admin = User::create(['nom' => 'ADMIN', 'prenom' => 'ISI', 'role' => 'admin',
            'email' => 'a@t.sn', 'password' => 'password', 'email_verified_at' => now()]);

        $this->actingAs($admin)->post('/admin/utilisateurs', [
            'prenom' => 'Fatou', 'nom' => 'SARR', 'email' => 'fatou.sarr.mediguide@gmail.com',
            'role' => 'secretaire', 'medecin_id' => $medecin->id, 'password' => 'motdepasse1',
        ])->assertRedirect(route('admin.utilisateurs.index'));

        $creee = User::where('email', 'fatou.sarr.mediguide@gmail.com')->first();
        $this->assertSame('secretaire', $creee->role);
        $this->assertSame($medecin->id, $creee->secretaire->medecin_id);
    }

    public function test_une_secretaire_sans_medecin_est_refusee_a_la_creation(): void
    {
        $this->medecin();
        $admin = User::create(['nom' => 'ADMIN', 'prenom' => 'ISI', 'role' => 'admin',
            'email' => 'a2@t.sn', 'password' => 'password', 'email_verified_at' => now()]);

        $this->actingAs($admin)->post('/admin/utilisateurs', [
            'prenom' => 'Awa', 'nom' => 'NDIAYE', 'email' => 'awa.ndiaye.mediguide@gmail.com',
            'role' => 'secretaire', 'password' => 'motdepasse1',
        ])->assertSessionHasErrors('medecin_id');

        $this->assertDatabaseMissing('users', ['email' => 'awa.ndiaye.mediguide@gmail.com']);
    }
}
