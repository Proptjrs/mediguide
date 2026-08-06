<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Hash, Notification};
use Tests\TestCase;

/**
 * Réinitialisation du mot de passe par e-mail.
 *
 * Sans ce parcours, un utilisateur ayant perdu son mot de passe serait
 * définitivement bloqué : seul le titulaire de la boîte peut reprendre la main.
 */
class MotDePasseOublieTest extends TestCase
{
    use RefreshDatabase;

    private function utilisateur(): User
    {
        return User::create([
            'nom' => 'GUEYE', 'prenom' => 'Samba', 'role' => 'patient',
            'email' => 'samba@gmail.com', 'password' => 'ancienmotdepasse',
            'email_verified_at' => now(),
        ]);
    }

    public function test_un_lien_est_envoye_a_ladresse_connue(): void
    {
        Notification::fake();
        $u = $this->utilisateur();

        $this->post(route('password.email'), ['email' => 'samba@gmail.com'])
            ->assertRedirect();

        Notification::assertSentTo($u, ResetPassword::class);
    }

    /** On ne révèle jamais qu'une adresse est inconnue (énumération de comptes). */
    public function test_une_adresse_inconnue_donne_la_meme_reponse(): void
    {
        Notification::fake();

        $this->post(route('password.email'), ['email' => 'inconnu@gmail.com'])
            ->assertRedirect()
            ->assertSessionHas('ok');

        Notification::assertNothingSent();
    }

    public function test_le_jeton_permet_de_changer_le_mot_de_passe(): void
    {
        Notification::fake();
        $u = $this->utilisateur();
        $this->post(route('password.email'), ['email' => $u->email]);

        $jeton = null;
        Notification::assertSentTo($u, ResetPassword::class, function ($notification) use (&$jeton) {
            $jeton = $notification->token;

            return true;
        });

        $this->post(route('password.update'), [
            'token' => $jeton,
            'email' => $u->email,
            'password' => 'nouveaumotdepasse',
            'password_confirmation' => 'nouveaumotdepasse',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('nouveaumotdepasse', $u->fresh()->password));
    }

    public function test_un_jeton_invalide_est_refuse(): void
    {
        $u = $this->utilisateur();

        $this->post(route('password.update'), [
            'token' => 'jeton-invente',
            'email' => $u->email,
            'password' => 'nouveaumotdepasse',
            'password_confirmation' => 'nouveaumotdepasse',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('ancienmotdepasse', $u->fresh()->password));
    }
}
