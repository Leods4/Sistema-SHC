<?php

namespace App\Http\Requests\Usuario;

use App\Enums\TipoUsuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        // A autorização já é feita pela Policy/Gate na rota ('manage-users')
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users'],
            'cpf' => ['required', 'string', 'max:14', 'unique:users'],

            // Nova regra
            'data_nascimento' => ['required', 'date'],

            'matricula' => ['nullable', 'string', 'unique:users'],

            // Mudança: senha opcional
            'password' => ['nullable', 'string', 'min:6'],

            'tipo' => ['required', Rule::enum(TipoUsuario::class)],

            // Curso e Fase obrigatórios apenas para ALUNO
            'curso_id' => [
                'nullable',
                'exists:cursos,id',
                Rule::requiredIf($this->tipo === TipoUsuario::ALUNO->value)
            ],
            'fase' => [
                'nullable',
                'integer',
                Rule::requiredIf($this->tipo === TipoUsuario::ALUNO->value)
            ],
        ];
    }
}
