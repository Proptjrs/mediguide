@extends('layouts.app', ['title' => 'Comptes utilisateurs — MediGuide'])

@section('content')
<div class="wrap dash-shell">
    <h2 class="k-title">Comptes utilisateurs</h2>
    <p class="k-sub" style="margin-bottom:28px">Créer, modifier, suspendre ou supprimer un compte (chap. 2 du mémoire).</p>

    <div class="panel">
        <h3>Rechercher
            <a href="{{ route('admin.utilisateurs.create') }}" class="btn btn-primary btn-sm">+ Nouveau compte</a></h3>
        <form method="GET" action="{{ route('admin.utilisateurs.index') }}" style="display:flex;gap:14px;align-items:flex-end">
            <div class="field" style="margin:0;flex:1">
                <label>Nom, prénom ou e-mail</label>
                <input type="text" name="q" value="{{ $recherche }}" placeholder="Rechercher…">
            </div>
            <button class="btn btn-outline btn-sm">Rechercher</button>
        </form>
    </div>

    <div class="panel">
        <h3>{{ $utilisateurs->total() }} compte{{ $utilisateurs->total() > 1 ? 's' : '' }}</h3>
        @forelse ($utilisateurs as $u)
            <div class="row-item">
                <div class="ava" style="width:44px;height:44px;font-size:1rem;border-radius:12px">
                    {{ strtoupper(substr($u->prenom, 0, 1) . substr($u->nom, 0, 1)) }}
                </div>
                <div class="grow">
                    <h4>{{ $u->fullName() }}</h4>
                    <div class="sub">{{ $u->email }}</div>
                </div>

                <span class="pill {{ $u->role === 'admin' ? 'r' : ($u->role === 'medecin' ? 'b'
                    : ($u->role === 'secretaire' ? 'o' : 'g')) }}">{{ $u->role }}</span>

                @if ($u->email_verified_at)
                    <span class="pill g" title="Adresse confirmée">Confirmé</span>
                @else
                    <span class="pill o" title="Lien de confirmation non cliqué">Non confirmé</span>
                @endif

                @if ($u->role === 'medecin')
                    <span class="pill {{ $u->medecin?->valide ? 'g' : 'o' }}">
                        {{ $u->medecin?->valide ? 'n° Ordre validé' : 'n° Ordre en attente' }}</span>
                @endif

                @unless ($u->actif) <span class="pill r">Suspendu</span> @endunless

                <a href="{{ route('admin.utilisateurs.edit', $u) }}" class="btn btn-outline btn-sm">Modifier</a>

                <form method="POST" action="{{ route('admin.utilisateurs.activation', $u) }}">
                    @csrf @method('PATCH')
                    <button class="btn btn-ghost btn-sm">{{ $u->actif ? 'Suspendre' : 'Réactiver' }}</button>
                </form>

                <form method="POST" action="{{ route('admin.utilisateurs.destroy', $u) }}"
                      onsubmit="return confirm('Supprimer définitivement le compte de {{ $u->fullName() }} ?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-ghost btn-sm" style="color:var(--red)">Supprimer</button>
                </form>
            </div>
        @empty
            <p style="color:var(--muted);margin:0">Aucun compte trouvé.</p>
        @endforelse
    </div>

    <div>{{ $utilisateurs->links() }}</div>
    <a href="{{ route('dashboard') }}" class="btn btn-outline btn-sm">← Retour à l'administration</a>
</div>
@endsection
