<?php

namespace Tests\Feature;

use App\Models\{Medecin, Patient, RendezVous, Specialite, StructureMedicale, User};
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Annulation de RDV par le patient (chap. 2 — "prendre, modifier ou annuler un rendez-vous"). */
class RdvAnnulationTest extends TestCase
{
    use RefreshDatabase;

    private function fixtures(): array
    {
        $spec = Specialite::create(['nom' => 'Cardiologie']);
        $structure = StructureMedicale::create(['nom' => 'Hôpital Roi Baudouin',
            'adresse' => 'Guédiawaye', 'latitude' => 14.7758, 'longitude' => -17.4056, 'type' => 'hopital']);
        $uMed = User::create(['nom' => 'DIALLO', 'prenom' => 'Moussa', 'role' => 'medecin',
            'email' => 'm@t.sn', 'password' => 'password', 'email_verified_at' => now()]);
        $medecin = Medecin::create(['utilisateur_id' => $uMed->id, 'structure_id' => $structure->id,
            'specialite_id' => $spec->id, 'num_ordre' => 'SN-1', 'valide' => true]);
        $uPat = User::create(['nom' => 'GUEYE', 'prenom' => 'Samba', 'role' => 'patient',
            'email' => 'p@t.sn', 'password' => 'password', 'email_verified_at' => now()]);
        $patient = Patient::create(['utilisateur_id' => $uPat->id]);
        $rdv = RendezVous::create(['patient_id' => $patient->id, 'medecin_id' => $medecin->id,
            'date_heure' => Carbon::tomorrow()->setTime(10, 0), 'statut' => 'CONFIRME']);

        return [$uPat, $patient, $medecin, $rdv];
    }

    public function test_le_patient_peut_annuler_son_propre_rdv(): void
    {
        [$uPat, , , $rdv] = $this->fixtures();

        $this->actingAs($uPat)
            ->delete(route('rdv.annuler', $rdv))
            ->assertRedirect();

        $this->assertSame('ANNULE', $rdv->fresh()->statut);
    }

    public function test_un_patient_ne_peut_pas_annuler_le_rdv_dun_autre(): void
    {
        [, , , $rdv] = $this->fixtures();
        $autre = User::create(['nom' => 'NDIAYE', 'prenom' => 'Awa', 'role' => 'patient',
            'email' => 'autre@t.sn', 'password' => 'password', 'email_verified_at' => now()]);
        Patient::create(['utilisateur_id' => $autre->id]);

        $this->actingAs($autre)
            ->delete(route('rdv.annuler', $rdv))
            ->assertForbidden();

        $this->assertSame('CONFIRME', $rdv->fresh()->statut);
    }
}
