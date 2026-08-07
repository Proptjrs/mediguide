/**
 * Agent de service : mode hors connexion de MediGuide.
 *
 * Le district de Guédiawaye connaît des coupures fréquentes. L'application
 * garde donc en mémoire les pages publiques déjà consultées et les affiche
 * telles quelles lorsque le réseau tombe, plutôt qu'une erreur du navigateur.
 *
 * IMPORTANT — rien de personnel n'est mis en cache. Les espaces connectés
 * (dossier médical, agenda, administration) ne sont jamais conservés sur
 * l'appareil : sur un téléphone partagé, une page de dossier médical restée
 * en mémoire serait consultable par n'importe qui, hors connexion et sans
 * mot de passe. Seules les pages ouvertes à tous sont mémorisées.
 */
const CACHE = 'mediguide-v2';
const HORS_LIGNE = '/hors-ligne.html';
const SOCLE = [HORS_LIGNE, '/js/accessibilite.js', '/favicon.ico'];

/** Pages publiques : aucune donnée personnelle ne s'y affiche. */
const PAGES_PUBLIQUES = ['/', '/orientation', '/resultats', '/urgence'];

/** Ressources statiques, identiques pour tous les visiteurs. */
const EXTENSIONS = /\.(css|js|png|jpe?g|svg|webp|ico|woff2?)$/i;

function memorisable(url) {
    const chemin = url.pathname;
    if (EXTENSIONS.test(chemin)) return true;
    return PAGES_PUBLIQUES.includes(chemin.replace(/\/$/, '') || '/');
}

self.addEventListener('install', (e) => {
    e.waitUntil(caches.open(CACHE).then(c => c.addAll(SOCLE)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (e) => {
    e.waitUntil(
        caches.keys()
            .then(noms => Promise.all(noms.filter(n => n !== CACHE).map(n => caches.delete(n))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (e) => {
    const requete = e.request;
    const url = new URL(requete.url);

    // Une réservation ou une connexion doit impérativement atteindre le serveur.
    if (requete.method !== 'GET' || url.origin !== self.location.origin) return;

    e.respondWith(
        fetch(requete)
            .then((reponse) => {
                if (reponse.ok && reponse.type === 'basic' && memorisable(url)) {
                    const copie = reponse.clone();
                    caches.open(CACHE).then(c => c.put(requete, copie));
                }
                return reponse;
            })
            .catch(() => caches.match(requete).then(
                (enCache) => enCache
                    || (requete.mode === 'navigate' ? caches.match(HORS_LIGNE) : undefined)
            ))
    );
});
