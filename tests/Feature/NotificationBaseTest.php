<?php

namespace Tests\Feature;

use App\Models\{Medecin, Patient, RendezVous, Specialite, StructureMedicale, User};
use App\Notifications\{RappelRdv, RdvConfirme};
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Canal « database » des notifications (mémoire, F5 et table `notifications`
 * de la section 5).
 *
 * Régression couverte : la migration utilisait morphs() (notifiable_id entier)
 * alors que les utilisateurs ont une clé primaire UUID — toute insertion
 * échouait, rendant la table inutilisable.
 */
class NotificationBaseTest extends TestCase
{
    use RefreshDatabase;

    private function rdv(): RendezVous
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

        return RendezVous::create(['patient_id' => $patient->id, 'medecin_id' => $medecin->id,
            'date_heure' => Carbon::tomorrow()->setTime(10, 0), 'statut' => 'CONFIRME']);
    }

    public function test_la_confirmation_est_enregistree_en_base(): void
    {
        $rdv = $this->rdv();
        $utilisateur = $rdv->patient->utilisateur;

        $utilisateur->notifyNow(new RdvConfirme($rdv));

        $this->assertSame(1, DB::table('notifications')->count());
        $notification = $utilisateur->notifications()->first();
        $this->assertNotNull($notification);
        $this->assertSame('RDV confirme', $notification->data['titre']);
        $this->assertSame($rdv->id, $notification->data['rdv_id']);
    }

    public function test_le_rappel_est_enregistre_en_base(): void
    {
        $rdv = $this->rdv();

        $rdv->patient->utilisateur->notifyNow(new RappelRdv($rdv));

        $this->assertSame(1, DB::table('notifications')->count());
        $this->assertSame(
            $rdv->patient->utilisateur->id,
            DB::table('notifications')->value('notifiable_id')
        );
    }

    /** La colonne notifiable_id doit accepter un UUID (et non un entier). */
    public function test_la_colonne_notifiable_id_accepte_un_uuid(): void
    {
        $rdv = $this->rdv();
        $rdv->patient->utilisateur->notifyNow(new RdvConfirme($rdv));

        $id = DB::table('notifications')->value('notifiable_id');
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            (string) $id
        );
    }
}
