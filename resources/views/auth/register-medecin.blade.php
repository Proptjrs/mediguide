@extends('layouts.app', ['title' => 'Inscription médecin — MediGuide'])

@section('content')
<div class="wrap" style="padding-top:56px;padding-bottom:96px">
  <div class="auth-shell">
    <div class="auth-shell-in">
      <div class="auth-side">
        <div>
          <h3>Rejoindre MediGuide en tant que médecin.</h3>
          <p>Votre inscription reste en attente jusqu'à ce qu'un administrateur vérifie votre numéro d'Ordre (chap. 2 et 3 du mémoire). Le planning de consultation est ensuite fixé par l'administration de votre structure.</p>
        </div>
        <p style="font-size:.78rem;color:#7DD3FC;margin-top:24px">Déjà inscrit ? Connectez-vous avec les identifiants transmis par votre administration.</p>
      </div>
      <div class="auth-form">
        <div class="auth-tabs">
          <a class="auth-tab" href="{{ route('register') }}">Patient</a>
          <a class="auth-tab on" href="{{ route('register.medecin') }}">Médecin</a>
        </div>
        <h2>Inscription médecin</h2>
        <p class="sub">Compte activé après vérification de votre n° d'Ordre.</p>
        <form method="POST" action="{{ route('register.medecin') }}">
          @csrf
          <div class="grid2">
            <div class="field">
              <label>Prénom</label>
              <input name="prenom" value="{{ old('prenom') }}" required placeholder="Fatou">
            </div>
            <div class="field">
              <label>Nom</label>
              <input name="nom" value="{{ old('nom') }}" required placeholder="Wade">
            </div>
          </div>
          <div class="field">
            <label>E-mail</label>
            <input type="email" name="email" value="{{ old('email') }}" required placeholder="vous@exemple.sn">
            @error('email') <div class="field-error">{{ $message }}</div> @enderror
          </div>
          <div class="grid2">
            <div class="field">
              <label>Structure</label>
              <select name="structure_id" required>
                <option value="">— Sélectionner —</option>
                @foreach ($structures as $s)
                    <option value="{{ $s->id }}" {{ old('structure_id') === $s->id ? 'selected' : '' }}>{{ $s->nom }}</option>
                @endforeach
              </select>
              @error('structure_id') <div class="field-error">{{ $message }}</div> @enderror
            </div>
            <div class="field">
              <label>Spécialité</label>
              <select name="specialite_id" required>
                <option value="">— Sélectionner —</option>
                @foreach ($specialites as $s)
                    <option value="{{ $s->id }}" {{ old('specialite_id') === $s->id ? 'selected' : '' }}>{{ $s->nom }}</option>
                @endforeach
              </select>
              @error('specialite_id') <div class="field-error">{{ $message }}</div> @enderror
            </div>
          </div>
          <div class="field">
            <label>Numéro d'Ordre</label>
            <input name="num_ordre" value="{{ old('num_ordre') }}" required placeholder="SN-XXXX">
            @error('num_ordre') <div class="field-error">{{ $message }}</div> @enderror
          </div>
          <div class="grid2">
            <div class="field">
              <label>Mot de passe</label>
              <input type="password" name="password" required minlength="8" placeholder="8 caractères minimum">
              @error('password') <div class="field-error">{{ $message }}</div> @enderror
            </div>
            <div class="field">
              <label>Confirmation</label>
              <input type="password" name="password_confirmation" required>
            </div>
          </div>
          <button class="btn btn-primary" style="width:100%;justify-content:center">Envoyer ma demande <svg><use href="#i-arrow"/></svg></button>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
