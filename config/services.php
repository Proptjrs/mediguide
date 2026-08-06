<?php

return [
    'twilio' => [
        'sid' => env('TWILIO_SID'),
        'token' => env('TWILIO_TOKEN'),
        'from' => env('TWILIO_FROM'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Services OpenStreetMap (mémoire, chap. 4.2.4 et section 8)
    |--------------------------------------------------------------------------
    | Par défaut : serveurs publics OSM (usage équitable, sans garantie de SLA).
    | En posant OSRM_URL / NOMINATIM_URL, la plateforme bascule sur des
    | instances auto-hébergées (voir docker-compose.yml, profil "osm"), ce qui
    | lève la dépendance aux serveurs publics documentée en section 8.
    |
    | Note : on utilise `?:` et non le 2e argument de env(), car une clé présente
    | mais vide dans le .env (OSRM_URL=) renvoie une chaîne vide, pas le défaut.
    */
    'osrm' => [
        'url' => rtrim(env('OSRM_URL') ?: 'https://router.project-osrm.org', '/'),
    ],

    'nominatim' => [
        'url' => rtrim(env('NOMINATIM_URL') ?: 'https://nominatim.openstreetmap.org', '/'),
        'user_agent' => env('NOMINATIM_USER_AGENT') ?: 'MediGuide/1.0 (memoire ISI)',
    ],
];
