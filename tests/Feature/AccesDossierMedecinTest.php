<?php

namespace Tests\Feature;

use App\Models\{DossierPatient, Medecin, Patient, RendezVous, Specialite, StructureMedicale, User};
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Accès du médecin au dossier de ses patients (chap. 2 et 4.2.7) :
 * autorisé uniquement si un rendez-vous CONFIRME ou HONORE les lie.
 */
class AccesDossierMedecinTest extends TestCase
{
    use RefreshDatabase;

    private function contexte(): array
    {
        $spec = Specialite::create(['nom' => 'Cardiologie']);
        $structure = StructureMedicale::create(['nom' => 'Hôpital Roi Baudouin',
            'adresse' => 'Guédiawaye', 'latitude' => 14.7758, 'longitude' => -17.4056, 'type' => 'hopital']);

        $creerMedecin = function (string $suffixe) use ($spec, $structure) {
            $u = User::create(['nom' => 'DIALLO', 'prenom' => 'Moussa', 'role' => 'medecin',
                'email' => "med{$suffixe}@gmail.com", 'password' => 'password', 'email_verified_at' => now()]);

            return Medecin::create(['utilisateur_id' => $u->id, 'structure_id' => $structure->id,
                'specialite_id' => $spec->id, 'num_ordre' => "SN-{$suffixe}", 'valide' => true]);
        };

        $uPat = User::create(['nom' => 'GUEYE', 'prenom' => 'Samba', 'role' => 'patient',
            'email' => 'patient@gmail.com', 'password' => 'password', 'email_verified_at' => now()]);
        $patient = Patient::create(['utilisateur_id' => $uPat->id]);
        $dossier = DossierPatient::create(['patient_id' => $patient->id, 'antecedents' => 'Hypertension']);

        return [$creerMedecin('1'), $creerMedecin('2'), $patient, $dossier];
    }

    public function test_le_medecin_traitant_accede_au_dossier(): void
    {
        [$traitant, , $patient, $dossier] = $this->contexte();
        RendezVous::create(['patient_id' => $patient->id, 'medecin_id' => $traitant->id,
            'date_heure' => Carbon::tomorrow()->setTime(10, 0), 'statut' => 'CONFIRME']);

        $this->actingAs($traitant->utilisateur)
            ->get(route('medecin.dossier', $dossier))
            ->assertOk()
            ->assertSee('Hypertension');
    }

    public function test_un_rendez_vous_honore_ouvre_aussi_le_dossier(): void
    {
        [$traitant, , $patient, $dossier] = $this->contexte();
        RendezVous::create(['patient_id' => $patient->id, 'medecin_id' => $traitant->id,
            'date_heure' => Carbon::yesterday()->setTime(10, 0), 'statut' => 'HONORE']);

        $this->actingAs($traitant->utilisateur)
            ->get(route('medecin.dossier', $dossier))->assertOk();
    }

    public function test_un_medecin_sans_rendez_vous_est_refuse(): void
    {
        [, $etranger, , $dossier] = $this->contexte();

        $this->actingAs($etranger->utilisateur)
            ->get(route('medecin.dossier', $dossier))->assertForbidden();
    }

    /** Un rendez-vous annulé ne donne aucun droit d'accès. */
    public function test_un_rendez_vous_annule_ne_donne_pas_acces(): void
    {
        [, $etranger, $patient, $dossier] = $this->contexte();
        RendezVous::create(['patient_id' => $patient->id, 'medecin_id' => $etranger->id,
            'date_heure' => Carbon::tomorrow()->setTime(11, 0), 'statut' => 'ANNULE']);

        $this->actingAs($etranger->utilisateur)
            ->get(route('medecin.dossier', $dossier))->assertForbidden();
    }

    public function test_un_autre_patient_ne_peut_pas_ouvrir_ce_dossier(): void
    {
        [, , , $dossier] = $this->contexte();
        $autre = User::create(['nom' => 'NDIAYE', 'prenom' => 'Awa', 'role' => 'patient',
            'email' => 'autre@gmail.com', 'password' => 'password', 'email_verified_at' => now()]);
        Patient::create(['utilisateur_id' => $autre->id]);

        $this->actingAs($autre)->get(route('medecin.dossier', $dossier))->assertForbidden();
    }
}
