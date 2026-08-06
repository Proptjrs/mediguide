<?php

namespace Tests\Feature;

use App\Models\{Specialite, StructureMedicale, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
