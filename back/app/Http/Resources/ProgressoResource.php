<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// [cite: 32] Formata a resposta do progresso
class ProgressoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'total_horas_aprovadas' => $this->resource['total_horas_aprovadas'],
            'horas_necessarias' => $this->resource['horas_necessarias'],
        ];
    }
}
