<?php

namespace App\Livewire;

use App\Services\QuestionnaireService;
use Livewire\Component;

/** Composant Livewire 3 — questionnaire d'orientation en 5 étapes (chap. 4.3). */
class QuestionnaireOrientation extends Component
{
    public int $etape = 1;

    // Étape 1 — localisation
    public ?float $lat = null;
    public ?float $lng = null;
    public string $quartier = '';

    // Étape 2 — profil
    public ?int $age = null;
    public string $sexe = '';
    public string $antecedents = '';

    // Étape 3 — difficulté principale
    public string $probleme = '';

    // Étape 4 — zone du corps
    public string $zone = '';

    // Étape 5 — urgence
    public int $niveauUrgence = 3;
    public array $signesAlarme = [];

    public function setPosition(float $lat, float $lng): void
    {
        $this->lat = $lat;
        $this->lng = $lng;
    }

    public function suivant(): void
    {
        $this->validate(match ($this->etape) {
            1 => ['lat' => 'required|numeric', 'lng' => 'required|numeric'],
            2 => ['age' => 'nullable|integer|min:0|max:120', 'sexe' => 'nullable|in:F,M'],
            3 => ['probleme' => 'required'],
            4 => ['zone' => 'required'],
            default => ['niveauUrgence' => 'required|integer|min:1|max:10', 'signesAlarme' => 'array'],
        }, ['lat.required' => 'Indiquez votre position ou choisissez un quartier.',
            'probleme.required' => 'Choisissez votre difficulté principale.',
            'zone.required' => 'Indiquez la zone concernée.']);

        if ($this->etape < 5) {
            $this->etape++;

            return;
        }
        $this->terminer();
    }

    public function precedent(): void
    {
        $this->etape = max(1, $this->etape - 1);
    }

    private function terminer(): void
    {
        $service = app(QuestionnaireService::class);
        $questionnaire = $service->analyser([
            'lat' => $this->lat, 'lng' => $this->lng,
            'age' => $this->age, 'sexe' => $this->sexe,
            'antecedents' => $this->antecedents,
            'probleme' => $this->probleme, 'zone' => $this->zone,
            'niveau_urgence' => $this->niveauUrgence,
            'signes_alarme' => $this->signesAlarme,
        ], auth()->user()?->patient?->id);

        if ($questionnaire->urgence_detectee) {                 // F2 : redirection urgence
            $this->redirectRoute('urgence', ['score' => $questionnaire->niveau_urgence]);

            return;
        }

        $this->redirectRoute('resultats', [                     // F3 : vers la carte
            'spec' => $questionnaire->specialite_resultat,
            'lat' => $this->lat, 'lng' => $this->lng,
        ]);
    }

    public function render()
    {
        return view('livewire.questionnaire-orientation')
            ->layout('layouts.app', ['title' => 'Orientation — MediGuide']);
    }
}
