<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Comptes de démonstration
    |--------------------------------------------------------------------------
    | Adresses des trois comptes créés par le seeder et proposées en connexion
    | rapide sur la page de connexion.
    |
    | Par défaut : adresses fictives @demo.sn — aucun e-mail ne peut donc partir
    | vers une boîte réelle. Pour recevoir réellement les notifications pendant
    | les tests, définir dans le .env de vraies adresses, par exemple avec le
    | sous-adressage Gmail (tout ce qui suit le « + » est ignoré par Gmail, donc
    | les trois arrivent dans la même boîte tout en restant distinguables) :
    |
    |   DEMO_PATIENT_EMAIL=monadresse+patient@gmail.com
    |   DEMO_MEDECIN_EMAIL=monadresse+medecin@gmail.com
    |   DEMO_ADMIN_EMAIL=monadresse+admin@gmail.com
    |
    | Note : on utilise `?:` et non le 2e argument de env(), car une clé présente
    | mais vide dans le .env (DEMO_PATIENT_EMAIL=) renvoie une chaîne vide.
    */

    'demo' => [
        'patient' => env('DEMO_PATIENT_EMAIL') ?: 'patient@demo.sn',
        'medecin' => env('DEMO_MEDECIN_EMAIL') ?: 'medecin@demo.sn',
        'admin' => env('DEMO_ADMIN_EMAIL') ?: 'admin@demo.sn',
        'secretaire' => env('DEMO_SECRETAIRE_EMAIL') ?: 'secretaire@mediguide.sn',
        'mot_de_passe' => env('DEMO_PASSWORD') ?: 'password',
    ],

];
