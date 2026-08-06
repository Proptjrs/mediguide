<?php

namespace App\Services;

use App\Models\StructureMedicale;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Géolocalisation 100 % libre (mémoire, chap. 4.2.4) :
 * - distances : formule de HAVERSINE en PHP pur (aucun appel réseau) ;
 * - temps de trajet : API publique OSRM, repli Haversine si indisponible ;
 * - géocodage : Nominatim (OpenStreetMap) lors du référencement des structures ;
 * - cache Redis/DB 24 h pour respecter l'usage équitable des services OSM.
 */
class GeolocService
{
    private const RAYON_TERRE_KM = 6371.0;

    public function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * self::RAYON_TERRE_KM * asin(sqrt($a));
    }

    /** Les 5 structures les plus proches proposant la spécialité (F3). */
    public function structuresProches(float $lat, float $lng, string $specialite, int $limit = 5)
    {
        return StructureMedicale::query()
            ->whereHas('medecins', fn ($q) => $q
                ->where('valide', true)
                ->whereHas('specialite', fn ($s) => $s->where('nom', $specialite)))
            ->get()
            ->map(function (StructureMedicale $s) use ($lat, $lng) {
                $s->distance_km = round($this->haversine($lat, $lng, $s->latitude, $s->longitude), 2);
                $s->duree = $this->dureeTrajet($lat, $lng, $s->latitude, $s->longitude, $s->distance_km);

                return $s;
            })
            ->sortBy('distance_km')
            ->take($limit)
            ->values();
    }

    /** Temps de trajet via OSRM (gratuit), avec fallback Haversine. */
    public function dureeTrajet(float $lat1, float $lng1, float $lat2, float $lng2, float $distanceKm): array
    {
        $cle = sprintf('osrm:%.4f,%.4f-%.4f,%.4f', $lat1, $lng1, $lat2, $lng2);

        return Cache::remember($cle, now()->addDay(), function () use ($lat1, $lng1, $lat2, $lng2, $distanceKm) {
            try {
                $base = config('services.osrm.url');
                $rep = Http::timeout(4)->get(
                    "{$base}/route/v1/driving/{$lng1},{$lat1};{$lng2},{$lat2}",
                    ['overview' => 'false']
                );
                if ($rep->ok() && ($duree = $rep->json('routes.0.duration'))) {
                    return [
                        'voiture_min' => (int) ceil($duree / 60),
                        'pied_min' => (int) ceil($distanceKm * 13),
                        'source' => 'osrm',
                    ];
                }
            } catch (\Throwable) {
                // service externe indisponible : on retombe sur l'estimation locale
            }

            return [                                       // fallback : vitesse moyenne urbaine
                'voiture_min' => max(2, (int) ceil($distanceKm * 3)),
                'pied_min' => max(3, (int) ceil($distanceKm * 13)),
                'source' => 'haversine',
            ];
        });
    }

    /** Adresse -> coordonnées GPS via Nominatim (usage admin, UC-A3). */
    public function geocoder(string $adresse): ?array
    {
        $cle = 'nominatim:' . md5($adresse);

        return Cache::remember($cle, now()->addDay(), function () use ($adresse) {
            $rep = Http::timeout(6)
                ->withHeaders(['User-Agent' => config('services.nominatim.user_agent')])
                ->get(config('services.nominatim.url') . '/search', [
                    'q' => $adresse . ', Guédiawaye, Sénégal',
                    'format' => 'json', 'limit' => 1,
                ]);
            $hit = $rep->json('0');

            return $hit ? ['lat' => (float) $hit['lat'], 'lng' => (float) $hit['lon']] : null;
        });
    }
}
