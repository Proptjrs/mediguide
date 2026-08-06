<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Medecin;
use App\Services\{GeolocService, QuestionnaireService, RdvService};
use Carbon\Carbon;
use Illuminate\Http\Request;

/** API REST MediGuide (mémoire, chap. 4.2.1-4.2.2) — consommée en AJAX et testée via Postman. */
class ApiController extends Controller
{
    /** POST /api/orientation — analyse du questionnaire (F1/F2). */
    public function orientation(Request $request, QuestionnaireService $service)
    {
        $data = $request->validate([
            'probleme' => 'required|string', 'zone' => 'nullable|string',
            'age' => 'nullable|integer', 'niveau_urgence' => 'required|integer|min:1|max:10',
            'signes_alarme' => 'array',
        ]);
        $score = $service->calculateUrgencyScore($data['niveau_urgence'], $data['signes_alarme'] ?? []);

        return response()->json([
            'urgence' => $service->isUrgence($score),
            'score' => $score,
            'specialite' => $service->isUrgence($score) ? null
                : $service->determineSpecialty($data['probleme'], $data['zone'] ?? null, $data['age'] ?? null),
        ]);
    }

    /** GET /api/structures — structures proches (F3). */
    public function structures(Request $request, GeolocService $geoloc)
    {
        $v = $request->validate([
            'lat' => 'required|numeric', 'lng' => 'required|numeric', 'spec' => 'required|string',
        ]);

        return response()->json(
            $geoloc->structuresProches($v['lat'], $v['lng'], $v['spec'])
                ->map(fn ($s) => [
                    'id' => $s->id, 'nom' => $s->nom, 'type' => $s->type,
                    'lat' => $s->latitude, 'lng' => $s->longitude,
                    'distance_km' => $s->distance_km, 'duree' => $s->duree,
                ])
        );
    }

    /** GET /api/medecin/{medecin}/creneaux — créneaux de la semaine (F4, consommé par le calendrier). */
    public function creneaux(Medecin $medecin, Request $request, RdvService $rdv)
    {
        $lundi = Carbon::parse($request->query('semaine', 'now'))->startOfWeek();

        return response()->json($rdv->creneauxSemaine($medecin, $lundi));
    }
}
