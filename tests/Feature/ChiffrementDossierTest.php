<?php

namespace Tests\Feature;

use App\Models\{Consultation, DossierPatient, Medecin, Patient, RendezVous, Specialite, StructureMedicale, User};
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Chiffrement au repos des données médicales (mémoire, section 6 :
 * « chiffrement des documents médicaux »).
 *
 * On vérifie les deux faces : illisible en base, lisible via l'application.
 */
class ChiffrementDossierTest extends TestCase
{
    use RefreshDatabase;

    public function test_les_antecedents_sont_chiffres_en_base(): void
    {
        $u = User::create(['nom' => 'GUEYE', 'prenom' => 'Samba', 'role' => 'patient',
            'email' => 'p@t.sn', 'password' => 'password']);
        $patient = Patient::create(['utilisateur_id' => $u->id]);
        $dossier = DossierPatient::create(['patient_id' => $patient->id, 'antecedents' => 'Diabète type 2']);

        // Lecture brute, sans passer par Eloquent : la valeur ne doit pas apparaître.
        $brut = DB::table('dossiers_patients')->where('id', $dossier->id)->value('antecedents');
        $this->assertNotSame('Diabète type 2', $brut);
        $this->assertStringNotContainsString('Diabète', $brut);

        // Via l'application : la valeur est déchiffrée de façon transparente.
        $this->assertSame('Diabète type 2', $dossier->fresh()->antecedents);
    }

    public function test_les_allergies_sont_chiffrees_en_base(): void
    {
        $u = User::create(['nom' => 'NDIAYE', 'prenom' => 'Awa', 'role' => 'patient',
            'email' => 'a@t.sn', 'password' => 'password']);
        $patient = Patient::create(['utilisateur_id' => $u->id, 'allergies' => 'Pénicilline']);

        $brut = DB::table('patients')->where('id', $patient->id)->value('allergies');
        $this->assertStringNotContainsString('Pénicilline', $brut);
        $this->assertSame('Pénicilline', $patient->fresh()->allergies);
    }

    public function test_le_compte_rendu_et_lordonnance_sont_chiffres(): void
    {
        $spec = Specialite::create(['nom' => 'Cardiologie']);
        $structure = StructureMedicale::create(['nom' => 'Hôpital Roi Baudouin',
            'adresse' => 'Guédiawaye', 'latitude' => 14.7758, 'longitude' => -17.4056, 'type' => 'hopital']);
        $uMed = User::create(['nom' => 'DIALLO', 'prenom' => 'Moussa', 'role' => 'medecin',
            'email' => 'm@t.sn', 'password' => 'password']);
        $medecin = Medecin::create(['utilisateur_id' => $uMed->id, 'structure_id' => $structure->id,
            'specialite_id' => $spec->id, 'num_ordre' => 'SN-1', 'valide' => true]);
        $uPat = User::create(['nom' => 'SARR', 'prenom' => 'Ibrahima', 'role' => 'patient',
            'email' => 'i@t.sn', 'password' => 'password']);
        $patient = Patient::create(['utilisateur_id' => $uPat->id]);
        $rdv = RendezVous::create(['patient_id' => $patient->id, 'medecin_id' => $medecin->id,
            'date_heure' => Carbon::tomorrow()->setTime(10, 0), 'statut' => 'CONFIRME']);

        $c = Consultation::create([
            'rendez_vous_id' => $rdv->id, 'medecin_id' => $medecin->id, 'patient_id' => $patient->id,
            'observations' => 'Hypertension confirmée', 'prescription' => 'Amlodipine 5 mg',
        ]);

        $brut = DB::table('consultations')->where('id', $c->id)->first();
        $this->assertStringNotContainsString('Hypertension', $brut->observations);
        $this->assertStringNotContainsString('Amlodipine', $brut->prescription);

        $relu = $c->fresh();
        $this->assertSame('Hypertension confirmée', $relu->observations);
        $this->assertSame('Amlodipine 5 mg', $relu->prescription);
    }
}
