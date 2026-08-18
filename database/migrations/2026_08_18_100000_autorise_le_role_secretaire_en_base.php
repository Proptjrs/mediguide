<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Ouvre le champ « role » au secrétariat, côté PostgreSQL.
 *
 * Sous PostgreSQL, une colonne déclarée « enum » n'est pas un type à part :
 * c'est une chaîne assortie d'une contrainte de vérification. Changer le type
 * de la colonne, comme l'a fait la migration précédente, ne supprime pas cette
 * contrainte — elle continuait donc de n'accepter que patient, medecin et
 * admin, et l'insertion d'une secrétaire faisait échouer le démarrage en
 * production alors que tout passait en développement, où MySQL avait bien
 * abandonné son énumération.
 *
 * La contrainte n'est pas simplement supprimée : elle est reposée avec les
 * quatre rôles. La base reste ainsi le dernier rempart si un jour une valeur
 * fantaisiste échappait à la validation applicative.
 */
return new class extends Migration
{
    private const ROLES = ['patient', 'medecin', 'secretaire', 'admin'];

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;                     // MySQL a déjà une simple chaîne
        }
        $valeurs = "'" . implode("', '", self::ROLES) . "'";
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ({$valeurs}))");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }
        // On revient aux trois rôles d'origine, après avoir écarté les comptes
        // qui ne s'y conforment plus : sans quoi la contrainte serait refusée.
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
        DB::table('users')->where('role', 'secretaire')->delete();
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check "
            . "CHECK (role IN ('patient', 'medecin', 'admin'))");
    }
};
