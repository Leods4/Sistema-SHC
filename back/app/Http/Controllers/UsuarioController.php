<?php

namespace App\Http\Controllers;

use App\Enums\StatusCertificado;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Usuario\StoreUsuarioRequest;
use App\Http\Requests\Usuario\UpdateUsuarioRequest;
use App\Http\Resources\UserResource;
use App\Http\Resources\ProgressoResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    /**
     * Lista usuários
     */
    public function index(Request $request)
    {
        // Gate 'manage-users' já aplicado na rota (Admin / Secretaria)
        $query = User::query()->with('curso');

        // Filtro por tipo
        if ($request->has('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        return UserResource::collection($query->get());
    }

    /**
     * Cria usuário
     * Agora senha é autogerada pelo Model caso data_nascimento esteja presente
     */
    public function store(StoreUsuarioRequest $request)
    {
        $data = $request->validated();
        $user = User::create($data);

        return new UserResource($user);
    }

    /**
     * Atualiza usuário
     */
    public function update(UpdateUsuarioRequest $request, User $user)
    {
        $validated = $request->validated();
        $user->update($validated);

        return new UserResource($user);
    }

    /**
     * Remove usuário
     */
    public function destroy(User $user)
    {
        $user->delete();
        return response()->noContent();
    }

    /**
     * Retorna progresso do aluno
     */
    public function getProgresso(User $user)
    {
        $totalAprovadas = $user->certificadosSubmetidos()
            ->whereIn('status', [
                StatusCertificado::APROVADO,
                StatusCertificado::APROVADO_COM_RESSALVAS
            ])
            ->sum('horas_validadas');

        $horasNecessarias = $user->curso->horas_necessarias ?? 0;

        return new ProgressoResource([
            'total_horas_aprovadas' => (int) $totalAprovadas,
            'horas_necessarias' => (int) $horasNecessarias,
        ]);
    }

    /**
     * Atualiza avatar do usuário logado
     */
    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => ['required', 'image', 'max:2048'],
        ]);

        $user = Auth::user();

        // Remove avatar antigo
        if ($user->avatar_url) {
            Storage::disk('public')->delete($user->avatar_url);
        }

        // Salva novo
        $path = $request->file('avatar')->store('avatars', 'public');
        $user->update(['avatar_url' => $path]);

        return response()->json([
            'avatar_url' => Storage::url($path)
        ]);
    }

    public function import(Request $request)
    {
        // 1. Validação básica: garante que enviaram uma lista
        $request->validate([
            'usuarios' => ['required', 'array'],
            'usuarios.*.nome' => ['required'], // Validação mínima dentro do array
            'usuarios.*.email' => ['required', 'email'],
            'usuarios.*.cpf' => ['required'],
            'usuarios.*.tipo' => ['required'],
        ]);

        $count = 0;

        // 2. Processamento em Loop
        foreach ($request->usuarios as $userData) {

            // Lógica de senha (se vier vazia, usa data nascimento ou padrão)
            $password = $userData['password'] ?? '12345678';

            // Se a senha não parecer um hash (não começa com $2y$), faz o hash
            if (!str_starts_with($password, '$2y$')) {
                // Se tiver data de nascimento e não tiver senha, usa data como senha
                if (isset($userData['data_nascimento']) && empty($userData['password'])) {
                    $password = \Carbon\Carbon::parse($userData['data_nascimento'])->format('dmY');
                }
                $password = Hash::make($password);
            }

            // 3. Cria ou Atualiza (Busca pelo CPF para não duplicar)
            User::updateOrCreate(
                ['cpf' => $userData['cpf']],
                [
                    'nome' => $userData['nome'],
                    'email' => $userData['email'],
                    'data_nascimento' => $userData['data_nascimento'] ?? null,
                    'password' => $password,
                    'tipo' => $userData['tipo'],
                    'curso_id' => $userData['curso_id'] ?? null,
                    'fase' => $userData['fase'] ?? null,
                    'matricula' => $userData['matricula'] ?? null,
                ]
            );
            $count++;
        }

        return response()->json([
            'message' => "Importação concluída. {$count} usuários processados.",
        ]);
    }

}
