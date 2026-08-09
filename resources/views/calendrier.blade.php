@extends('layouts.app', ['title' => 'Prise de rendez-vous — MediGuide'])

@section('content')
<div class="cal-shell">
    <h2 class="k-title">Choisissez votre créneau</h2>
    <p class="k-sub">Les créneaux marqués « Libre » sont disponibles. Un clic suffit pour réserver.</p>

    <div class="cal-doc" style="margin-top:28px">
        <div class="ava">{{ strtoupper(substr($medecin->utilisateur->prenom, 0, 1) . substr($medecin->utilisateur->nom, 0, 1)) }}</div>
        <div>
            <h3>{{ $medecin->utilisateur->fullName() }}</h3>
            <div class="s">{{ $medecin->specialite->nom }} · {{ $medecin->structure->nom }}</div>
        </div>
    </div>

    @error('creneau') <div class="field-error" style="margin-top:16px">{{ $message }}</div> @enderror


    <div class="cal-week">
        <a class="wk-btn" href="{{ route('calendrier', [$medecin, 'semaine' => $lundi->copy()->subWeek()->toDateString()]) }}" aria-label="Semaine précédente">‹</a>
        <h4>Semaine du {{ $lundi->translatedFormat('j F Y') }}</h4>
        <a class="wk-btn" href="{{ route('calendrier', [$medecin, 'semaine' => $lundi->copy()->addWeek()->toDateString()]) }}" aria-label="Semaine suivante">›</a>
    </div>

    <div class="cal-grid">
        @foreach ($semaine as $jour)
            <div class="cal-day">
                <div class="dh">
                    {{ $jour['date']->translatedFormat('l') }}
                    <small>{{ $jour['date']->format('d/m') }}</small>
                </div>
                <div class="slots">
                    @forelse ($jour['creneaux'] as $c)
                        @if ($c['libre'])
                            @auth
                                <form method="POST" action="{{ route('rdv.reserver', $medecin) }}">
                                    @csrf
                                    <input type="hidden" name="date_heure" value="{{ $c['datetime'] }}">
                                    <button class="slot free">{{ $c['heure'] }} · Libre</button>
                                </form>
                            @else
                                {{-- Créneau déjà pris ou hors planning. --}}
                                <a href="{{ route('login') }}" class="slot free" style="display:block">{{ $c['heure'] }} · Libre</a>
                            @endauth
                        @else
                            <button class="slot busy" disabled>{{ $c['heure'] }}</button>
                        @endif
                    @empty
                        <p style="font-size:.82rem;color:var(--muted-2);text-align:center;padding:20px 0">Pas de consultation</p>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>

    <div class="cal-legend">
        <span class="lg"><i style="background:var(--green-pale)"></i> Disponible</span>
        <span class="lg"><i style="background:var(--bg);border:1px solid var(--border)"></i> Occupé</span>
    </div>
    <p style="font-size:.82rem;color:var(--muted-2);margin-top:20px">Réservation protégée contre les doubles bookings
        (transaction + verrou pessimiste). Confirmation immédiate par e-mail.</p>
</div>
@endsection
