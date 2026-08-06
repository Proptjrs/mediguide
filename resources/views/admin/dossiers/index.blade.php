@extends('layouts.app', ['title' => 'Dossiers patients — MediGuide'])

@section('content')
<div class="wrap dash-shell">
    <h2 class="k-title">Consultation d'un dossier patient <span class="pill b">Accès superviseur — lecture seule</span></h2>
    <p class="k-sub" style="margin-bottom:28px">L'administrateur peut consulter n'importe quel dossier patient à des fins de support et d'audit (chap. 4.2.7 du mémoire).</p>

    <div class="panel">
        <form method="GET" action="{{ route('admin.dossiers.index') }}" style="display:flex;gap:14px;align-items:flex-end">
            <div class="field" style="margin:0;flex:1">
                <label>Rechercher un patient</label>
                <input type="text" name="q" value="{{ $recherche }}" placeholder="Nom, prénom ou e-mail…">
            </div>
            <button class="btn btn-primary btn-sm">Rechercher</button>
        </form>
    </div>

    <div class="panel">
        @forelse ($patients as $p)
            <div class="row-item">
                <div class="ava" style="width:44px;height:44px;font-size:1rem;border-radius:12px">
                    {{ strtoupper(substr($p->utilisateur->prenom, 0, 1) . substr($p->utilisateur->nom, 0, 1)) }}
                </div>
                <div class="grow">
                    <h4>{{ $p->utilisateur->fullName() }}</h4>
                    <div class="sub">{{ $p->utilisateur->email }} @if($p->groupe_sanguin) · {{ $p->groupe_sanguin }} @endif</div>
                </div>
                @if ($p->dossier)
                    <a href="{{ route('admin.dossiers.show', $p->dossier) }}" class="btn btn-outline btn-sm">Consulter</a>
                @else
                    <span class="pill r">Pas de dossier</span>
                @endif
            </div>
        @empty
            <p style="color:var(--muted);margin:0">Aucun patient trouvé.</p>
        @endforelse
    </div>

    <div>{{ $patients->links() }}</div>
</div>
@endsection
