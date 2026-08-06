<?php

namespace Tests\Feature;

use App\Models\{Disponibilite, Medecin, Patient, Specialite, StructureMedicale, User};
use App\Services\RdvService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** Test anti-doublon du mémoire (chap. 4.6.1) : "prise de RDV disponible et indisponible". */
class RdvAntiDoublonTest extends TestCase
{
    use RefreshDatabase;

    private function fixtures(): array
    {
        $spec = Specialite::create(['nom' => 'Cardiologie']);
        $structure = StructureMedicale::create(['nom' => 'Hôpital Roi Baudouin',
            'adresse' => 'Guédiawaye', 'latitude' => 14.7758, 'longitude' => -17.4056, 'type' => 'hopital']);
        $uMed = User::create(['nom' => 'DIALLO', 'prenom' => 'Moussa', 'role' => 'medecin',
            'email' => 'm@t.sn', 'password' => 'password']);
        $medecin = Medecin::create(['utilisateur_id' => $uMed->id, 'structure_id' => $structure->id,
            'specialite_id' => $spec->id, 'num_ordre' => 'SN-1', 'valide' => true]);
        $uPat = User::create(['nom' => 'GUEYE', 'prenom' => 'Samba', 'role' => 'patient',
            'email' => 'p@t.sn', 'password' => 'password']);
        $patient = Patient::create(['utilisateur_id' => $uPat->id]);

        return [$medecin, $patient];
    }

    public function test_un_creneau_libre_peut_etre_reserve(): void
    {
        Notification::fake();
        [$medecin, $patient] = $this->fixtures();

        $rdv = app(RdvService::class)->creerRendezVous(
            $patient, $medecin, Carbon::tomorrow()->setTime(10, 0));

        $this->assertSame('CONFIRME', $rdv->statut);
        $this->assertDatabaseCount('rendez_vous', 1);
    }

    public function test_un_creneau_deja_pris_est_refuse(): void
    {
        Notification::fake();
        [$medecin, $patient] = $this->fixtures();
        $creneau = Carbon::tomorrow()->setTime(10, 0);

        app(RdvService::class)->creerRendezVous($patient, $medecin, $creneau);

        $this->expectException(\RuntimeException::class);      // anti-doublon
        app(RdvService::class)->creerRendezVous($patient, $medecin, $creneau);
    }
}
