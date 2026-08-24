<?php

namespace Tests\Feature;

use App\Models\{Specialite, StructureMedicale, User};
use App\Notifications\CompteMedecinValide;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** Inscription médecin (chap. 2 et 3) : compte en attente jusqu'à validation admin du n° d'Ordre. */
class InscriptionMedecinTest extends TestCase
{
    use RefreshDatabase;

    public function test_inscription_medecin_cree_un_compte_non_valide(): void
    {
        $structure = StructureMedicale::create(['nom' => 'Hôpital Roi Baudouin',
            'adresse' => 'Guédiawaye', 'latitude' => 14.7758, 'longitude' => -17.4056, 'type' => 'hopital']);
        $spec = Specialite::create(['nom' => 'Cardiologie']);

        $this->post(route('register.medecin'), [
            'prenom' => 'Fatou', 'nom' => 'WADE', 'email' => 'fatou@test.sn',
            'structure_id' => $structure->id, 'specialite_id' => $spec->id,
            'num_ordre' => 'SN-9999', 'password' => 'password', 'password_confirmation' => 'password',
        ])->assertRedirect(route('login'));

        $user = User::where('email', 'fatou@test.sn')->first();
        $this->assertNotNull($user);
        $this->assertSame('medecin', $user->role);
        $this->assertFalse($user->medecin->valide);
    }

    public function test_un_medecin_non_valide_ne_peut_pas_se_connecter(): void
    {
        $structure = StructureMedicale::create(['nom' => 'Hôpital Roi Baudouin',
            'adresse' => 'Guédiawaye', 'latitude' => 14.7758, 'longitude' => -17.4056, 'type' => 'hopital']);
        $spec = Specialite::create(['nom' => 'Cardiologie']);

        $this->post(route('register.medecin'), [
            'prenom' => 'Fatou', 'nom' => 'WADE', 'email' => 'fatou@test.sn',
            'structure_id' => $structure->id, 'specialite_id' => $spec->id,
            'num_ordre' => 'SN-9999', 'password' => 'password', 'password_confirmation' => 'password',
        ]);

        $this->post(route('login'), [
            'email' => 'fatou@test.sn', 'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    /**
     * L'inscription n'exige plus que le domaine sache recevoir du courrier.
     *
     * La règle « dns » écartait tout domaine sans enregistrement de messagerie —
     * y compris mediguide.sn, celui des comptes de la plateforme : aucun d'eux
     * n'aurait pu s'inscrire par le formulaire.
     */
    public function test_une_adresse_a_domaine_quelconque_est_acceptee(): void
    {
        $structure = StructureMedicale::create(['nom' => 'Hôpital Roi Baudouin',
            'adresse' => 'Guédiawaye', 'latitude' => 14.7758, 'longitude' => -17.4056, 'type' => 'hopital']);
        $spec = Specialite::create(['nom' => 'Cardiologie']);

        $this->post(route('register.medecin'), [
            'prenom' => 'Awa', 'nom' => 'NDIAYE', 'email' => 'awa@mediguide.sn',
            'structure_id' => $structure->id, 'specialite_id' => $spec->id,
            'num_ordre' => 'SN-8888', 'password' => 'password', 'password_confirmation' => 'password',
        ])->assertSessionHasNoErrors();

        $this->assertNotNull(User::where('email', 'awa@mediguide.sn')->first());
    }

    /** La validation par l'administrateur est annoncée au médecin. */
    public function test_le_medecin_est_averti_quand_son_compte_est_valide(): void
    {
        Notification::fake();

        $structure = StructureMedicale::create(['nom' => 'Hôpital Roi Baudouin',
            'adresse' => 'Guédiawaye', 'latitude' => 14.7758, 'longitude' => -17.4056, 'type' => 'hopital']);
        $spec = Specialite::create(['nom' => 'Cardiologie']);

        $this->post(route('register.medecin'), [
            'prenom' => 'Fatou', 'nom' => 'WADE', 'email' => 'fatou@test.sn',
            'structure_id' => $structure->id, 'specialite_id' => $spec->id,
            'num_ordre' => 'SN-9999', 'password' => 'password', 'password_confirmation' => 'password',
        ]);
        $medecin = User::where('email', 'fatou@test.sn')->first()->medecin;

        $admin = User::create(['nom' => 'ADMIN', 'prenom' => 'ISI', 'role' => 'admin',
            'email' => 'admin@test.sn', 'password' => 'password', 'email_verified_at' => now()]);

        $this->actingAs($admin)->post(route('admin.valider', $medecin));

        $this->assertTrue($medecin->fresh()->valide);
        Notification::assertSentTo($medecin->utilisateur, CompteMedecinValide::class);
    }
}
