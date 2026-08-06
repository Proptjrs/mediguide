@extends('layouts.app', ['title' => 'Confirmez votre adresse — MediGuide'])

@section('content')
<div class="wrap dash-shell" style="max-width:640px">
    <div class="panel" style="text-align:center;padding:44px">
        <div style="width:64px;height:64px;margin:0 auto 20px;border-radius:50%;background:var(--blue-pale);display:flex;align-items:center;justify-content:center">
            <svg style="width:30px;height:30px;stroke:var(--blue-dark);stroke-width:1.8;fill:none"><use href="#i-mail"/></svg>
        </div>

        <h2 class="k-title" style="font-size:1.5rem">Confirmez votre adresse e-mail</h2>
        <p class="k-sub" style="margin:12px auto 8px">
            Nous venons d'envoyer un lien de confirmation à
            <b style="color:var(--text-dark)">{{ auth()->user()->email }}</b>.
        </p>
        <p class="k-sub" style="margin:0 auto 28px;font-size:.92rem">
            Cliquez ce lien pour activer votre espace. C'est aussi à cette adresse que vous
            recevrez vos confirmations de rendez-vous et vos rappels — elle doit donc être
            une adresse que vous consultez réellement.
        </p>

        <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button class="btn btn-primary btn-sm">Renvoyer le lien <svg><use href="#i-arrow"/></svg></button>
            </form>
            <a href="{{ route('profil') }}" class="btn btn-outline btn-sm">Corriger mon adresse</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn btn-ghost btn-sm">Se déconnecter</button>
            </form>
        </div>

        <p style="font-size:.82rem;color:var(--muted-2);margin-top:24px">
            Pensez à regarder dans les courriers indésirables. Le lien est valable 60 minutes.
        </p>
    </div>
</div>
@endsection
