@extends('layouts.app', ['title' => 'Planning — ' . $medecin->utilisateur->fullName() . ' — MediGuide'])

@section('content')
<div class="wrap dash-shell">
    <h2 class="k-title">Planning de consultation</h2>
    <p class="k-sub" style="margin-bottom:28px">{{ $medecin->utilisateur->fullName() }} · {{ $medecin->specialite->nom }} · {{ $medecin->structure->nom }}</p>

    <div class="panel">
        <h3>Plages horaires fixées par l'administration</h3>
        @php $jours = [1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi']; @endphp
        @foreach ($jours as $num => $nom)
            <div class="row-item">
                <div style="width:100px;font-weight:700;color:var(--text-dark);font-size:.9rem">{{ $nom }}</div>
                <div class="grow" style="display:flex;flex-wrap:wrap;gap:8px">
                    @forelse ($creneaux->get($num, collect()) as $c)
                        <span class="pill b" style="display:inline-flex;align-items:center;gap:8px">
                            {{ substr($c->heure_debut, 0, 5) }}–{{ substr($c->heure_fin, 0, 5) }}
                            <form method="POST" action="{{ route('admin.planning.destroy', $c) }}" style="display:contents">
                                @csrf @method('DELETE')
                                <button class="btn-ghost" style="color:var(--blue-dark);font-size:.9rem;line-height:1">✕</button>
                            </form>
                        </span>
                    @empty
                        <span style="color:var(--muted-2);font-size:.85rem">Aucune plage</span>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>

    <div class="panel">
        <h3>Ajouter une plage horaire</h3>
        <form method="POST" action="{{ route('admin.planning.store', $medecin) }}" style="display:flex;gap:14px;flex-wrap:wrap;align-items:flex-end">
            @csrf
            <div class="field" style="margin:0;width:150px">
                <label>Jour</label>
                <select name="jour_semaine" required>
                    @foreach (['1' => 'Lundi', '2' => 'Mardi', '3' => 'Mercredi', '4' => 'Jeudi', '5' => 'Vendredi'] as $v => $l)
                        <option value="{{ $v }}">{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field" style="margin:0;width:120px">
                <label>Début</label>
                <input type="time" name="heure_debut" required>
            </div>
            <div class="field" style="margin:0;width:120px">
                <label>Fin</label>
                <input type="time" name="heure_fin" required>
            </div>
            <button class="btn btn-primary btn-sm">Ajouter</button>
        </form>
        @error('heure_fin') <div class="field-error" style="margin-top:12px">{{ $message }}</div> @enderror
    </div>
</div>
@endsection
