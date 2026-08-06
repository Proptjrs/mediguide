<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Réinitialisation du mot de passe par e-mail (« mot de passe oublié »).
 *
 * Sans ce parcours, un utilisateur ayant perdu son mot de passe serait
 * définitivement bloqué. Le lien envoyé est signé et valable une heure ; il
 * n'est adressé qu'à l'adresse enregistrée, ce qui garantit que seul le
 * titulaire de la boîte peut reprendre la main sur le compte.
 */
class MotDePasseOublieController extends Controller
{
    public function demander()
    {
        return view('auth.mot-de-passe-oublie');
    }

    /**
     * On répond toujours la même chose, que l'adresse existe ou non : révéler
     * qu'une adresse est inconnue permettrait d'énumérer les comptes.
     */
    public function envoyerLien(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        Password::sendResetLink($request->only('email'));

        return back()->with('ok',
            'Si un compte existe pour cette adresse, un lien de réinitialisation vient d\'y être envoyé.');
    }

    public function formulaire(Request $request, string $token)
    {
        return view('auth.reinitialiser-mot-de-passe', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function reinitialiser(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $statut = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password) {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($statut !== Password::PasswordReset) {
            throw ValidationException::withMessages([
                'email' => 'Ce lien de réinitialisation est invalide ou a expiré. Demandez-en un nouveau.',
            ]);
        }

        return redirect()->route('login')
            ->with('ok', 'Mot de passe modifié — vous pouvez vous connecter.');
    }
}
