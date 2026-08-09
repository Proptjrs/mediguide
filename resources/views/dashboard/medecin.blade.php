@extends('layouts.app', ['title' => 'Espace médecin — MediGuide'])

@section('content')
<div class="wrap dash-shell">
    <span class="session-chip"><svg><use href="#i-check"/></svg>Connecté — Dr. {{ auth()->user()->fullName() }}</span>
    <h2 class="k-title">Mon agenda</h2>
    <p class="k-sub" style="margin-bottom:28px">Vos consultations, votre planning et vos indisponibilités.</p>

    <div class="dash-grid">
        <div class="kpi">
            <div class="ic"><svg><use href="#i-cal"/></svg></div>
            <div class="n">{{ $rdvsJour->count() }}</div>
            <div class="l">Consultations aujourd'hui</div>
            <span class="tag">{{ now()->translatedFormat('l j F') }}</span>
        </div>
        <div class="kpi">
            <div class="ic"><svg><use href="#i-doc"/></svg></div>
            <div class="n">{{ $rdvsJour->where('statut', 'CONFIRME')->count() }}</div>
            <div class="l">Rendez-vous à clore</div>
        </div>
        <div class="kpi">
            <div class="ic"><svg><use href="#i-alert"/></svg></div>
            <div class="n">{{ $indisponibilites->count() }}</div>
            <div class="l">Indisponibilités déclarées</div>
        </div>
    </div>

    <div class="panel">
        <h3>Agenda du jour</h3>
        @forelse ($rdvsJour as $rdv)
            <div class="row-item">
                <span class="pill b">{{ $rdv->date_heure->format('H:i') }}</span>
                <div class="grow">
                    <h4>{{ $rdv->patient->utilisateur->fullName() }}</h4>
                    <div class="sub">{{ $rdv->motif ?? 'Consultation' }}</div>
                </div>
                <span class="pill {{ $rdv->statut === 'CONFIRME' ? 'o' : ($rdv->statut === 'HONORE' ? 'g' : 'r') }}">{{ $rdv->statut }}</span>
                @if ($rdv->statut === 'CONFIRME')
                    <form method="POST" action="{{ route('rdv.honorer', $rdv) }}">
                        @csrf @method('PATCH')
                        <button class="btn btn-outline btn-sm">Patient reçu</button>
                    </form>
                    <form method="POST" action="{{ route('rdv.absent', $rdv) }}">
                        @csrf @method('PATCH')
                        <button class="btn btn-outline btn-sm">Absent</button>
                    </form>
                @endif
            </div>
        @empty
            <p style="color:var(--muted);margin:0">Aucune consultation aujourd'hui.</p>
        @endforelse
    </div>

    <div class="panel">
        <h3>Mon planning de consultation <span class="pill b">Défini par l'administration</span></h3>
        <p class="panel-note">Votre planning est établi par l'administration de l'hôpital. Vous pouvez le consulter
            et déclarer une indisponibilité ponctuelle (congé, mission, urgence, formation).</p>
        @php $jours = [1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi']; @endphp
        @foreach ($jours as $num => $nom)
            <div class="row-item">
                <div style="width:100px;font-weight:700;color:var(--text-dark);font-size:.9rem">{{ $nom }}</div>
                <div class="grow" style="display:flex;flex-wrap:wrap;gap:8px">
                    @forelse ($planningBase->get($num, collect()) as $c)
                        <span class="pill b">{{ substr($c->heure_debut, 0, 5) }}–{{ substr($c->heure_fin, 0, 5) }}</span>
                    @empty
                        <span style="color:var(--muted-2);font-size:.85rem">Aucune plage</span>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>

    <div class="panel">
        <h3>Déclarer une indisponibilité ponctuelle</h3>
        <p class="panel-note">Congé, mission, urgence ou formation — pas de modification du planning de base.</p>
        <form method="POST" action="{{ route('medecin.indisponibilite.store') }}" style="display:flex;gap:14px;flex-wrap:wrap;align-items:flex-end;margin-bottom:8px">
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
            <button class="btn btn-outline btn-sm">Déclarer</button>
        </form>
        <p class="panel-note" style="margin:0 0 16px">Laisser début/fin vides pour une journée entière.</p>
        @error('heure_fin') <div class="field-error" style="margin-bottom:12px">{{ $message }}</div> @enderror
        @error('date') <div class="field-error" style="margin-bottom:12px">{{ $message }}</div> @enderror

        @forelse ($indisponibilites as $i)
            <div class="row-item">
                <div class="grow">
                    <h4>{{ \Illuminate\Support\Carbon::parse($i->date)->translatedFormat('l j F Y') }}</h4>
                    <div class="sub">
                        @if ($i->heure_debut)
                            {{ substr($i->heure_debut, 0, 5) }}–{{ substr($i->heure_fin, 0, 5) }}
                        @else
                            Journée entière
                        @endif
                        · {{ ucfirst($i->motif) }}
                    </div>
                </div>
                <form method="POST" action="{{ route('medecin.indisponibilite.destroy', $i) }}">
                    @csrf @method('DELETE')
                    <button class="btn btn-ghost btn-sm" style="color:var(--red)">Annuler</button>
                </form>
            </div>
        @empty
            <p style="color:var(--muted);margin:0">Aucune indisponibilité déclarée.</p>
        @endforelse
    </div>
</div>
@endsection
