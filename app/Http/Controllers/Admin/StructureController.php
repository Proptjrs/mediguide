<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StructureMedicale;
use App\Services\GeolocService;
use Illuminate\Http\Request;

/**
 * Gestion des structures médicales référencées par l'administrateur
 * (mémoire, chap. 2 : « Gérer les structures médicales référencées »).
 *
 * Les coordonnées peuvent être saisies à la main ou déduites de l'adresse par
 * géocodage Nominatim (chap. 4.2.4, UC-A3).
 */
class StructureController extends Controller
{
    private const TYPES = ['hopital', 'centre_sante', 'poste_sante', 'clinique'];

    public function index()
    {
        return view('admin.structures.index', [
            'structures' => StructureMedicale::withCount('medecins')->orderBy('nom')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.structures.form', [
            'structure' => new StructureMedicale(),
            'types' => self::TYPES,
        ]);
    }

    public function store(Request $request, GeolocService $geoloc)
    {
        $data = $this->valider($request);
        $data = $this->completerCoordonnees($data, $geoloc);

        $structure = StructureMedicale::create($data);

        return redirect()->route('admin.structures.index')
            ->with('ok', 'Structure « ' . $structure->nom . ' » ajoutée.');
    }

    public function edit(StructureMedicale $structure)
    {
        return view('admin.structures.form', [
            'structure' => $structure,
            'types' => self::TYPES,
        ]);
    }

    public function update(Request $request, StructureMedicale $structure, GeolocService $geoloc)
    {
        $data = $this->valider($request, $structure);
        $data = $this->completerCoordonnees($data, $geoloc);

        $structure->update($data);

        return redirect()->route('admin.structures.index')
            ->with('ok', 'Structure « ' . $structure->nom . ' » mise à jour.');
    }

    public function destroy(StructureMedicale $structure)
    {
        if ($structure->medecins()->exists()) {
            return back()->with('erreur',
                'Impossible de supprimer « ' . $structure->nom . ' » : des médecins y sont rattachés.');
        }

        $nom = $structure->nom;
        $structure->delete();

        return redirect()->route('admin.structures.index')
            ->with('ok', 'Structure « ' . $nom . ' » supprimée.');
    }

    private function valider(Request $request, ?StructureMedicale $structure = null): array
    {
        $unique = 'unique:structures_medicales,nom' . ($structure ? ',' . $structure->id : '');

        return $request->validate([
            'nom' => ['required', 'string', 'max:150', $unique],
            'adresse' => 'required|string|max:255',
            'telephone' => 'nullable|string|max:30',
            'type' => 'required|in:' . implode(',', self::TYPES),
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'urgences_24h' => 'nullable|boolean',
        ]);
    }

    /**
     * Complète latitude/longitude par géocodage Nominatim si elles sont absentes.
     * En cas d'échec du service, on retombe sur le centre de Guédiawaye pour ne
     * jamais bloquer l'administrateur — il pourra corriger à la main.
     */
    private function completerCoordonnees(array $data, GeolocService $geoloc): array
    {
        $data['urgences_24h'] = (bool) ($data['urgences_24h'] ?? false);

        if (! empty($data['latitude']) && ! empty($data['longitude'])) {
            return $data;
        }

        $coords = $geoloc->geocoder($data['adresse']);

        $data['latitude'] = $coords['lat'] ?? 14.7758;
        $data['longitude'] = $coords['lng'] ?? -17.4056;

        return $data;
    }
}
