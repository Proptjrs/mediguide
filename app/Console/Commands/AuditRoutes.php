<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
class AuditRoutes extends Command {
    protected $signature = 'audit:routes';
    protected $description = 'Verifie que chaque route pointe vers une action existante';
    public function handle(): int {
        $ko = 0; $total = 0; $autres = 0;
        foreach (Route::getRoutes() as $r) {
            $uri = $r->uri();
            if (preg_match('#^(_|sanctum|livewire|storage|up)#', $uri)) continue;
            $action = $r->getActionName();
            $total++;
            if (! str_contains($action, '@')) { $autres++; continue; }
            [$class, $method] = explode('@', $action);
            if (! class_exists($class)) { $this->error("  CLASSE MANQUANTE : $class ($uri)"); $ko++; continue; }
            if (! method_exists($class, $method)) { $this->error("  METHODE MANQUANTE : $action ($uri)"); $ko++; }
        }
        $this->info("  Total: $total | invocables/livewire: $autres | cassees: $ko");
        return $ko === 0 ? 0 : 1;
    }
}
