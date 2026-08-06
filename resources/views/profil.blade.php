@extends('layouts.app', ['title' => 'Mon profil — MediGuide'])

@section('content')
<div class="wrap dash-shell" style="max-width:860px">
    <span class="session-chip"><svg><use href="#i-check"/></svg>{{ $utilisateur->fullName() }}</span>
    <h2 class="k-title">Mon profil</h2>
    <p class="k-sub" style="margin-bottom:28px">Vos informations personnelles et vos paramètres de connexion.</p>

    <div class="panel">
        <h3>Identité et contact</h3>
        <form method="POST" action="{{ route('profil.update') }}">
            @csrf @method('PUT')
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
            <div class="grid2">
                <div class="field">
                    <label>E-mail</label>
                    <input type="email" name="email" value="{{ old('email', $utilisateur->email) }}" required>
                    @error('email') <div class="field-error">{{ $message }}</div> @enderror
                </div>
                <div class="field">
                    <label>Téléphone</label>
                    <input name="telephone" value="{{ old('telephone', $utilisateur->telephone) }}" placeholder="+221 …">
                </div>
            </div>
            <div style="display:flex;justify-content:flex-end">
                <button class="btn btn-primary btn-sm">Enregistrer</button>
            </div>
        </form>
    </div>

    @if ($medecin)
        <div class="panel">
            <h3>Profil professionnel <span class="pill b">Défini par l'administration</span></h3>
            <p class="panel-note">La spécialité, la structure de rattachement et le numéro d'Ordre sont vérifiés puis fixés par l'administration (chap. 3 et UC-A2). Contactez-la pour toute correction.</p>
            <div class="row-item">
                <svg style="width:18px;height:18px;stroke:var(--blue)"><use href="#i-kit"/></svg>
                <div class="grow"><h4>Spécialité</h4><div class="sub">{{ $medecin->specialite->nom }}</div></div>
            </div>
            <div class="row-item">
                <svg style="width:18px;height:18px;stroke:var(--blue)"><use href="#i-pin"/></svg>
                <div class="grow"><h4>Structure</h4><div class="sub">{{ $medecin->structure->nom }}</div></div>
            </div>
            <div class="row-item">
                <svg style="width:18px;height:18px;stroke:var(--blue)"><use href="#i-shield"/></svg>
                <div class="grow"><h4>Numéro d'Ordre</h4><div class="sub">{{ $medecin->num_ordre }}</div></div>
                <span class="pill {{ $medecin->valide ? 'g' : 'o' }}">{{ $medecin->valide ? 'Validé' : 'En attente' }}</span>
            </div>
        </div>
    @endif

    @if ($patient)
        <div class="panel">
            <h3>Mon dossier médical <span class="pill b">Visible par mes médecins traitants</span></h3>
            <form method="POST" action="{{ route('profil.dossier') }}">
                @csrf @method('PUT')
                <div class="grid2">
                    <div class="field">
                        <label>Groupe sanguin</label>
                        <input name="groupe_sanguin" value="{{ old('groupe_sanguin', $patient->groupe_sanguin) }}" placeholder="O+">
                    </div>
                    <div class="field">
                        <label>Allergies connues</label>
                        <input name="allergies" value="{{ old('allergies', $patient->allergies) }}" placeholder="Pénicilline…">
                    </div>
                </div>
                <div class="field">
                    <label>Antécédents médicaux</label>
                    <textarea name="antecedents" rows="3" placeholder="Diabète, hypertension…">{{ old('antecedents', $patient->dossier?->antecedents) }}</textarea>
                </div>
                <div style="display:flex;justify-content:flex-end">
                    <button class="btn btn-primary btn-sm">Enregistrer le dossier</button>
                </div>
            </form>
        </div>
    @endif

    <div class="panel">
        <h3>Mot de passe</h3>
        <form method="POST" action="{{ route('profil.password') }}">
            @csrf @method('PUT')
            <div class="field">
                <label>Mot de passe actuel</label>
                <input type="password" name="mot_de_passe_actuel" required>
                @error('mot_de_passe_actuel') <div class="field-error">{{ $message }}</div> @enderror
            </div>
            <div class="grid2">
                <div class="field">
                    <label>Nouveau mot de passe</label>
                    <input type="password" name="password" required minlength="8">
                    @error('password') <div class="field-error">{{ $message }}</div> @enderror
                </div>
                <div class="field">
                    <label>Confirmation</label>
                    <input type="password" name="password_confirmation" required>
                </div>
            </div>
            <div style="display:flex;justify-content:flex-end">
                <button class="btn btn-primary btn-sm">Modifier le mot de passe</button>
            </div>
        </form>
    </div>

    <div class="panel">
        <h3>Session</h3>
        <p class="panel-note">Fermer votre session sur cet appareil.</p>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="btn btn-outline btn-sm">Se déconnecter</button>
        </form>
    </div>
</div>
@endsection
