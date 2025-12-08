<?php

namespace App\Http\Requests\Usuario;

use App\Enums\TipoUsuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user(); // usuário autenticado
        $targetUserId = $this->route('user')->id; // usuário sendo editado

        // Admin e Secretaria podem editar qualquer usuário
        if ($user->isAdmin() || $user->isSecretaria()) {
            return true;
        }

        // O próprio usuário pode editar o próprio perfil
        return $user->id === $targetUserId;
    }

    public function rules(): array
    {
        $userId = $this->route('user')->id;

        return [
            'nome' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users')->ignore($userId),
            ],

            'cpf' => [
                'required',
                'string',
                'max:14',
                Rule::unique('users')->ignore($userId),
            ],

            'matricula' => [
                'nullable',
                'string',
                Rule::unique('users')->ignore($userId),
            ],

            // ➜ Campo novo
            'data_nascimento' => ['required', 'date'],

            // Senha opcional
            'password' => ['nullable', 'string', 'min:6'],

            'tipo' => ['required', Rule::enum(TipoUsuario::class)],

            'curso_id' => ['nullable', 'exists:cursos,id'],

            'fase' => ['nullable', 'integer'],
        ];
    }
}
