<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\CertificadoController;
use App\Http\Controllers\ConfiguracaoController;
use App\Http\Controllers\CursoController;
use App\Http\Controllers\CategoriaController;


// 1. Autenticação (Público)
// [cite: 19]
Route::post('/auth/login', [AuthController::class, 'login']);

// 2. Rotas Protegidas (Requerem Token)
Route::middleware('auth:sanctum')->group(function () {

    // 2.1. Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    // [cite: 56]
    Route::post('/auth/change-password', [AuthController::class, 'changePassword']);
    // [cite: 55] (Rota para o *próprio* usuário logado)
    Route::post('/usuarios/avatar', [UsuarioController::class, 'updateAvatar']);

    // 2.2. Usuários (CRUD)
    // [cite: 43] (Listar)
    Route::get('/usuarios', [UsuarioController::class, 'index'])->middleware('can:manage-users');
    // [cite: 43] (Criar)
    Route::post('/usuarios', [UsuarioController::class, 'store'])->middleware('can:manage-users');

    Route::prefix('usuarios/{user}')->scopeBindings()->group(function () {
        Route::get('/', [UsuarioController::class, 'show'])->middleware('can:manage-users');
        // [cite: 44] (Atualizar)
        Route::put('/', [UsuarioController::class, 'update'])->middleware('can:manage-users');
        // [cite: 45] (Remover)
        Route::delete('/', [UsuarioController::class, 'destroy'])->middleware('can:manage-users');
        // [cite: 32] (Progresso do Aluno)
        Route::get('/progresso', [UsuarioController::class, 'getProgresso'])->middleware('can:view-progresso,user');
    });

    // 2.3. Certificados
    // [cite: 29, 35, 46] (Listagem dinâmica baseada no perfil)
    Route::get('/certificados', [CertificadoController::class, 'index']);
    // [cite: 28] (Aluno envia)
    Route::post('/certificados', [CertificadoController::class, 'store'])->middleware('can:is-aluno');

    Route::prefix('certificados/{certificado}')->scopeBindings()->group(function () {
        Route::get('/', [CertificadoController::class, 'show']); // Adicionar policy (aluno dono, coord, sec, admin)
        // [cite: 39] (Coordenador avalia)
        Route::patch('/avaliar', [CertificadoController::class, 'avaliar'])->middleware('can:avaliar-certificado,certificado');
    });

    // 2.4. Configurações (Admin)
    // [cite: 51]
    Route::get('/configuracoes', [ConfiguracaoController::class, 'index'])->middleware('can:is-admin');
    Route::put('/configuracoes', [ConfiguracaoController::class, 'update'])->middleware('can:is-admin');

    // 2.5. Cursos
    // (Leitura: Disponível para selects em formulários de qualquer usuário)
    Route::get('/cursos', [CursoController::class, 'index']);

    // (Gestão: Restrita ao Administrador)
    Route::post('/cursos', [CursoController::class, 'store'])->middleware('can:is-admin');

    Route::prefix('cursos/{curso}')->scopeBindings()->group(function () {
        Route::get('/', [CursoController::class, 'show']); // Ver detalhes
        Route::put('/', [CursoController::class, 'update'])->middleware('can:is-admin');
        Route::delete('/', [CursoController::class, 'destroy'])->middleware('can:is-admin');
    });

    // 2.6. Categorias (Novo)
    // Listagem aberta para todos os usuários logados (para popular selects)
    Route::get('/categorias', [CategoriaController::class, 'index']);

    // Gestão restrita ao Administrador
    Route::post('/categorias', [CategoriaController::class, 'store'])->middleware('can:is-admin');
    Route::delete('/categorias/{categoria}', [CategoriaController::class, 'destroy'])->middleware('can:is-admin');

    // Rota Exclusiva de Importação (Admin)
    Route::post('/usuarios/import', [UsuarioController::class, 'import'])->middleware('can:is-admin');
});
