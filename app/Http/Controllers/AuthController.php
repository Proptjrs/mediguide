<?php

namespace App\Http\Controllers;

use App\Support\Courriel;
use App\Models\{Medecin, Patient, Specialite, StructureMedicale, User};
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Hash};
use Illuminate\Validation\ValidationException;

/** Authentification (mémoire, chap. 4.2.2) : inscription, connexion, rôles. */
class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Identifiants incorrects.',
            ]);
        }
        abort_unless($request->user()->actif, 403, 'Compte suspendu.');
        if ($request->user()->role === 'medecin' && ! $request->user()->medecin?->valide) {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => 'Votre inscription est en attente de vérification par un administrateur.',
            ]);
        }
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    /** UC-P1 : inscription patient (les médecins sont créés puis validés par l'admin). */
    public function register(Request $request)
    {
        $data = $request->validate([
            'prenom' => 'required|string|max:100',
            'nom' => 'required|string|max:100',
            // email:dns vérifie que le domaine existe réellement (enregistrement MX),
            // ce qui écarte les adresses inventées type « moi@nimportequoi.com ».
            // L'appartenance de la boîte est ensuite prouvée par le lien de confirmation.
            'email' => 'required|email:rfc|unique:users,email',
            'telephone' => 'nullable|string|max:20',
            'date_naissance' => 'nullable|date|before:today',
            'sexe' => 'nullable|in:F,M',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'prenom' => $data['prenom'], 'nom' => $data['nom'],
                'email' => $data['email'], 'telephone' => $data['telephone'] ?? null,
                'password' => $data['password'], 'role' => 'patient',
            ]);
            $patient = Patient::create([
                'utilisateur_id' => $user->id,
                'date_naissance' => $data['date_naissance'] ?? null,
                'sexe' => $data['sexe'] ?? null,
            ]);

            return $user;
        });

        event(new Registered($user));      // déclenche l'envoi du lien de confirmation
        Auth::login($user);

        return redirect()->route('verification.notice')
            ->with('ok', 'Bienvenue ' . $user->prenom . ' ! Confirmez votre adresse pour accéder à votre espace.');
    }

    /** Page « confirmez votre adresse » affichée tant que le lien n'est pas cliqué. */
    public function noticeVerification(Request $request)
    {
        return $request->user()->hasVerifiedEmail()
            ? redirect()->route('dashboard')
            : view('auth.verifier-email');
    }

    /** Cible du lien signé reçu par e-mail : marque l'adresse comme confirmée. */
    public function verifierEmail(EmailVerificationRequest $request)
    {
        if (! $request->user()->hasVerifiedEmail()) {
            $request->fulfill();                                    // déclenche l'événement Verified
        }

        return redirect()->route('dashboard')
            ->with('ok', 'Adresse confirmée — vous recevrez désormais vos rendez-vous par e-mail.');
    }

    public function renvoyerVerification(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        Courriel::tenter(
            fn () => $request->user()->sendEmailVerificationNotification(),
            "lien de confirmation d'adresse"
        );

        return back()->with('ok', 'Un nouveau lien vient de vous être envoyé.');
    }

    public function showRegisterMedecin()
    {
        return view('auth.register-medecin', [
            'structures' => StructureMedicale::orderBy('nom')->get(),
            'specialites' => Specialite::orderBy('nom')->get(),
        ]);
    }

    /** Inscription médecin — le compte reste en attente (`valide = false`) jusqu'à vérification
     *  du numéro d'Ordre par l'administrateur (chap. 2 et 3 du mémoire, UC-A2). */
    public function registerMedecin(Request $request)
    {
        $data = $request->validate([
            'prenom' => 'required|string|max:100',
            'nom' => 'required|string|max:100',
            'email' => 'required|email:rfc|unique:users,email',
            'structure_id' => 'required|exists:structures_medicales,id',
            'specialite_id' => 'required|exists:specialites,id',
            'num_ordre' => 'required|string|max:50|unique:medecins,num_ordre',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'prenom' => $data['prenom'], 'nom' => $data['nom'],
                'email' => $data['email'], 'password' => $data['password'], 'role' => 'medecin',
            ]);
            Medecin::create([
                'utilisateur_id' => $user->id,
                'structure_id' => $data['structure_id'],
                'specialite_id' => $data['specialite_id'],
                'num_ordre' => $data['num_ordre'],
                'valide' => false,
            ]);

            return $user;
        });

        // Le médecin franchit DEUX barrières indépendantes : confirmer son adresse
        // (elle lui appartient) et voir son numéro d'Ordre validé par l'admin
        // (il est bien médecin). Le lien de confirmation part donc dès maintenant.
        event(new Registered($user));

        return redirect()->route('login')->with('ok',
            'Inscription transmise. Confirmez votre adresse via l\'e-mail que nous venons de vous envoyer, '
            . 'puis attendez la validation de votre numéro d\'Ordre par un administrateur.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('accueil');
    }
}
