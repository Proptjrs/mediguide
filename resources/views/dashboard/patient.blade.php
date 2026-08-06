@extends('layouts.app', ['title' => 'Mon espace patient — MediGuide'])

@section('content')
<div class="wrap dash-shell">
    <span class="session-chip"><svg><use href="#i-check"/></svg>Connecté — {{ auth()->user()->fullName() }}</span>
    <h2 class="k-title">Mon espace patient</h2>
    <p class="k-sub" style="margin-bottom:28px">Vos rendez-vous et votre dossier médical, au même endroit.</p>

    <div class="dash-grid">
        <div class="kpi">
            <div class="ic"><svg><use href="#i-cal"/></svg></div>
            <div class="n">{{ $rdvs->count() }}</div>
            <div class="l">Rendez-vous à venir</div>
        </div>
        <div class="kpi">
            <div class="ic"><svg><use href="#i-shield"/></svg></div>
            <div class="n">{{ $dossier ? 1 : 0 }}</div>
            <div class="l">Dossier médical sécurisé</div>
            @if ($dossier) <span class="tag">Chiffré</span> @endif
        </div>
        <div class="kpi">
            <div class="ic"><svg><use href="#i-target"/></svg></div>
            <div class="n" style="font-size:1.3rem">Nouveau</div>
            <div class="l">Besoin d'un autre avis médical ?</div>
        </div>
    </div>

    <div class="panel">
        <h3>Prochains rendez-vous <a class="btn btn-primary btn-sm" href="{{ route('orientation') }}">+ Nouveau</a></h3>
        @forelse ($rdvs as $rdv)
            <div class="row-item">
                <div class="ava" style="width:44px;height:44px;font-size:1rem;border-radius:12px">
                    {{ strtoupper(substr($rdv->medecin->utilisateur->prenom, 0, 1) . substr($rdv->medecin->utilisateur->nom, 0, 1)) }}
                </div>
                <div class="grow">
                    <h4>{{ $rdv->medecin->utilisateur->fullName() }} — {{ $rdv->medecin->specialite->nom }}</h4>
                    <div class="sub">{{ $rdv->date_heure->translatedFormat('l j F Y · H\hi') }} · {{ $rdv->medecin->structure->nom }}</div>
                </div>
                <span class="pill g" style="margin-right:10px">{{ $rdv->statut }}</span>
                <a href="{{ route('calendrier', $rdv->medecin) }}" class="btn btn-outline btn-sm">Modifier</a>
                <form method="POST" action="{{ route('rdv.annuler', $rdv) }}" onsubmit="return confirm('Annuler ce rendez-vous ?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-ghost btn-sm" style="color:var(--red)">Annuler</button>
                </form>
            </div>
        @empty
            <p style="color:var(--muted);margin:0">Aucun rendez-vous à venir —
                <a href="{{ route('orientation') }}" style="color:var(--blue-dark);font-weight:600">commencez une orientation</a>.</p>
        @endforelse
    </div>

    <div class="panel">
        <h3>Notifications <span class="pill b">E-mail · SMS · historique</span></h3>
        @forelse ($notifications as $n)
            <div class="notif">
                <span class="ic"><svg><use href="#i-{{ str_contains($n->data['titre'] ?? '', 'Rappel') ? 'mail' : 'check' }}"/></svg></span>
                <div>
                    <b>{{ $n->data['titre'] ?? 'Notification' }}</b>
                    @if (! empty($n->data['date']))
                        — rendez-vous du {{ \Illuminate\Support\Carbon::parse($n->data['date'])->translatedFormat('j F Y à H\hi') }}
                    @endif
                    <div class="sub">{{ $n->created_at->diffForHumans() }}</div>
                </div>
            </div>
        @empty
            <p style="color:var(--muted);margin:0">Aucune notification pour le moment.</p>
        @endforelse
    </div>

    <div class="panel">
        <h3>Mon dossier médical <span class="pill b">Accès personnel uniquement</span></h3>
        @if ($dossier)
            <div class="row-item"><svg style="width:18px;height:18px;stroke:var(--red)"><use href="#i-heart"/></svg>
                <div class="grow"><h4>Groupe sanguin</h4><div class="sub">{{ auth()->user()->patient->groupe_sanguin ?? 'Non renseigné' }}</div></div></div>
            <div class="row-item"><svg style="width:18px;height:18px;stroke:var(--amber)"><use href="#i-alert"/></svg>
                <div class="grow"><h4>Allergies</h4><div class="sub">{{ auth()->user()->patient->allergies ?? 'Aucune connue' }}</div></div></div>
            <div class="row-item"><svg style="width:18px;height:18px;stroke:var(--blue)"><use href="#i-doc"/></svg>
                <div class="grow"><h4>Antécédents</h4><div class="sub">{{ $dossier->antecedents ?? 'RAS' }}</div></div></div>
        @else
            <p style="color:var(--muted);margin:0">Dossier en cours de création.</p>
        @endif
    </div>

    <div class="panel">
        <h3>Mes ordonnances</h3>
        @forelse ($consultations as $c)
            <div class="row-item">
                <svg style="width:18px;height:18px;stroke:var(--blue)"><use href="#i-doc"/></svg>
                <div class="grow">
                    <h4>Ordonnance — {{ $c->created_at->translatedFormat('j F Y') }}</h4>
                    <div class="sub">Dr. {{ $c->medecin->utilisateur->fullName() }} · {{ $c->medecin->specialite->nom }}</div>
                </div>
                <a href="{{ route('consultation.ordonnance', $c) }}" class="btn btn-outline btn-sm">Ouvrir</a>
            </div>
        @empty
            <p style="color:var(--muted);margin:0">Aucune ordonnance pour le moment.</p>
        @endforelse
    </div>
</div>
@endsection
