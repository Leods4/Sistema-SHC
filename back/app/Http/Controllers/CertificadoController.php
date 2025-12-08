<?php

namespace App\Http\Controllers;

use App\Enums\StatusCertificado;
use App\Models\Certificado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Certificado\StoreCertificadoRequest;
use App\Http\Requests\Certificado\AvaliacaoRequest;
use App\Http\Resources\CertificadoResource;

class CertificadoController extends Controller
{
    /**
     * INDEX — Listagem com filtros por regras de permissão e filtros avançados
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Agora inclui a categoria
        $query = Certificado::query()->with('aluno', 'coordenador', 'categoria');

        /**
         * 🔍 FILTROS GLOBAIS
         */

        // 1. Filtro por aluno
        if ($request->filled('aluno_id')) {
            $query->where('aluno_id', $request->aluno_id);
        }

        // 2. Busca por nome/cpf do aluno
        if ($request->filled('search')) {
            $term = $request->search;

            $query->whereHas('aluno', function ($q) use ($term) {
                $q->where('nome', 'like', "%{$term}%")
                  ->orWhere('cpf', 'like', "%{$term}%");
            });
        }

        // 3. Intervalo de datas
        if ($request->filled('data_inicio') && $request->filled('data_fim')) {
            $query->whereBetween('data_emissao', [
                $request->data_inicio,
                $request->data_fim,
            ]);
        }

        // 4. Filtro por curso (somente Secretaria/Admin)
        if (
            $request->filled('curso_id') &&
            ($user->isSecretaria() || $user->isAdmin())
        ) {
            $query->whereHas('aluno', function ($q) use ($request) {
                $q->where('curso_id', $request->curso_id);
            });
        }

        // 5. (NOVO) filtro por categoria
        if ($request->filled('categoria_id')) {
            $query->where('categoria_id', $request->categoria_id);
        }

        /**
         * 👤 REGRAS POR PAPEL DO USUÁRIO
         */

        if ($user->isAluno()) {
            $query->where('aluno_id', $user->id);

        } elseif ($user->isCoordenador()) {

            // Coordenador só vê alunos do seu curso
            $query->whereHas('aluno', fn($q) =>
                $q->where('curso_id', $user->curso_id)
            );

            // Listar apenas ENTREGUES se solicitado
            if ($request->status === 'ENTREGUE') {
                $query->where('status', StatusCertificado::ENTREGUE);
            }

        } elseif ($user->isSecretaria()) {
            // Secretaria vê tudo (com filtros opcionais)
        }
        // Admin também vê tudo

        return CertificadoResource::collection(
            $query->latest()->get()
        );
    }

    /**
     * STORE — Aluno envia certificado
     */
    public function store(StoreCertificadoRequest $request)
    {
        $path = $request->file('arquivo')->store('certificados', 'public');

        $certificado = Certificado::create([
            ...$request->validated(),
            'arquivo_url' => $path,
            'aluno_id' => Auth::id(),
            'status' => StatusCertificado::ENTREGUE,
        ]);

        // Necessário para o Resource retornar categoria
        $certificado->load('categoria');

        return new CertificadoResource($certificado);
    }

    /**
     * AVALIAR — Coordenador aprova/reprova o certificado
     */
    public function avaliar(Certificado $certificado, AvaliacaoRequest $request)
    {
        $data = $request->validated();

        // Se reprovado → horas validadas = 0
        if ($data['status'] === StatusCertificado::REPROVADO->value) {
            $data['horas_validadas'] = 0;
        }

        $certificado->update([
            ...$data,
            'coordenador_id' => Auth::id(),
            'data_validacao' => now(),
        ]);

        // Recarrega categoria para o Resource
        $certificado->load('categoria');

        return new CertificadoResource($certificado);
    }
}
