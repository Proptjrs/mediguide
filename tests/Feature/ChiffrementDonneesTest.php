<?php

namespace Tests\Feature;

use App\Models\{Patient, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Chiffrement au repos des données de santé.
 *
 * Depuis le retrait du dossier médical partagé, la seule donnée de santé
 * conservée est celle que le patient saisit dans son profil : ses allergies.
 * Elle doit rester illisible dans la base et lisible à travers l'application.
 */
class ChiffrementDonneesTest extends TestCase
{
    use RefreshDatabase;

    private function patient(string $allergies): Patient
    {
        $u = User::create(['nom' => 'NDIAYE', 'prenom' => 'Awa', 'role' => 'patient',
            'email' => 'a@t.sn', 'password' => 'password']);

        return Patient::create(['utilisateur_id' => $u->id, 'allergies' => $allergies]);
    }

    public function test_les_allergies_sont_illisibles_dans_la_base(): void
    {
        $patient = $this->patient('Pénicilline');

        // Lecture brute, sans passer par Eloquent : rien ne doit transparaître.
        $brut = DB::table('patients')->where('id', $patient->id)->value('allergies');

        $this->assertNotSame('Pénicilline', $brut);
        $this->assertStringNotContainsString('Pénicilline', $brut);
        $this->assertStringNotContainsString('nicilline', $brut);
    }

    public function test_les_allergies_sont_lisibles_par_lapplication(): void
    {
        $patient = $this->patient('Arachide, pénicilline');

        $this->assertSame('Arachide, pénicilline', $patient->fresh()->allergies);
    }

    public function test_le_dossier_medical_partage_nexiste_plus(): void
    {
        $this->assertFalse(\Schema::hasTable('dossiers_patients'));
        $this->assertFalse(\Schema::hasTable('consultations'));
    }
}
