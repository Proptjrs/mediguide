<?php

use App\Http\Controllers\{AuthController, DashboardController, MotDePasseOublieController,
    OrientationController, ProfilController, RendezVousController};
use App\Http\Controllers\Admin\{PlanningController, StructureController, UtilisateurController};
use App\Http\Controllers\Medecin\IndisponibiliteController;
use App\Http\Controllers\Secretaire\AgendaController;
use App\Livewire\AssistantMedical;
use App\Livewire\QuestionnaireOrientation;
use Illuminate\Support\Facades\Route;

/* ---- Page d'accueil ----
 | Seule page ouverte à tous : elle présente la plateforme et conduit à
 | l'inscription ou à la connexion. */
Route::get('/', [OrientationController::class, 'accueil'])->name('accueil');

/* ---- Orientation : ouverte à tous ----
 | S'orienter ne demande pas de compte. Un patient qui ne sait pas vers quel
 | service se diriger doit pouvoir le découvrir immédiatement, sans barrière —
 | c'est le cœur du service rendu, et l'exiger d'emblée écarterait précisément
 | ceux que la plateforme cherche à aider. Le questionnaire ne conserve alors
 | aucun rattachement : le résultat n'appartient à personne.
 |
 | Le compte devient nécessaire au moment de réserver, c'est-à-dire dès qu'un
 | rendez-vous doit porter un nom. */
Route::get('/orientation', QuestionnaireOrientation::class)->name('orientation');           // F1
Route::get('/assistant', AssistantMedical::class)->name('assistant');                       // F8
Route::get('/urgence', [OrientationController::class, 'urgence'])->name('urgence');         // F2

/* ---- Ce qui suppose une identification ----
 | Les structures proposées et les créneaux d'un médecin ne sont consultables
 | qu'une fois connecté : on entre là dans la prise de rendez-vous. */
Route::middleware('auth')->group(function () {
    Route::get('/resultats', [OrientationController::class, 'resultats'])->name('resultats'); // F3
    Route::get('/medecin/{medecin}/calendrier', [RendezVousController::class, 'calendrier'])
        ->name('calendrier');                                                              // F4
});

