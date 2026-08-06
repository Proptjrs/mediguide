@extends('layouts.app', ['title' => 'Nouveau mot de passe — MediGuide'])

@section('content')
<div class="wrap dash-shell" style="max-width:560px">
    <div class="panel" style="padding:40px">
        <h2 class="k-title" style="font-size:1.4rem">Choisir un nouveau mot de passe</h2>
        <p class="k-sub" style="margin:10px 0 26px;font-size:.95rem">
            Vous pouvez maintenant définir le mot de passe de votre choix.
        </p>

        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="field">
                <label>Adresse e-mail</label>
                <input type="email" name="email" value="{{ old('email', $email) }}" required readonly
                       style="background:var(--border-2);cursor:not-allowed">
                @error('email') <div class="field-error">{{ $message }}</div> @enderror
            </div>
            <div class="field">
                <label>Nouveau mot de passe</label>
                <input type="password" name="password" required minlength="8" autofocus placeholder="8 caractères minimum">
                @error('password') <div class="field-error">{{ $message }}</div> @enderror
            </div>
            <div class="field">
                <label>Confirmation</label>
                <input type="password" name="password_confirmation" required minlength="8">
            </div>

            <button class="btn btn-primary" style="width:100%;justify-content:center">
                Enregistrer <svg><use href="#i-arrow"/></svg>
            </button>
        </form>
    </div>
</div>
@endsection
