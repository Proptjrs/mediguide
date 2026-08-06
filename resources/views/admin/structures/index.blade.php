@extends('layouts.app', ['title' => 'Structures médicales — MediGuide'])

@section('content')
<div class="wrap dash-shell">
    <h2 class="k-title">Structures médicales référencées</h2>
    <p class="k-sub" style="margin-bottom:28px">Ajouter, modifier ou retirer une structure du district. Les coordonnées GPS peuvent être déduites de l'adresse par géocodage OpenStreetMap.</p>

    <div class="panel">
        <h3>{{ $structures->count() }} structure{{ $structures->count() > 1 ? 's' : '' }}
            <a href="{{ route('admin.structures.create') }}" class="btn btn-primary btn-sm">+ Nouvelle structure</a></h3>
        @forelse ($structures as $s)
            <div class="row-item">
                <svg style="width:18px;height:18px;stroke:var(--blue)"><use href="#i-pin"/></svg>
                <div class="grow">
                    <h4>{{ $s->nom }}</h4>
                    <div class="sub">
                        {{ ucfirst(str_replace('_', ' ', $s->type)) }} · {{ $s->adresse }}
                        · {{ $s->medecins_count }} médecin{{ $s->medecins_count > 1 ? 's' : '' }}
                        · {{ number_format($s->latitude, 4) }}, {{ number_format($s->longitude, 4) }}
                    </div>
                </div>
                @if ($s->urgences_24h) <span class="pill r">Urgences 24h</span> @endif
                <a href="{{ route('admin.structures.edit', $s) }}" class="btn btn-outline btn-sm">Modifier</a>
                <form method="POST" action="{{ route('admin.structures.destroy', $s) }}"
                      onsubmit="return confirm('Supprimer « {{ $s->nom }} » ?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-ghost btn-sm" style="color:var(--red)">Supprimer</button>
                </form>
            </div>
        @empty
            <p style="color:var(--muted);margin:0">Aucune structure référencée.</p>
        @endforelse
    </div>

    <a href="{{ route('dashboard') }}" class="btn btn-outline btn-sm">← Retour à l'administration</a>
</div>
@endsection
