<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\DTOs\RegisterDTO;
use App\DTOs\LoginDTO;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

final class AuthController
{
    public function __construct(private AuthService $authService) {}

    public function register(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'password' => 'required|string|min:6|confirmed',
            ]);

            if (User::where('email', $validated['email'])->exists()) {
                return response()->json([
                    'message' => 'Email já cadastrado',
                ], 400);
            }

            $result = $this->authService->register(RegisterDTO::fromArray($validated));

            return response()->json([
                'message' => 'Usuário registrado com sucesso',
                'data' => [
                    'user' => $result['user'],
                    'token' => $result['token'],
                ]
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validação falhou',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erro ao registrar', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Erro ao registrar',
            ], 500);
        }
    }

    public function login(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            $result = $this->authService->login(LoginDTO::fromArray($validated));

            return response()->json([
                'message' => 'Login realizado com sucesso',
                'data' => [
                    'user' => $result['user'],
                    'token' => $result['token'],
                ]
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validação falhou',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erro ao fazer login', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Credenciais inválidas',
            ], 401);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $this->authService->logout($request->user());
            return response()->json([
                'message' => 'Logout realizado com sucesso',
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao fazer logout', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Erro ao fazer logout',
            ], 500);
        }
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $request->user(),
        ]);
    }
}
