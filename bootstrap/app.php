<?php

use App\Http\Middleware\{CheckRole, EmpecherMiseEnCache};
use Illuminate\Foundation\Application;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Notifications\{ResetPassword, VerifyEmail};
use Illuminate\Foundation\Configuration\{Exceptions, Middleware};
use Illuminate\Notifications\Messages\MailMessage;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias(['role' => CheckRole::class]);          // chap. 4.2.2
        $middleware->redirectGuestsTo('/connexion');

        // Toutes les pages web portent un formulaire avec jeton CSRF : on interdit
        // leur mise en cache pour éviter les « 419 Page Expired » sur jeton périmé.
        $middleware->appendToGroup('web', EmpecherMiseEnCache::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Sans ce message, le visiteur qui ouvre le questionnaire se retrouve sur
        // la page de connexion sans savoir ce qui s'est passé.
        $exceptions->render(function (AuthenticationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Authentification requise.'], 401);
            }

            return redirect()->guest(route('login'))
                ->with('erreur', 'Connectez-vous pour accéder à cette page.');
        });
    })
    ->booted(function () {
        // E-mail de réinitialisation du mot de passe, en français.
        ResetPassword::toMailUsing(function (object $notifiable, string $token) {
            return (new MailMessage)
                ->subject('Réinitialisation de votre mot de passe — MediGuide')
                ->greeting("Bonjour {$notifiable->prenom},")
                ->line('Vous avez demandé à réinitialiser le mot de passe de votre compte MediGuide.')
                ->action('Choisir un nouveau mot de passe',
                    route('password.reset', ['token' => $token, 'email' => $notifiable->email]))
                ->line('Ce lien expire dans ' . config('auth.passwords.users.expire', 60) . ' minutes.')
                ->line('Si vous n\'êtes pas à l\'origine de cette demande, ignorez ce message : votre mot de passe reste inchangé.')
                ->salutation('— L\'équipe MediGuide');
        });

        // E-mail de confirmation d'adresse, en français et aux couleurs du projet.
        VerifyEmail::toMailUsing(function (object $notifiable, string $url) {
            return (new MailMessage)
                ->subject('Confirmez votre adresse — MediGuide')
                ->greeting("Bonjour {$notifiable->prenom},")
                ->line('Une dernière étape avant d\'accéder à votre espace MediGuide : confirmez que cette adresse e-mail est bien la vôtre.')
                ->action('Confirmer mon adresse', $url)
                ->line('C\'est à cette adresse que vous recevrez la confirmation de vos rendez-vous et les rappels la veille.')
                ->line('Si vous n\'êtes pas à l\'origine de cette inscription, ignorez simplement ce message.')
                ->salutation('— L\'équipe MediGuide');
        });
    })
    ->create();
