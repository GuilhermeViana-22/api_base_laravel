<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Respostas que se repetem em quase toda rota, declaradas uma vez só.
 *
 * As rotas as referenciam por `#/components/responses/<nome>` em vez de
 * redigir o mesmo 401 cinquenta vezes.
 */
#[OA\Response(
    response: 'Unauthorized',
    description: 'Sem token, token expirado ou inválido.',
    content: new OA\JsonContent(
        ref: '#/components/schemas/MessageResponse',
        example: ['message' => 'Unauthenticated.'],
    ),
)]
#[OA\Response(
    response: 'NotFound',
    description: 'O registro não existe (ou, no site, ainda não está no ar).',
    content: new OA\JsonContent(
        ref: '#/components/schemas/MessageResponse',
        example: ['message' => 'Not found.'],
    ),
)]
#[OA\Response(
    response: 'Forbidden',
    description: 'O papel de quem está logado não alcança este módulo ou esta ação. Vale para qualquer rota '
        .'do painel, não só as documentadas com este retorno.',
    content: new OA\JsonContent(
        ref: '#/components/schemas/AuthError',
        example: [
            'message' => 'Você não tem permissão para esta ação.',
            'code' => 'forbidden',
            'module' => 'posts',
            'action' => 'delete',
        ],
    ),
)]
#[OA\Response(
    response: 'ValidationError',
    description: 'Algum campo não passou na validação.',
    content: new OA\JsonContent(ref: '#/components/schemas/ValidationError'),
)]
#[OA\Response(
    response: 'TooManyRequests',
    description: 'Cinco tentativas por minuto, por e-mail + IP. O header `Retry-After` e o campo de mesmo nome dizem quantos segundos esperar.',
    content: new OA\JsonContent(
        ref: '#/components/schemas/AuthError',
        example: [
            'message' => 'Muitas tentativas. Aguarde um minuto e tente novamente.',
            'code' => 'too_many_requests',
            'retry_after' => 60,
        ],
    ),
)]
#[OA\Response(response: 'NoContent', description: 'Excluído. Sem corpo na resposta.')]
final class Responses {}
