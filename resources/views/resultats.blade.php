@extends('layouts.app', ['title' => 'Structures proches — MediGuide'])

@section('content')
<div class="wrap">
    <div class="res-head">
        <span class="res-badge"><svg style="width:15px;height:15px"><use href="#i-compass"/></svg> Spécialité recommandée : <b>{{ $spec }}</b></span>
        <div class="sec-head" style="margin-top:18px;margin-bottom:0">
            <h2 class="k-title">Structures proches de vous</h2>
            <p class="k-sub">Triées par distance (formule de Haversine) · temps de trajet estimés via OSRM · Centre Hospitalier Roi Baudouin et structures environnantes.</p>
        </div>
    </div>

    <div class="res-grid">
        <div class="res-list">
            @forelse ($structures as $i => $s)
                <div class="res-card {{ $i === 0 ? 'top-pick' : '' }}">
                    @if ($i === 0) <span class="badge-top">Le plus proche</span> @endif
                    <div class="top">
                        <div>
                            <div class="type">{{ str_replace('_', ' ', $s->type) }}</div>
                            <h3>{{ $s->nom }}</h3>
                        </div>
                        <span class="rank">{{ $i + 1 }}</span>
                    </div>
                    <div class="meta">
                        <span><svg><use href="#i-pin"/></svg> <b>{{ $s->distance_km }} km</b></span>
                        <span><svg><use href="#i-users"/></svg> à pied <b>{{ $s->duree['pied_min'] }} min</b></span>
                        <span><svg><use href="#i-cal"/></svg> en voiture <b>{{ $s->duree['voiture_min'] }} min</b></span>
                    </div>
                    @php $doc = $s->medecins->first(); @endphp
                    @if ($doc)
                        <div class="act">
                            <a href="{{ route('calendrier', $doc) }}" class="btn btn-primary btn-sm">
                                Prendre RDV <svg><use href="#i-arrow"/></svg>
                            </a>
                        </div>
                    @endif
                </div>
            @empty
                <div class="panel" style="border-color:var(--amber);background:var(--amber-pale)">
                    <p style="margin:0;color:#92400E">Aucune structure du district ne propose {{ $spec }} pour le moment.
                    Les urgences de l'Hôpital Roi Baudouin restent ouvertes 24 h/24.</p>
                </div>
            @endforelse
        </div>
        <div id="map" role="application" aria-label="Carte des structures médicales"></div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    const map = L.map('map').setView([{{ $lat }}, {{ $lng }}], 14);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png',
        { attribution: '© OpenStreetMap contributors' }).addTo(map);

    const youIcon = L.divIcon({className:'mg-you-marker', html:'<div style="width:16px;height:16px;border-radius:50%;background:#0284C7;border:3px solid #fff;box-shadow:0 2px 8px rgba(2,132,199,.5)"></div>', iconSize:[16,16]});
    L.marker([{{ $lat }}, {{ $lng }}], {icon: youIcon}).addTo(map).bindPopup('<b>Vous êtes ici</b>');

    function mgPin(n, color){
        return L.divIcon({
            className:'mg-marker mg-marker-' + (n % 5),
            html:'<div class="mg-pin" style="--pin-color:' + color + '"><span>' + (n+1) + '</span></div>',
            iconSize:[34,38], iconAnchor:[17,36],
        });
    }
    @foreach ($structures as $i => $s)
        L.marker([{{ $s->latitude }}, {{ $s->longitude }}], {icon: mgPin({{ $i }}, '{{ $i === 0 ? "#0284C7" : "#0369A1" }}')}).addTo(map)
            .bindPopup('<b>{{ $i + 1 }}. {{ $s->nom }}</b><br>{{ $spec }} · {{ $s->distance_km }} km');
    @endforeach
</script>
@endsection
