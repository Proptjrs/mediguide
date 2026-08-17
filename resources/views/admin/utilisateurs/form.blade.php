@php $edition = $utilisateur->exists; @endphp
@extends('layouts.app', ['title' => ($edition ? 'Modifier un compte' : 'Nouveau compte') . ' — MediGuide'])

@section('content')
<div class="wrap dash-shell" style="max-width:820px">
    <h2 class="k-title">{{ $edition ? 'Modifier le compte' : 'Nouveau compte' }}</h2>
    <p class="k-sub" style="margin-bottom:28px">
        @if ($edition)
            {{ $utilisateur->fullName() }} — rôle : {{ $utilisateur->role }}.
        @else
            Un compte créé ici est considéré comme confirmé : c'est l'administration qui répond de l'adresse.
        @endif
    </p>

    <div class="panel">
        <form method="POST" action="{{ $edition ? route('admin.utilisateurs.update', $utilisateur) : route('admin.utilisateurs.store') }}">
            @csrf
            @if ($edition) @method('PUT') @endif

            <div class="grid2">
                <div class="field">
                    <label>Prénom</label>
                    <input name="prenom" value="{{ old('prenom', $utilisateur->prenom) }}" required>
                    @error('prenom') <div class="field-error">{{ $message }}</div> @enderror
                </div>
                <div class="field">
                    <label>Nom</label>
                    <input name="nom" value="{{ old('nom', $utilisateur->nom) }}" required>
                    @error('nom') <div class="field-error">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="field">
                <label>E-mail</label>
                <input type="email" name="email" value="{{ old('email', $utilisateur->email) }}" required>
                <p class="field-error" style="color:var(--muted);font-weight:500">
                    Le domaine est vérifié. Modifier cette adresse annule la confirmation et envoie un nouveau lien.</p>
                @error('email') <div class="field-error">{{ $message }}</div> @enderror
            </div>

            <div class="grid2">
                <div class="field">
                    <label>Téléphone</label>
                    <input name="telephone" value="{{ old('telephone', $utilisateur->telephone) }}" placeholder="+221 …">
                </div>

                @unless ($edition)
                    <div class="field">
                        <label>Rôle</label>
                        <select name="role" required
                                onchange="document.getElementById('bloc-medecin').hidden = this.value !== 'secretaire'">
                            <option value="patient" {{ old('role') === 'patient' ? 'selected' : '' }}>Patient</option>
                            <option value="secretaire" {{ old('role') === 'secretaire' ? 'selected' : '' }}>Secrétaire médicale</option>
                            <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Administrateur</option>
                        </select>
                        <p class="field-error" style="color:var(--muted);font-weight:500">
                            Un médecin ne se crée pas ici : il s'inscrit lui-même avec son n° d'Ordre, que vous validez ensuite.</p>
                        @error('role') <div class="field-error">{{ $message }}</div> @enderror
                    </div>

                    {{-- Une secrétaire tient l'agenda d'un médecin : sans ce
                         rattachement, son espace serait vide. Le bloc n'apparaît
                         donc que pour ce rôle. --}}
                    <div class="field" id="bloc-medecin" {{ old('role') === 'secretaire' ? '' : 'hidden' }}>
                        <label>Médecin assisté</label>
                        <select name="medecin_id">
                            <option value="">— choisir —</option>
                            @foreach ($medecins as $m)
                                <option value="{{ $m->id }}" {{ old('medecin_id') === $m->id ? 'selected' : '' }}>
                                    D<sup>r</sup> {{ $m->utilisateur->fullName() }} — {{ $m->specialite->nom }}
                                </option>
                            @endforeach
                        </select>
                        @error('medecin_id') <div class="field-error">{{ $message }}</div> @enderror
                    </div>
                @endunless
            </div>

            @unless ($edition)
                <div class="field">
                    <label>Mot de passe provisoire</label>
                    <input type="password" name="password" required minlength="8">
                    <p class="field-error" style="color:var(--muted);font-weight:500">
                        Au moins 8 caractères. Le titulaire pourra le changer depuis son profil.</p>
                    @error('password') <div class="field-error">{{ $message }}</div> @enderror
                </div>
            @endunless

            <div style="display:flex;gap:12px;justify-content:flex-end">
                <a href="{{ route('admin.utilisateurs.index') }}" class="btn btn-outline btn-sm">Annuler</a>
                <button class="btn btn-primary btn-sm">{{ $edition ? 'Enregistrer' : 'Créer le compte' }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
