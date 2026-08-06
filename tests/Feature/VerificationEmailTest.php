<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Notification, URL};
use Tests\TestCase;

/**
 * Confirmation de l'adresse e-mail à l'inscription.
 *
 * Deux garanties complémentaires :
 *  - le domaine existe réellement (règle de validation email:dns) ;
 *  - la boîte appartient bien à l'utilisateur (lien signé à cliquer).
 */
class VerificationEmailTest extends TestCase
{
    use RefreshDatabase;

    private array $inscription = [
        'prenom' => 'Awa', 'nom' => 'NDIAYE',
        'email' => 'awa.ndiaye.test@gmail.com',
        'password' => 'motdepasse123', 'password_confirmation' => 'motdepasse123',
    ];

    public function test_une_adresse_au_domaine_inexistant_est_refusee(): void
    {
        $this->post(route('register'), [
            ...$this->inscription,
            'email' => 'moi@domaine-qui-nexiste-vraiment-pas-12345.com',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_linscription_envoie_un_lien_de_confirmation(): void
    {
        Notification::fake();

        $this->post(route('register'), $this->inscription)
            ->assertRedirect(route('verification.notice'));

        $user = User::where('email', $this->inscription['email'])->firstOrFail();
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_lespace_personnel_est_inaccessible_tant_que_non_confirme(): void
    {
        Notification::fake();
        $this->post(route('register'), $this->inscription);

        $this->get(route('dashboard'))->assertRedirect(route('verification.notice'));
    }

    public function test_le_lien_signe_confirme_ladresse(): void
    {
        Notification::fake();
        $this->post(route('register'), $this->inscription);
        $user = User::where('email', $this->inscription['email'])->firstOrFail();

        $lien = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id, 'hash' => sha1($user->email),
        ]);

        $this->actingAs($user)->get($lien)->assertRedirect(route('dashboard'));

        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->actingAs($user->fresh())->get(route('dashboard'))->assertOk();
    }

    public function test_changer_dadresse_annule_la_confirmation(): void
    {
        Notification::fake();
        $u = User::create([
            'nom' => 'GUEYE', 'prenom' => 'Samba', 'role' => 'patient',
            'email' => 'ancienne.adresse.test@gmail.com', 'password' => 'password',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($u)->put(route('profil.update'), [
            'prenom' => 'Samba', 'nom' => 'GUEYE',
            'email' => 'nouvelle.adresse.test@gmail.com',
        ])->assertRedirect(route('verification.notice'));

        $this->assertNull($u->fresh()->email_verified_at);
        Notification::assertSentTo($u->fresh(), VerifyEmail::class);
    }
}
