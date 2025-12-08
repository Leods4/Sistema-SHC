<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Certificado;
use App\Observers\AuditObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Resources\Json\JsonResource;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Remove wrapping do JSON
        JsonResource::withoutWrapping();

        // Observers
        User::observe(AuditObserver::class);
        Certificado::observe(AuditObserver::class);

        // Gates de Perfil
        Gate::define('is-admin', fn (User $user) => $user->isAdmin());
        Gate::define('is-secretaria', fn (User $user) => $user->isSecretaria());
        Gate::define('is-coordenador', fn (User $user) => $user->isCoordenador());
        Gate::define('is-aluno', fn (User $user) => $user->isAluno());

        // Gates de Ação
        Gate::define('manage-users', fn (User $user) => $user->isAdmin() || $user->isSecretaria() || $user->isCoordenador());
        Gate::define('view-all-history', fn (User $user) => $user->isAdmin() || $user->isSecretaria() || $user->isCoordenador());

        // Gates de Nível de Recurso
        Gate::define('avaliar-certificado', function (User $coordenador, $certificado) {
            if (!$coordenador->isCoordenador()) {
                return false;
            }

            return $coordenador->curso_id === $certificado->aluno->curso_id;
        });

        Gate::define('view-progresso', function (User $user, $aluno) {
            if ($user->isAdmin() || $user->isSecretaria()) {
                return true;
            }

            if ($user->isCoordenador() && $user->curso_id === $aluno->curso_id) {
                return true;
            }

            return $user->id === $aluno->id;
        });
    }
}
