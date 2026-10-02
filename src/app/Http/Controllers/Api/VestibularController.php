<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\VestibularSectionResource;
use App\Models\VestibularSection;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

/** Página /vestibular no site público (`/api/vestibular`). */
class VestibularController extends Controller
{
    #[OA\Get(
        path: '/vestibular',
        summary: 'Seções e blocos da página /vestibular',
        description: 'Na ordem do painel. Link ainda sem endereço nem arquivo e vídeo sem URL não vêm.',
        tags: ['Site · Vestibular'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'As seções, cada uma com os blocos visíveis.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/VestibularSection')),
                ], type: 'object'),
            ),
        ],
    )]
    public function show(): AnonymousResourceCollection
    {
        $secoes = VestibularSection::ordered()
            ->with(['blocks' => fn ($q) => $q->visible()])
            ->get();

        return VestibularSectionResource::collection($secoes);
    }
}
