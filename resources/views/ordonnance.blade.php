@extends('layouts.app', ['title' => 'Ordonnance — MediGuide'])

@section('content')
<div class="rx-shell">
    <div class="rx-paper">
        <div class="rx-head">
            <div>
                <h4>Ordonnance médicale</h4>
                <div class="sub">{{ $consultation->created_at->translatedFormat('j F Y') }}</div>
            </div>
            <span class="rx-lock"><svg><use href="#i-lock"/></svg>Dossier chiffré</span>
        </div>
        <div class="rx-body">
            <div class="rx-row"><span>Patient</span> <b>{{ $consultation->patient->utilisateur->fullName() }}</b></div>
            <div class="rx-row"><span>Médecin</span> <b>{{ $consultation->medecin->utilisateur->fullName() }} — {{ $consultation->medecin->specialite->nom }}</b></div>
            <div class="rx-row"><span>Structure</span> <b>{{ $consultation->medecin->structure->nom }}</b></div>

            <div class="rx-meds">
                @foreach (preg_split('/\r?\n/', trim($consultation->prescription)) as $i => $ligne)
                    @continue(trim($ligne) === '')
                    <div class="rx-med">
                        <div class="n">{{ $i + 1 }}</div>
                        <div>
                            <h5>{{ $ligne }}</h5>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="rx-sign">
                <div class="who">Rédigée par<b>Dr. {{ $consultation->medecin->utilisateur->fullName() }}</b></div>
                <span class="pill b">{{ $consultation->medecin->num_ordre }}</span>
            </div>
        </div>
    </div>
    <div class="rx-acts">
        <a href="{{ url()->previous() }}" class="btn btn-outline btn-sm">Fermer</a>
        <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">
            <svg><use href="#i-doc"/></svg> Télécharger le PDF
        </button>
    </div>
</div>
@endsection
