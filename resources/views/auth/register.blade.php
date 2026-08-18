@extends('layouts.app', ['title' => 'Inscription — MediGuide'])

@section('content')
<div class="wrap" style="padding-top:56px;padding-bottom:96px">
  <div class="auth-shell">
    <div class="auth-shell-in">
      <div class="auth-side">
        <div>
          <h3>Un espace pour chaque acteur du parcours de soin.</h3>
          <p>MediGuide réunit patients, médecins, secrétariats et administrateurs sur une même plateforme : chaque rôle n'accède qu'à ce qui le concerne.</p>
          <div class="auth-role-list">
            <div class="auth-role-btn"><svg><use href="#i-heart"/></svg><span>Patient<small>S'orienter et prendre rendez-vous</small></span></div>
            <div class="auth-role-btn"><svg><use href="#i-kit"/></svg><span>Médecin<small>Consulter son agenda et ses patients</small></span></div>
            <div class="auth-role-btn"><svg><use href="#i-cal"/></svg><span>Secrétariat<small>Tenir l'agenda du médecin</small></span></div>
            <div class="auth-role-btn"><svg><use href="#i-shield"/></svg><span>Administrateur<small>Structures, comptes et plannings</small></span></div>
          </div>
        </div>
        <p style="font-size:.78rem;color:#7DD3FC;margin-top:24px">Vous êtes médecin ? <a href="{{ route('register.medecin') }}" style="color:#BAE6FD;font-weight:700;text-decoration:underline">Inscrivez-vous ici</a> — votre numéro d'Ordre sera vérifié par l'administrateur.</p>
      </div>
      <div class="auth-form">
        <div class="auth-tabs">
          <a class="auth-tab" href="{{ route('login') }}">Connexion</a>
          <a class="auth-tab on" href="{{ route('register') }}">Inscription</a>
        </div>
        <h2>Créer mon compte patient</h2>
        <p class="sub">Gratuit, en une minute (UC-P1).</p>
        <form method="POST" action="{{ route('register') }}">
          @csrf
          <div class="grid2">
            <div class="field">
              <label>Prénom</label>
              <input name="prenom" value="{{ old('prenom') }}" required placeholder="Awa">
            </div>
            <div class="field">
              <label>Nom</label>
              <input name="nom" value="{{ old('nom') }}" required placeholder="Ndiaye">
            </div>
          </div>
          <div class="field">
            <label>E-mail</label>
            <input type="email" name="email" value="{{ old('email') }}" required placeholder="vous@exemple.sn">
            @error('email') <div class="field-error">{{ $message }}</div> @enderror
          </div>
          <div class="grid2">
            <div class="field">
              <label>Téléphone</label>
              <input name="telephone" value="{{ old('telephone') }}" placeholder="+221 …">
            </div>
            <div class="field">
              <label>Naissance</label>
              <input type="date" name="date_naissance">
            </div>
          </div>
          <div class="field">
            <label>Sexe</label>
            <select name="sexe">
              <option value="">—</option><option value="F">Féminin</option><option value="M">Masculin</option>
            </select>
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
          <button class="btn btn-primary" style="width:100%;justify-content:center">Créer mon compte <svg><use href="#i-arrow"/></svg></button>
        </form>
        {{-- Seul le patient s'inscrit librement : le médecin passe par un formulaire
             distinct (n° d'Ordre à vérifier) et l'administrateur est créé en interne.
             Ce renvoi doit rester bien visible, sinon un médecin remplit le mauvais
             formulaire et se retrouve avec un compte patient. --}}
        <a href="{{ route('register.medecin') }}" class="auth-switch">
          <svg><use href="#i-kit"/></svg>
          <span><b>Vous êtes médecin ?</b>
            <small>Inscription dédiée — votre n° d'Ordre sera vérifié par l'administrateur.</small></span>
          <svg class="auth-switch-go"><use href="#i-arrow"/></svg>
        </a>
      </div>
    </div>
  </div>
</div>
@endsection