/* ---- Authentification (chap. 4.2.2) ---- */
Route::middleware('guest')->group(function () {
    Route::get('/connexion', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/connexion', [AuthController::class, 'login']);
    Route::get('/inscription', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/inscription', [AuthController::class, 'register']);
    Route::get('/inscription-medecin', [AuthController::class, 'showRegisterMedecin'])->name('register.medecin');
    Route::post('/inscription-medecin', [AuthController::class, 'registerMedecin']);

    // Mot de passe oublié : le lien de réinitialisation part vers l'adresse
    // enregistrée, seul le titulaire de la boîte peut donc reprendre la main.
    Route::get('/mot-de-passe-oublie', [MotDePasseOublieController::class, 'demander'])->name('password.request');
    Route::post('/mot-de-passe-oublie', [MotDePasseOublieController::class, 'envoyerLien'])
        ->middleware('throttle:6,1')->name('password.email');
    Route::get('/reinitialiser/{token}', [MotDePasseOublieController::class, 'formulaire'])->name('password.reset');
    Route::post('/reinitialiser', [MotDePasseOublieController::class, 'reinitialiser'])->name('password.update');
});

/* ---- Confirmation de l'adresse e-mail ----
 | L'adresse saisie à l'inscription doit être confirmée : c'est la seule preuve
 | qu'elle existe ET qu'elle appartient bien à l'utilisateur. Tant que ce n'est
 | pas fait, l'espace personnel reste inaccessible (middleware "verified"). */
Route::middleware('auth')->group(function () {
    // Accessible sans confirmation : sinon un compte non confirmé ne pourrait
    // même pas se déconnecter.
    Route::post('/deconnexion', [AuthController::class, 'logout'])->name('logout');

    Route::get('/email/confirmation', [AuthController::class, 'noticeVerification'])
        ->name('verification.notice');

    Route::get('/email/confirmer/{id}/{hash}', [AuthController::class, 'verifierEmail'])
        ->middleware(['signed', 'throttle:6,1'])->name('verification.verify');

    Route::post('/email/renvoyer', [AuthController::class, 'renvoyerVerification'])
        ->middleware('throttle:6,1')->name('verification.send');

    // Profil accessible sans confirmation : il faut pouvoir corriger une adresse
    // mal saisie. Changer d'adresse annule la confirmation et renvoie un lien.
    Route::get('/profil', [ProfilController::class, 'show'])->name('profil');
    Route::put('/profil', [ProfilController::class, 'update'])->name('profil.update');
    Route::put('/profil/mot-de-passe', [ProfilController::class, 'updatePassword'])->name('profil.password');
});

/* ---- Espace connecté (adresse confirmée) ---- */
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Renseignements de santé du patient : ils appartiennent à son profil et
    // ne sont visibles que de lui.
    Route::put('/profil/sante', [ProfilController::class, 'updatePatient'])
        ->middleware('role:patient')->name('profil.sante');

    Route::post('/medecin/{medecin}/reserver', [RendezVousController::class, 'reserver'])
        ->middleware('role:patient')->name('rdv.reserver');                              // F4
    Route::delete('/rdv/{rendezVous}/annuler', [RendezVousController::class, 'annuler'])
        ->middleware('role:patient')->name('rdv.annuler');

    // Clôture du rendez-vous par le médecin : présent ou non présenté.
    Route::patch('/rdv/{rendezVous}/honorer', [RendezVousController::class, 'honorer'])
        ->middleware('role:medecin')->name('rdv.honorer');
    Route::patch('/rdv/{rendezVous}/absent', [RendezVousController::class, 'absent'])
        ->middleware('role:medecin')->name('rdv.absent');

    Route::post('/admin/medecin/{medecin}/valider', [DashboardController::class, 'validerMedecin'])
        ->middleware('role:admin')->name('admin.valider');                               // UC-A2

    // Plannings : définis par l'admin, pas par le médecin (chap. 3)
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/medecin/{medecin}/planning', [PlanningController::class, 'edit'])->name('planning.edit');
        Route::post('/medecin/{medecin}/planning', [PlanningController::class, 'store'])->name('planning.store');
        Route::delete('/planning/{disponibilite}', [PlanningController::class, 'destroy'])->name('planning.destroy');
        // Gestion des comptes utilisateurs (chap. 2 : créer, modifier, suspendre, supprimer)
        Route::get('/utilisateurs', [UtilisateurController::class, 'index'])->name('utilisateurs.index');
        Route::get('/utilisateurs/creer', [UtilisateurController::class, 'create'])->name('utilisateurs.create');
        Route::post('/utilisateurs', [UtilisateurController::class, 'store'])->name('utilisateurs.store');
        Route::get('/utilisateurs/{utilisateur}/modifier', [UtilisateurController::class, 'edit'])->name('utilisateurs.edit');
        Route::put('/utilisateurs/{utilisateur}', [UtilisateurController::class, 'update'])->name('utilisateurs.update');
        Route::patch('/utilisateurs/{utilisateur}/activation', [UtilisateurController::class, 'basculerActivation'])
            ->name('utilisateurs.activation');
        Route::delete('/utilisateurs/{utilisateur}', [UtilisateurController::class, 'destroy'])->name('utilisateurs.destroy');

        // Gestion des structures médicales référencées (chap. 2)
        Route::get('/structures', [StructureController::class, 'index'])->name('structures.index');
        Route::get('/structures/creer', [StructureController::class, 'create'])->name('structures.create');
        Route::post('/structures', [StructureController::class, 'store'])->name('structures.store');
        Route::get('/structures/{structure}/modifier', [StructureController::class, 'edit'])->name('structures.edit');
        Route::put('/structures/{structure}', [StructureController::class, 'update'])->name('structures.update');
        Route::delete('/structures/{structure}', [StructureController::class, 'destroy'])->name('structures.destroy');
    });

    // Indisponibilités ponctuelles : déclarées par le médecin (exceptions au planning de base)
    Route::middleware('role:medecin')->prefix('medecin')->name('medecin.')->group(function () {
        Route::post('/indisponibilite', [IndisponibiliteController::class, 'store'])->name('indisponibilite.store');
        Route::delete('/indisponibilite/{disponibilite}', [IndisponibiliteController::class, 'destroy'])
            ->name('indisponibilite.destroy');
    });

    // La secrétaire tient l'agenda du médecin qu'elle assiste : elle déclare ses
    // absences et libère ses créneaux, sans accéder aux comptes ni aux structures.
    Route::middleware('role:secretaire')->prefix('secretaire')->name('secretaire.')->group(function () {
        Route::get('/agenda', [AgendaController::class, 'index'])->name('agenda');
        Route::post('/indisponibilite', [AgendaController::class, 'store'])->name('indisponibilite.store');
        Route::delete('/indisponibilite/{disponibilite}', [AgendaController::class, 'destroy'])
            ->name('indisponibilite.destroy');
    });
});
