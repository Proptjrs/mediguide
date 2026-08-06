@php $edition = $structure->exists; @endphp
@extends('layouts.app', ['title' => ($edition ? 'Modifier' : 'Nouvelle') . ' structure — MediGuide'])

@section('content')
<div class="wrap dash-shell" style="max-width:820px">
    <h2 class="k-title">{{ $edition ? 'Modifier la structure' : 'Nouvelle structure médicale' }}</h2>
    <p class="k-sub" style="margin-bottom:28px">Laisser latitude et longitude vides pour un géocodage automatique de l'adresse (Nominatim / OpenStreetMap).</p>

    <div class="panel">
        <form method="POST" action="{{ $edition ? route('admin.structures.update', $structure) : route('admin.structures.store') }}">
            @csrf
            @if ($edition) @method('PUT') @endif

            <div class="field">
                <label>Nom de la structure</label>
                <input name="nom" value="{{ old('nom', $structure->nom) }}" required placeholder="Centre de Santé Wakhinane">
                @error('nom') <div class="field-error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label>Adresse</label>
                <input name="adresse" value="{{ old('adresse', $structure->adresse) }}" required placeholder="Wakhinane Nimzatt, Guédiawaye">
                @error('adresse') <div class="field-error">{{ $message }}</div> @enderror
            </div>

            <div class="grid2">
                <div class="field">
                    <label>Type</label>
                    <select name="type" required>
                        @foreach ($types as $t)
                            <option value="{{ $t }}" {{ old('type', $structure->type) === $t ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $t)) }}
                            </option>
                        @endforeach
                    </select>
                    @error('type') <div class="field-error">{{ $message }}</div> @enderror
                </div>
                <div class="field">
                    <label>Téléphone</label>
                    <input name="telephone" value="{{ old('telephone', $structure->telephone) }}" placeholder="+221 …">
                </div>
            </div>

            <div class="grid2">
                <div class="field">
                    <label>Latitude <span style="font-weight:500;color:var(--muted-2)">(optionnel)</span></label>
                    <input name="latitude" value="{{ old('latitude', $structure->latitude) }}" placeholder="14.7758">
                    @error('latitude') <div class="field-error">{{ $message }}</div> @enderror
                </div>
                <div class="field">
                    <label>Longitude <span style="font-weight:500;color:var(--muted-2)">(optionnel)</span></label>
                    <input name="longitude" value="{{ old('longitude', $structure->longitude) }}" placeholder="-17.4056">
                    @error('longitude') <div class="field-error">{{ $message }}</div> @enderror
                </div>
            </div>

            <div style="display:flex;align-items:center;gap:10px;margin:8px 0 24px">
                <input type="checkbox" name="urgences_24h" value="1" id="u24"
                       style="width:18px;height:18px;accent-color:var(--blue)"
                       {{ old('urgences_24h', $structure->urgences_24h) ? 'checked' : '' }}>
                <label for="u24" style="margin:0;font-weight:500;font-size:.9rem;color:var(--text)">Service d'urgences ouvert 24 h/24</label>
            </div>

            <div style="display:flex;gap:12px;justify-content:flex-end">
                <a href="{{ route('admin.structures.index') }}" class="btn btn-outline btn-sm">Annuler</a>
                <button class="btn btn-primary btn-sm">{{ $edition ? 'Enregistrer' : 'Créer la structure' }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
