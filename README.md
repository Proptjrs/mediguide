# MediGuide — Plateforme d'orientation et de prise de rendez-vous médicaux
### Cas du district sanitaire de Guédiawaye · Mémoire L3GL — GUEYE Samba, ISI Dakar

Stack (conforme au mémoire, chap. 3.2.10) : **Laravel 11 (PHP 8.2+) · Blade · Livewire 3 ·
Bootstrap 5.3 · MySQL 8 · Redis · Leaflet/OpenStreetMap · Haversine/OSRM/Nominatim · Docker**.

> **Windows** — ne pas définir `PHP_CLI_SERVER_WORKERS` dans le `.env` : le mode
> multi-worker du serveur intégré de PHP n'est pas supporté sous Windows et fige
> `php artisan serve` (requêtes de plusieurs minutes, « Maximum execution time exceeded »).

## Installation

```bash
# 1. Créer le squelette Laravel 11 puis ajouter Livewire
composer create-project laravel/laravel:^11.0 mediguide
cd mediguide
composer require livewire/livewire:^3.0 laravel/sanctum:^4.0
composer require laravel/dusk:^8.0 --dev

# 2. Copier les dossiers de CE paquet par-dessus le projet
#    (app/, config/, database/, resources/, routes/, tests/, docker/,
#     bootstrap/app.php, .env.example, docker-compose.yml)

# 3. Configurer
cp .env.example .env
php artisan key:generate

# 4a. Sans Docker (MySQL local — XAMPP écoute sur le port 3307)
php artisan migrate --seed
php artisan serve        # http://localhost:8000

# Dans un SECOND terminal — indispensable : sans lui, aucune notification
# (e-mail, SMS) n'est envoyée, les jobs restent en attente dans la table "jobs".
php artisan queue:work

# 4b. Avec Docker (chap. 4.5) — MySQL, Redis, nginx et la file d'attente inclus
docker compose up -d --build
docker compose exec app php artisan migrate --seed
#    L'entrypoint installe les dépendances, crée le .env et génère APP_KEY au besoin.
```

## Hébergement local d'OSRM et Nominatim (section 8)

Par défaut la plateforme utilise les serveurs publics OpenStreetMap. Pour supprimer
cette dépendance externe, deux instances locales sont fournies (profil `osm`) :

```bash
# 1. Préparation unique du graphe routier (Sénégal + Gambie : ~100 Mo téléchargés,
#    ~450 Mo de graphe, ~5 min de calcul). Utiliser "run" et non "up".
docker compose --profile osm-prep run --rm osrm-prep

# 2. Démarrer OSRM local puis l'application branchée dessus
docker compose --profile osm up -d osrm
OSRM_URL=http://osrm:5000 docker compose up -d

# 3. Géocodage local (Nominatim) — import plus lourd, compter 30 à 60 min
docker compose --profile osm up -d nominatim
NOMINATIM_URL=http://nominatim:8080 docker compose up -d
```

Sans ces variables, `config/services.php` retombe sur les serveurs publics ; le calcul
des distances reste de toute façon en PHP pur (Haversine), et `GeolocService` bascule
automatiquement sur une estimation locale si OSRM est injoignable.

## E-mail : envoi réel

Le canal e-mail sort vraiment par SMTP. Trois modes (détaillés dans `.env.example`) :

| `MAIL_MAILER` | Effet |
|---|---|
| `log` (défaut) | **Aucun envoi** — le message est écrit dans `storage/logs/laravel.log`. |
| `smtp` + Mailpit | Envoi SMTP réel, consultable sur **http://localhost:8025** (fourni par `docker compose up`). |
| `smtp` + fournisseur | Livraison dans une **vraie boîte** (Gmail ou Brevo). |

Configuration retenue pour la démonstration — **Gmail** :

```
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=<adresse Gmail complète>
MAIL_PASSWORD=<mot de passe d'application, 16 caractères sans espaces>
```

Gmail refuse le mot de passe habituel du compte : il faut activer la validation en
deux étapes puis générer un **mot de passe d'application** sur
<https://myaccount.google.com/apppasswords>. Ce secret reste dans le `.env` local,
que `.gitignore` exclut — il n'est jamais versionné ni livré.

Rappel : `php artisan queue:work` doit tourner, sinon les notifications restent
en attente dans la table `jobs` et rien ne part.

## Tests et audits

```bash
php artisan test                 # PHPUnit — 57 tests (unitaires + feature)
php artisan dusk                 # E2E navigateur — 11 parcours
```

Trois commandes vérifient la conformité au cahier des charges (section 13) :

```bash
php artisan audit:routes && php artisan audit:logique && php artisan audit:crud
```

Dusk utilise Chrome par défaut (`php artisan dusk:chrome-driver`). Sur un poste sans
Chrome, on peut piloter Edge en ajoutant à `.env.dusk.local` :

```
DUSK_BROWSER=edge
DUSK_DRIVER_URL=http://localhost:9515
DUSK_BROWSER_BINARY="C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe"
```

puis en lançant `msedgedriver --port=9515` (version identique à celle d'Edge).

## Correspondance avec le mémoire
| Fonctionnalité | Fichier principal |
|---|---|
| F1 Questionnaire 5 étapes | `app/Livewire/QuestionnaireOrientation.php` |
| F2 Détection d'urgence (score ≥ 7) | `app/Services/QuestionnaireService.php` |
| F3 Géolocalisation (Haversine/OSRM/Nominatim) | `app/Services/GeolocService.php` |
| F4 RDV anti-doublon (`lockForUpdate`) | `app/Services/RdvService.php` |
| F5 Notifications mail + database + SMS Twilio | `app/Notifications/` + `Channels/TwilioChannel.php` + rappel J-1 (`routes/console.php`) |
| F9 Compte-rendu de consultation | `app/Http/Controllers/ConsultationController.php` |
| Authentification complète (login/inscription) | `app/Http/Controllers/AuthController.php` + `resources/views/auth/` |
| API REST (Postman, chap. 4.6.2) | `routes/api.php` + `app/Http/Controllers/Api/ApiController.php` |
| DossierPolicy (chap. 4.2.7) | `app/Policies/DossierPolicy.php` |
| Tests E2E Dusk (chap. 4.6.3) | `tests/Browser/ParcoursPatientTest.php` |
| CI/CD Tests → Build → Deploy SSH (chap. 4.5.2) | `.github/workflows/ci.yml` |
| F6 Dossier patient (Policies) | `app/Models/DossierPatient.php` + middleware |
| F7–F12 Rôles | `app/Http/Middleware/CheckRole.php` + dashboards |

Comptes créés par le seeder (`php artisan migrate --seed`) : les adresses figurent dans `database/seeders/DatabaseSeeder.php`, le mot de passe est défini par `DEMO_PASSWORD` dans le `.env`. Ils ne sont volontairement affichés nulle part dans l'interface.

## API REST (testable avec Postman, chap. 4.6.2)
| Méthode | Route | Fonction |
|---|---|---|
| POST | `/api/orientation` | Analyse du questionnaire → spécialité ou urgence |
| GET | `/api/structures?lat=&lng=&spec=` | Structures proches (Haversine + OSRM) |
| GET | `/api/medecin/{id}/creneaux` | Créneaux de la semaine |
| GET | `/api/me` (Sanctum) | Utilisateur authentifié |
