#!/bin/bash
# Préparation unique du graphe routier OSRM pour le Sénégal (mémoire, section 8).
# Télécharge l'extrait Geofabrik puis exécute la chaîne extract -> partition -> customize
# nécessaire à l'algorithme MLD utilisé par osrm-routed.
set -e

cd /data
PBF="senegal-and-gambia-latest.osm.pbf"

if [ ! -f "$PBF" ]; then
    echo "ERREUR : $PBF absent du volume osrmdata." >&2
    echo "Le service osrm-fetch doit s'exécuter d'abord :" >&2
    echo "  docker compose --profile osm-prep up" >&2
    exit 1
fi

echo "==> osrm-extract (profil voiture)…"
osrm-extract -p /opt/car.lua "$PBF"

echo "==> osrm-partition…"
osrm-partition senegal-and-gambia-latest.osrm

echo "==> osrm-customize…"
osrm-customize senegal-and-gambia-latest.osrm

echo "==> Graphe routier prêt dans le volume osrmdata."
ls -lh /data/senegal-and-gambia-latest.osrm* | head -5
