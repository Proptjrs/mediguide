<?php

namespace Tests\Feature;

use App\Models\{Medecin, Patient, RendezVous, Specialite, StructureMedicale, User};
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Auth, Notification};
use Tests\TestCase;

/** Gestion des comptes par l'administrateur (chap. 2). */
class GestionComptesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create(['nom' => 'ADMIN', 'prenom' => 'ISI', 'role' => 'admin',
            'email' => 'admin@t.sn', 'password' => 'password', 'email_verified_at' => now()]);
    }

    public function test_ladmin_peut_creer_un_compte_patient(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.utilisateurs.store'), [
                'prenom' => 'Awa', 'nom' => 'NDIAYE', 'email' => 'awa.creee@gmail.com',
                'role' => 'patient', 'password' => 'motdepasse1',
            ])->assertRedirect(route('admin.utilisateurs.index'));

        $u = User::where('email', 'awa.creee@gmail.com')->firstOrFail();
        $this->assertSame('patient', $u->role);
        $this->assertNotNull($u->email_verified_at, 'Un compte créé par l\'admin est déjà confirmé.');
    }

    /** Un médecin ne se crée pas côté admin : il s'inscrit puis est validé. */
    public function test_le_role_medecin_est_refuse_a_la_creation(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.utilisateurs.store'), [
                'prenom' => 'Faux', 'nom' => 'MEDECIN', 'email' => 'faux.medecin@gmail.com',
                'role' => 'medecin', 'password' => 'motdepasse1',
            ])->assertSessionHasErrors('role');
    }

    public function test_suspendre_un_compte_empeche_la_connexion(): void
    {
        $u = User::create(['nom' => 'GUEYE', 'prenom' => 'Samba', 'role' => 'patient',
            'email' => 'samba@gmail.com', 'password' => 'password', 'email_verified_at' => now()]);

        $this->actingAs($this->admin())
            ->patch(route('admin.utilisateurs.activation', $u))->assertRedirect();

        $this->assertFalse($u->fresh()->actif);

        // On quitte la session admin : /connexion est protégée par le middleware "guest".
        Auth::logout();
        $this->flushSession();

        $this->post(route('login'), ['email' => 'samba@gmail.com', 'password' => 'password'])
            ->assertForbidden();
    }

    public function test_ladmin_ne_peut_pas_se_suspendre_lui_meme(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->patch(route('admin.utilisateurs.activation', $admin));

        $this->assertTrue($admin->fresh()->actif);
    }

    public function test_un_medecin_avec_rendez_vous_ne_peut_pas_etre_supprime(): void
    {
        $spec = Specialite::create(['nom' => 'Cardiologie']);
        $structure = StructureMedicale::create(['nom' => 'Hôpital Roi Baudouin',
            'adresse' => 'Guédiawaye', 'latitude' => 14.7758, 'longitude' => -17.4056, 'type' => 'hopital']);
        $uMed = User::create(['nom' => 'DIALLO', 'prenom' => 'Moussa', 'role' => 'medecin',
            'email' => 'moussa@gmail.com', 'password' => 'password', 'email_verified_at' => now()]);
        $medecin = Medecin::create(['utilisateur_id' => $uMed->id, 'structure_id' => $structure->id,
            'specialite_id' => $spec->id, 'num_ordre' => 'SN-1', 'valide' => true]);
        $uPat = User::create(['nom' => 'GUEYE', 'prenom' => 'Samba', 'role' => 'patient',
            'email' => 'p@gmail.com', 'password' => 'password', 'email_verified_at' => now()]);
        $patient = Patient::create(['utilisateur_id' => $uPat->id]);
        RendezVous::create(['patient_id' => $patient->id, 'medecin_id' => $medecin->id,
            'date_heure' => Carbon::tomorrow()->setTime(10, 0), 'statut' => 'CONFIRME']);

        $this->actingAs($this->admin())
            ->delete(route('admin.utilisateurs.destroy', $uMed))->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $uMed->id]);
    }

    public function test_un_patient_ne_peut_pas_gerer_les_comptes(): void
    {
        $u = User::create(['nom' => 'GUEYE', 'prenom' => 'Samba', 'role' => 'patient',
            'email' => 'intrus@gmail.com', 'password' => 'password', 'email_verified_at' => now()]);

        $this->actingAs($u)->get(route('admin.utilisateurs.index'))->assertForbidden();
    }

    /** L'inscription médecin envoie aussi un lien de confirmation d'adresse. */
    public function test_linscription_medecin_envoie_un_lien_de_confirmation(): void
    {
        Notification::fake();
        $structure = StructureMedicale::create(['nom' => 'Hôpital Roi Baudouin',
            'adresse' => 'Guédiawaye', 'latitude' => 14.7758, 'longitude' => -17.4056, 'type' => 'hopital']);
        $spec = Specialite::create(['nom' => 'Cardiologie']);

        $this->post(route('register.medecin'), [
            'prenom' => 'Fatou', 'nom' => 'WADE', 'email' => 'fatou.wade@gmail.com',
            'structure_id' => $structure->id, 'specialite_id' => $spec->id,
            'num_ordre' => 'SN-9999', 'password' => 'motdepasse1', 'password_confirmation' => 'motdepasse1',
        ])->assertRedirect(route('login'));

        $u = User::where('email', 'fatou.wade@gmail.com')->firstOrFail();
        $this->assertNull($u->email_verified_at);
        $this->assertFalse($u->medecin->valide);
        Notification::assertSentTo($u, \Illuminate\Auth\Notifications\VerifyEmail::class);
    }

    public function test_changer_l_adresse_previent_l_ancienne_boite(): void
    {
        \Illuminate\Support\Facades\Notification::fake();

        $admin = User::create(['nom' => 'ADMIN', 'prenom' => 'ISI', 'role' => 'admin',
            'email' => 'admin.previent@gmail.com', 'password' => 'password',
            'email_verified_at' => now()]);
        $cible = User::create(['nom' => 'DIOP', 'prenom' => 'Mariama', 'role' => 'patient',
            'email' => 'mariama.avant@gmail.com', 'password' => 'password',
            'email_verified_at' => now()]);

        $this->actingAs($admin)->put('/admin/utilisateurs/' . $cible->id, [
            'prenom' => 'Mariama', 'nom' => 'DIOP', 'email' => 'mariama.apres@gmail.com',
        ])->assertRedirect(route('admin.utilisateurs.index'));

        // L'ancienne boîte est prévenue : sans cela, un compte pourrait être
        // détourné sans que son titulaire ne le sache jamais.
        \Illuminate\Support\Facades\Notification::assertSentOnDemand(
            \App\Notifications\AdresseModifiee::class,
            fn ($notification, $canaux, $notifiable) =>
                $notifiable->routes['mail'] === 'mariama.avant@gmail.com');

        // Et la nouvelle adresse doit être confirmée avant tout accès.
        $this->assertNull($cible->fresh()->email_verified_at);
    }
}
