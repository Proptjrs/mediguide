<?php

namespace Tests\Feature;

use App\Models\{Medecin, Patient, RendezVous, Specialite, StructureMedicale, User};
use App\Notifications\Channels\TwilioChannel;
use App\Notifications\RdvConfirme;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Http, Log};
use Tests\TestCase;

/**
 * Canal SMS (mémoire, chap. 4.2.6 et section 8).
 *
 * Le SMS est implémenté et testé ; sa mise en production reste conditionnée à
 * l'obtention d'un compte opérateur commercial. Ces tests couvrent les trois
 * comportements du canal sans jamais appeler l'API réelle.
 */
class SmsTwilioTest extends TestCase
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
            'email' => 'p@t.sn', 'password' => 'password', 'telephone' => '+221770000000']);
        $patient = Patient::create(['utilisateur_id' => $uPat->id]);

        return RendezVous::create(['patient_id' => $patient->id, 'medecin_id' => $medecin->id,
            'date_heure' => Carbon::tomorrow()->setTime(10, 0), 'statut' => 'CONFIRME']);
    }

    /** Sans compte opérateur (état documenté en section 8) : le SMS est journalisé, pas envoyé. */
    public function test_sans_compte_twilio_le_sms_est_seulement_journalise(): void
    {
        config(['services.twilio.sid' => null]);
        Http::fake();
        Log::spy();

        $rdv = $this->rdv();
        (new TwilioChannel)->send($rdv->patient->utilisateur, new RdvConfirme($rdv));

        Http::assertNothingSent();
        Log::shouldHaveReceived('info')
            ->withArgs(fn ($m) => str_contains($m, '[SMS simulé]') && str_contains($m, '+221770000000'))
            ->once();
    }

    /** Avec un compte configuré : l'API est appelée avec le bon destinataire. */
    public function test_avec_un_compte_twilio_lapi_est_appelee(): void
    {
        config([
            'services.twilio.sid' => 'AC_test',
            'services.twilio.token' => 'token_test',
            'services.twilio.from' => '+15550000000',
        ]);
        Http::fake([
            'api.twilio.com/*' => Http::response(['sid' => 'SM_abc'], 201),
        ]);

        $rdv = $this->rdv();
        (new TwilioChannel)->send($rdv->patient->utilisateur, new RdvConfirme($rdv));

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'AC_test/Messages.json')
                && $request['To'] === '+221770000000'
                && ! empty($request['Body']);
        });
    }

    /** Un refus de l'API ne doit pas être silencieux : il est relancé pour que la file réessaie. */
    public function test_un_echec_twilio_est_relance(): void
    {
        config(['services.twilio.sid' => 'AC_test', 'services.twilio.token' => 'token_test']);
        Http::fake([
            'api.twilio.com/*' => Http::response(
                ['code' => 21608, 'message' => 'Numéro non vérifié (compte trial)'], 400
            ),
        ]);

        $rdv = $this->rdv();

        $this->expectException(\RuntimeException::class);
        (new TwilioChannel)->send($rdv->patient->utilisateur, new RdvConfirme($rdv));
    }

    /** Un patient sans téléphone ne déclenche aucun appel. */
    public function test_sans_numero_aucun_appel(): void
    {
        config(['services.twilio.sid' => 'AC_test']);
        Http::fake();

        $rdv = $this->rdv();
        $rdv->patient->utilisateur->update(['telephone' => null]);

        (new TwilioChannel)->send($rdv->patient->utilisateur->fresh(), new RdvConfirme($rdv));

        Http::assertNothingSent();
    }
}
