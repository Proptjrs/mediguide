@extends('layouts.app', ['title' => 'Agenda du médecin — MediGuide'])

@section('content')
<div class="wrap dash-shell">
    <span class="session-chip"><svg><use href="#i-check"/></svg>Connecté — {{ auth()->user()->fullName() }}, secrétariat</span>
    <h2 class="k-title">Agenda du D<sup>r</sup> {{ $medecin->utilisateur->fullName() }}</h2>
    <p class="k-sub" style="margin-bottom:28px">
        {{ $medecin->specialite->nom }} — {{ $medecin->structure->nom }}.
        Vous tenez cet agenda : vous déclarez les absences et libérez les créneaux.
    </p>

    <div class="dash-grid">
        <div class="kpi">
            <div class="ic"><svg><use href="#i-cal"/></svg></div>
            <div class="n">{{ $rendezVous->count() }}</div>
            <div class="l">Rendez-vous à venir</div>
        </div>
        <div class="kpi">
            <div class="ic"><svg><use href="#i-alert"/></svg></div>
            <div class="n">{{ $absences->count() }}</div>
            <div class="l">Absences enregistrées</div>
        </div>
        <div class="kpi">
            <div class="ic"><svg><use href="#i-check"/></svg></div>
            {{-- Les créneaux vivent dans la clé « creneaux » de chaque journée :
                 les aplatir sans passer par elle ne donnait jamais rien. --}}
            <div class="n">{{ collect($semaine)->pluck('creneaux')->flatten(1)->where('libre', true)->count() }}</div>
            <div class="l">Créneaux libres cette semaine</div>
            <span class="tag">semaine du {{ $lundi->translatedFormat('j F') }}</span>
        </div>
    </div>

    <div class="panel">
        <h3>Déclarer une absence</h3>
        <p class="panel-note">Le créneau disparaît aussitôt du calendrier public : aucun patient ne peut plus le réserver.</p>
        <form method="POST" action="{{ route('secretaire.indisponibilite.store') }}"
              style="display:flex;gap:14px;flex-wrap:wrap;align-items:flex-end;margin-bottom:8px">
            @csrf
            <div class="field" style="margin:0;flex:1;min-width:170px">
                <label>Motif</label>
                <select name="motif" required>
                    <option value="conge">Congé</option>
                    <option value="mission">Mission</option>
                    <option value="urgence">Urgence médicale</option>
                    <option value="formation">Formation</option>
                </select>
            </div>
            <div class="field" style="margin:0;width:150px">
                <label>Date</label>
                <input type="date" name="date" min="{{ today()->toDateString() }}" required>
            </div>
            <div class="field" style="margin:0;width:120px">
                <label>Début</label>
                <input type="time" name="heure_debut">
            </div>
            <div class="field" style="margin:0;width:120px">
                <label>Fin</label>
                <input type="time" name="heure_fin">
            </div>
            <button class="btn btn-outline btn-sm">Enregistrer</button>
        </form>
        <p class="panel-note" style="margin:0">Laisser début et fin vides pour une journée entière.</p>
        @error('heure_fin') <div class="field-error" style="margin-top:12px">{{ $message }}</div> @enderror
        @error('date') <div class="field-error" style="margin-top:12px">{{ $message }}</div> @enderror
    </div>

    <div class="panel">
        <h3>Absences enregistrées</h3>
        @forelse ($absences as $absence)
            <div class="row-item">
                <span class="pill o">{{ ucfirst($absence->motif) }}</span>
                <div class="grow">
                    <h4>{{ $absence->date->translatedFormat('l j F Y') }}</h4>
                    <div class="sub">
                        @if ($absence->heure_debut)
                            de {{ substr($absence->heure_debut, 0, 5) }} à {{ substr($absence->heure_fin, 0, 5) }}
                        @else
                            journée entière
                        @endif
                    </div>
                </div>
                <form method="POST" action="{{ route('secretaire.indisponibilite.destroy', $absence) }}">
                    @csrf @method('DELETE')
                    <button class="btn btn-outline btn-sm">Annuler</button>
                </form>
            </div>
        @empty
            <p style="color:var(--muted);margin:0">Aucune absence enregistrée.</p>
        @endforelse
    </div>

    <div class="panel">
        <h3>Rendez-vous à venir</h3>
        <p class="panel-note">Le secrétariat consulte ces rendez-vous ; seul le médecin les clôt.</p>
        @forelse ($rendezVous as $rdv)
            <div class="row-item">
                <span class="pill b">{{ $rdv->date_heure->format('d/m · H\hi') }}</span>
                <div class="grow">
                    <h4>{{ $rdv->patient?->utilisateur?->fullName() ?? 'patient supprimé' }}</h4>
                    <div class="sub">{{ $rdv->motif ?? 'Consultation' }}</div>
                </div>
                <span class="pill {{ $rdv->statut === 'CONFIRME' ? 'o' : ($rdv->statut === 'HONORE' ? 'g' : 'r') }}">{{ $rdv->statut }}</span>
            </div>
        @empty
            <p style="color:var(--muted);margin:0">Aucun rendez-vous à venir.</p>
        @endforelse
    </div>
</div>
@endsection
