<?php
namespace App\Http\Requests\Certificado;
use App\Enums\StatusCertificado;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class AvaliacaoRequest extends FormRequest
{
    public function authorize(): bool { return true; } // Autorização é feita no Gate da rota
    public function rules(): array
    {
        return [
            // [cite: 38]
            'status' => ['required', new Enum(StatusCertificado::class)],
            'horas_validadas' => ['required_if:status,APROVADO,APROVADO_COM_RESSALVAS', 'nullable', 'integer', 'min:0'],
            'observacao' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
