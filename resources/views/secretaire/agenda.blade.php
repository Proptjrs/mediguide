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
            <div class="n">{{ collect($semaine)->flatten(1)->where('libre', true)->count() }}</div>
            <div class="l">Créneaux libres cette semaine</div>
            <span class="tag">semaine du {{ $lundi->translatedFormat('j F') }}</span>
        </div>
    </div>

    <section class="card" style="margin-top:26px">
        <h3>Déclarer une absence</h3>
        <p class="hint">Le créneau disparaît aussitôt du calendrier public : aucun patient ne peut plus le réserver.</p>
        <form method="POST" action="{{ route('secretaire.indisponibilite.store') }}" class="grid2">
            @csrf
            <label>Date
                <input type="date" name="date" required min="{{ now()->toDateString() }}">
            </label>
            <label>Motif
                <select name="motif" required>
                    <option value="conge">Congé</option>
                    <option value="mission">Mission</option>
                    <option value="urgence">Urgence</option>
                    <option value="formation">Formation</option>
                </select>
            </label>
            <label>De (facultatif)
                <input type="time" name="heure_debut">
            </label>
            <label>À
                <input type="time" name="heure_fin">
            </label>
            <button class="btn-primary" type="submit">Enregistrer l'absence</button>
        </form>
        <p class="hint">Sans horaire, l'absence couvre la journée entière.</p>
    </section>

    <section class="card" style="margin-top:22px">
        <h3>Absences enregistrées</h3>
        @forelse ($absences as $absence)
            <div class="ligne">
                <span>{{ $absence->date->translatedFormat('l j F Y') }}
                    @if ($absence->heure_debut) — de {{ substr($absence->heure_debut, 0, 5) }}
                        à {{ substr($absence->heure_fin, 0, 5) }}
                    @else — journée entière @endif
                    <span class="tag">{{ ucfirst($absence->motif) }}</span>
                </span>
                <form method="POST" action="{{ route('secretaire.indisponibilite.destroy', $absence) }}">
                    @csrf @method('DELETE')
                    <button class="btn-ghost" type="submit">Annuler</button>
                </form>
            </div>
        @empty
            <p class="hint">Aucune absence enregistrée.</p>
        @endforelse
    </section>

    <section class="card" style="margin-top:22px">
        <h3>Rendez-vous à venir</h3>
        @forelse ($rendezVous as $rdv)
            <div class="ligne">
                <span>{{ $rdv->date_heure->translatedFormat('l j F, H\hi') }}
                    — {{ $rdv->patient?->utilisateur?->fullName() ?? 'patient supprimé' }}
                    <span class="tag">{{ $rdv->statut }}</span>
                </span>
            </div>
        @empty
            <p class="hint">Aucun rendez-vous à venir.</p>
        @endforelse
    </section>
</div>
@endsection
