<?php

namespace App\Http\Requests\Certificado;

use Illuminate\Foundation\Http\FormRequest;

class StoreCertificadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Autorização via Gate/Policy
    }

    public function rules(): array
    {
        return [
            // atualizado: agora valida o ID da categoria
            'categoria_id' => ['required', 'integer', 'exists:categorias,id'],

            'nome_certificado' => ['required', 'string', 'max:255'],
            'instituicao' => ['required', 'string', 'max:255'],
            'data_emissao' => ['required', 'date'],
            'carga_horaria_solicitada' => ['required', 'integer', 'min:1'],

            // arquivo PDF até 10MB
            'arquivo' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ];
    }
}
