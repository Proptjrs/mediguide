@extends('layouts.app', ['title' => 'Connexion — MediGuide'])

@section('content')
<div class="wrap" style="padding-top:56px;padding-bottom:96px">
  <div class="auth-shell">
    <div class="auth-shell-in">
      <div class="auth-side">
        <div>
          <h3>Un espace pour chaque acteur du parcours de soin.</h3>
          <p>MediGuide réunit patients, médecins et administrateurs sur une même plateforme : chaque rôle n'accède qu'à ce qui le concerne.</p>
          <div class="auth-role-list">
            <div class="auth-role-btn"><svg><use href="#i-heart"/></svg><span>Patient<small>S'orienter et prendre rendez-vous</small></span></div>
            <div class="auth-role-btn"><svg><use href="#i-kit"/></svg><span>Médecin<small>Consulter son agenda et ses patients</small></span></div>
            <div class="auth-role-btn"><svg><use href="#i-shield"/></svg><span>Administrateur<small>Gérer structures, comptes et plannings</small></span></div>
          </div>
        </div>
        <p style="font-size:.78rem;color:#7DD3FC;margin-top:24px">Pas encore de compte ? <a href="{{ route('register') }}" style="color:#BAE6FD;font-weight:700;text-decoration:underline">Créez-en un en une minute</a> — la confirmation se fait par e-mail.</p>
      </div>
      <div class="auth-form">
        <div class="auth-tabs">
          <a class="auth-tab on" href="{{ route('login') }}">Connexion</a>
          <a class="auth-tab" href="{{ route('register') }}">Inscription</a>
        </div>
        <h2>Bon retour</h2>
        <p class="sub">Accédez à votre espace MediGuide.</p>
        <form method="POST" action="{{ route('login') }}">
          @csrf
          <div class="field">
            <label>E-mail</label>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="vous@exemple.sn">
            @error('email') <div class="field-error">{{ $message }}</div> @enderror
          </div>
          <div class="field">
            <label>Mot de passe</label>
            <input type="password" name="password" required placeholder="••••••••">
          </div>
          <p style="text-align:right;margin:-8px 0 14px;font-size:.86rem"><a href="{{ route('password.request') }}" style="color:var(--blue-dark);font-weight:600">Mot de passe oublié ?</a></p>
          <div style="display:flex;align-items:center;gap:10px;margin:-6px 0 20px">
            <input type="checkbox" name="remember" id="remember" style="width:18px;height:18px;accent-color:var(--blue)">
            <label for="remember" style="margin:0;font-weight:500;font-size:.88rem;color:var(--muted)">Se souvenir de moi</label>
          </div>
          <button class="btn btn-primary" style="width:100%;justify-content:center">Se connecter <svg><use href="#i-arrow"/></svg></button>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
