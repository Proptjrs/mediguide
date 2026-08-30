<?php

namespace App\Http\Controllers;

use App\Services\GeolocService;
use Illuminate\Http\Request;

class OrientationController extends Controller
{
    public function accueil()
    {
        return view('accueil');
    }

    /** F3 — carte Leaflet + structures triées par Haversine. */
    public function resultats(Request $request, GeolocService $geoloc)
    {
        $lat = (float) $request->query('lat', 14.7712);          // défaut : Golf Sud
        $lng = (float) $request->query('lng', -17.4098);
        $spec = $request->query('spec', 'Médecine Générale');

        return view('resultats', [
            'spec' => $spec, 'lat' => $lat, 'lng' => $lng,
            'structures' => $geoloc->structuresProches($lat, $lng, $spec),
        ]);
    }

    /** F2 — écran urgence SAMU 15 / Pompiers 18. */
    public function urgence(Request $request)
    {
        return view('urgence', ['score' => (int) $request->query('score', 0)]);
    }
}
