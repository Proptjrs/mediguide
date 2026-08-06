@extends('layouts.app', ['title' => 'Dossier — ' . $dossier->patient->utilisateur->fullName() . ' — MediGuide'])

@section('content')
<div class="wrap dash-shell">
    <h2 class="k-title">{{ $dossier->patient->utilisateur->fullName() }} <span class="pill b">Accès superviseur — lecture seule</span></h2>
    <p class="k-sub" style="margin-bottom:28px">{{ $dossier->patient->utilisateur->email }}</p>

    <div class="panel">
        <h3>Informations médicales</h3>
        <div class="row-item"><svg style="width:18px;height:18px;stroke:var(--red)"><use href="#i-heart"/></svg>
            <div class="grow"><h4>Groupe sanguin</h4><div class="sub">{{ $dossier->patient->groupe_sanguin ?? 'Non renseigné' }}</div></div></div>
        <div class="row-item"><svg style="width:18px;height:18px;stroke:var(--amber)"><use href="#i-alert"/></svg>
            <div class="grow"><h4>Allergies</h4><div class="sub">{{ $dossier->patient->allergies ?? 'Aucune connue' }}</div></div></div>
        <div class="row-item"><svg style="width:18px;height:18px;stroke:var(--blue)"><use href="#i-doc"/></svg>
            <div class="grow"><h4>Antécédents</h4><div class="sub">{{ $dossier->antecedents ?? 'RAS' }}</div></div></div>
    </div>

    <div class="panel">
        <h3>Historique des rendez-vous</h3>
        @forelse ($rdvs as $rdv)
            <div class="row-item">
                <div class="grow">
                    <h4>{{ $rdv->medecin->utilisateur->fullName() }} — {{ $rdv->medecin->specialite->nom }}</h4>
                    <div class="sub">{{ $rdv->date_heure->translatedFormat('l j F Y · H\hi') }}</div>
                </div>
                <span class="pill {{ $rdv->statut === 'HONORE' ? 'g' : ($rdv->statut === 'ANNULE' ? 'r' : 'o') }}">{{ $rdv->statut }}</span>
            </div>
        @empty
            <p style="color:var(--muted);margin:0">Aucun rendez-vous.</p>
        @endforelse
    </div>

    <div class="panel">
        <h3>Comptes-rendus et ordonnances</h3>
        @forelse ($consultations as $c)
            <div class="row-item">
                <svg style="width:18px;height:18px;stroke:var(--blue)"><use href="#i-doc"/></svg>
                <div class="grow">
                    <h4>{{ $c->created_at->translatedFormat('j F Y') }} — Dr. {{ $c->medecin->utilisateur->fullName() }}</h4>
                    <div class="sub">{{ $c->observations }}</div>
                </div>
                @if ($c->prescription)
                    <a href="{{ route('consultation.ordonnance', $c) }}" class="btn btn-outline btn-sm">Ordonnance</a>
                @endif
            </div>
        @empty
            <p style="color:var(--muted);margin:0">Aucun compte-rendu.</p>
        @endforelse
    </div>

    <a href="{{ route('admin.dossiers.index') }}" class="btn btn-outline btn-sm">← Retour à la recherche</a>
</div>
@endsection
