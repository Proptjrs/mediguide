@extends('layouts.app', ['title' => 'Mot de passe oublié — MediGuide'])

@section('content')
<div class="wrap dash-shell" style="max-width:560px">
    <div class="panel" style="padding:40px">
        <h2 class="k-title" style="font-size:1.4rem">Mot de passe oublié</h2>
        <p class="k-sub" style="margin:10px 0 26px;font-size:.95rem">
            Indiquez l'adresse de votre compte : nous vous enverrons un lien pour choisir
            un nouveau mot de passe. Le lien est valable une heure.
        </p>

        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            <div class="field">
                <label>Adresse e-mail</label>
                <input type="email" name="email" value="{{ old('email') }}" required data-focus-large placeholder="vous@exemple.sn">
                @error('email') <div class="field-error">{{ $message }}</div> @enderror
            </div>
            <button class="btn btn-primary" style="width:100%;justify-content:center">
                Envoyer le lien <svg><use href="#i-arrow"/></svg>
            </button>
        </form>

        <p style="text-align:center;margin-top:20px;font-size:.88rem">
            <a href="{{ route('login') }}" style="color:var(--blue-dark);font-weight:600">Retour à la connexion</a>
        </p>
    </div>
</div>
@endsection
