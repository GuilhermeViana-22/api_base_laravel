<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResendCodeRequest;
use App\Http\Requests\VerifyEmailRequest;
use App\Http\Resources\AuthTokenResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\VerificationResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Autenticação da área restrita (`/api/auth/*`).
 *
 * Fluxo de cadastro:
 *   1. POST register      -> cria a conta e envia o código   (201, data = verificação)
 *   2. POST verify-email  -> confere o código e já faz login (200, data = sessão)
 *      POST resend-code   -> envia outro código              (200, data = verificação)
 *
 * O e-mail com o código sai antes da resposta: 201/200 significa que o
 * provedor aceitou a mensagem (`email_sent: true`). Se o envio falhar, 503
 * `verification_email_failed` e nada é gravado.
 *
 * Sucesso: `{ message, data }`. Erro de negócio: `{ message, code, ... }`
 * (ver App\Exceptions\AuthException). Validação: 422 `{ message, errors }`.
 */
class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    #[OA\Post(
        path: '/auth/register',
        summary: 'Cria a conta e envia o código de verificação',
        description: 'O e-mail sai antes da resposta: 201 significa que o provedor aceitou a mensagem. '
            .'Se o envio falhar, nada é gravado (503 `verification_email_failed`). '
            .'E-mail de conta ainda não confirmada pode se cadastrar de novo — recebe outro código.',
        tags: ['Autenticação'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255, minLength: 3, example: 'Maria Souza'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255, example: 'maria@exemplo.com'),
                    new OA\Property(property: 'password', description: 'Pelo menos 8 caracteres, com letras e números.', type: 'string', format: 'password', maxLength: 255, minLength: 8, example: 'univesp2026'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'univesp2026'),
                ],
                type: 'object',
            ),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Conta criada; o código está a caminho.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/Verification'),
                    new OA\Property(property: 'message', type: 'string', example: 'Cadastro recebido! Enviamos um código de verificação para o seu e-mail.'),
                    new OA\Property(property: 'email_sent', description: '`false` quando já havia um código recente: use aquele.', type: 'boolean', example: true),
                ], type: 'object'),
            ),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequests'),
            new OA\Response(
                response: 503,
                description: 'O provedor de e-mail recusou ou não respondeu a tempo; nada foi gravado.',
                content: new OA\JsonContent(ref: '#/components/schemas/AuthError'),
            ),
        ],
    )]
    public function register(RegisterRequest $request): JsonResponse
    {
        $verification = $this->authService->register($request->validated())['verification'];
        $sent = $verification->wasJustSent();

        return (new VerificationResource($verification))
            ->additional([
                'message' => $sent
                    ? 'Cadastro recebido! Enviamos um código de verificação para o seu e-mail.'
                    : 'Já enviamos um código para este e-mail há pouco. Use o código recebido.',
                'email_sent' => $sent,
            ])
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Post(
        path: '/auth/verify-email',
        summary: 'Confere o código e já devolve a sessão',
        description: 'Código certo confirma o e-mail e abre a sessão de uma vez: não é preciso chamar `login` depois.',
        tags: ['Autenticação'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'code'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'maria@exemplo.com'),
                    new OA\Property(property: 'code', description: 'Os dígitos recebidos por e-mail (ver `code_length`).', type: 'string', example: '123456'),
                ],
                type: 'object',
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'E-mail confirmado e sessão aberta.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/AuthSession'),
                    new OA\Property(property: 'message', type: 'string', example: 'E-mail confirmado! Boas-vindas à Área Restrita.'),
                ], type: 'object'),
            ),
            new OA\Response(
                response: 409,
                description: '`email_already_verified`: a conta já estava confirmada; basta fazer login.',
                content: new OA\JsonContent(ref: '#/components/schemas/AuthError'),
            ),
            new OA\Response(
                response: 410,
                description: '`verification_code_expired`: não há código válido; peça outro.',
                content: new OA\JsonContent(ref: '#/components/schemas/AuthError'),
            ),
            new OA\Response(
                response: 422,
                description: 'Campo inválido, ou `verification_code_invalid` com `attempts_remaining`.',
                content: new OA\JsonContent(oneOf: [
                    new OA\Schema(ref: '#/components/schemas/AuthError'),
                    new OA\Schema(ref: '#/components/schemas/ValidationError'),
                ]),
            ),
            new OA\Response(
                response: 429,
                description: 'Limite do grupo `/auth`, ou `verification_too_many_attempts` (errou o código vezes demais; peça outro).',
                content: new OA\JsonContent(ref: '#/components/schemas/AuthError'),
            ),
        ],
    )]
    public function verifyEmail(VerifyEmailRequest $request): JsonResponse
    {
        $session = $this->authService->verifyEmail(
            $request->validated('email'),
            $request->validated('code'),
        );

        return (new AuthTokenResource($session))
            ->additional(['message' => 'E-mail confirmado! Boas-vindas à Área Restrita.'])
            ->response();
    }

    #[OA\Post(
        path: '/auth/resend-code',
        summary: 'Envia outro código de verificação',
        description: 'Há uma espera entre um envio e o próximo (AUTH_VERIFICATION_RESEND_COOLDOWN_SECONDS). '
            .'O `resend_available_at` da resposta anterior diz a partir de quando vale a pena chamar.',
        tags: ['Autenticação'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'maria@exemplo.com'),
                ],
                type: 'object',
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Código novo a caminho.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/Verification'),
                    new OA\Property(property: 'message', type: 'string', example: 'Enviamos um novo código para o seu e-mail.'),
                    new OA\Property(property: 'email_sent', type: 'boolean', example: true),
                ], type: 'object'),
            ),
            new OA\Response(
                response: 409,
                description: '`email_already_verified`: a conta já está confirmada.',
                content: new OA\JsonContent(ref: '#/components/schemas/AuthError'),
            ),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
            new OA\Response(
                response: 429,
                description: 'Limite do grupo `/auth`, ou `verification_resend_cooldown` com `retry_after` em segundos.',
                content: new OA\JsonContent(ref: '#/components/schemas/AuthError'),
            ),
            new OA\Response(
                response: 503,
                description: '`verification_email_failed`: o provedor não aceitou a mensagem.',
                content: new OA\JsonContent(ref: '#/components/schemas/AuthError'),
            ),
        ],
    )]
    public function resendCode(ResendCodeRequest $request): JsonResponse
    {
        $verification = $this->authService->resendCode($request->validated('email'));

        return (new VerificationResource($verification))
            ->additional(['message' => 'Enviamos um novo código para o seu e-mail.', 'email_sent' => true])
            ->response();
    }

    #[OA\Post(
        path: '/auth/login',
        summary: 'Abre a sessão e devolve o token Bearer',
        description: 'Senha certa mas e-mail ainda não confirmado responde 403 `email_not_verified`, '
            .'já com um código novo enviado e o bloco `verification` para a tela do código.',
        tags: ['Autenticação'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'maria@exemplo.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'univesp2026'),
                ],
                type: 'object',
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sessão aberta.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/AuthSession'),
                ], type: 'object'),
            ),
            new OA\Response(
                response: 401,
                description: '`invalid_credentials`: e-mail ou senha não conferem.',
                content: new OA\JsonContent(ref: '#/components/schemas/AuthError'),
            ),
            new OA\Response(
                response: 403,
                description: '`email_not_verified`: confirme o e-mail para entrar. Traz `verification`.',
                content: new OA\JsonContent(ref: '#/components/schemas/AuthError'),
            ),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequests'),
        ],
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        $session = $this->authService->login(
            $request->validated('email'),
            $request->validated('password'),
        );

        return (new AuthTokenResource($session))->response();
    }

    #[OA\Get(
        path: '/auth/me',
        summary: 'A conta de quem está logado',
        security: [['bearerAuth' => []]],
        tags: ['Autenticação'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'A conta do token enviado.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/User'),
                ], type: 'object'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        ],
    )]
    public function me(Request $request): JsonResponse
    {
        return (new UserResource($request->user()))->response();
    }

    #[OA\Post(
        path: '/auth/logout',
        summary: 'Encerra a sessão',
        description: 'Revoga o token enviado; os outros tokens da mesma conta continuam valendo.',
        security: [['bearerAuth' => []]],
        tags: ['Autenticação'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sessão encerrada.',
                content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse'),
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        ],
    )]
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json(['message' => 'Logout realizado com sucesso.']);
    }
}
