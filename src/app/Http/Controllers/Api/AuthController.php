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

/**
 * Autenticação da área restrita (`/api/auth/*`).
 *
 * Fluxo de cadastro:
 *   1. POST register      -> cria a conta e envia o código   (201, data = verificação)
 *   2. POST verify-email  -> confere o código e já faz login (200, data = sessão)
 *      POST resend-code   -> envia outro código              (200, data = verificação)
 *
 * Sucesso: `{ message, data }`. Erro de negócio: `{ message, code, ... }`
 * (ver App\Exceptions\AuthException). Validação: 422 `{ message, errors }`.
 */
class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return (new VerificationResource($result['verification']))
            ->additional(['message' => 'Cadastro recebido! Enviamos um código de verificação para o seu e-mail.'])
            ->response()
            ->setStatusCode(201);
    }

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

    public function resendCode(ResendCodeRequest $request): JsonResponse
    {
        $verification = $this->authService->resendCode($request->validated('email'));

        return (new VerificationResource($verification))
            ->additional(['message' => 'Enviamos um novo código para o seu e-mail.'])
            ->response();
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $session = $this->authService->login(
            $request->validated('email'),
            $request->validated('password'),
        );

        return (new AuthTokenResource($session))->response();
    }

    public function me(Request $request): JsonResponse
    {
        return (new UserResource($request->user()))->response();
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json(['message' => 'Logout realizado com sucesso.']);
    }
}
