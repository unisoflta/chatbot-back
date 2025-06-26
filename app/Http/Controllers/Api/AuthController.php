<?php

namespace App\Http\Controllers\Api;

use App\Domains\User\DTOs\UserDTO;
use App\Domains\User\Services\UserService;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Exception;

class AuthController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {}

    /**
     * @OA\Post(
     *     path="/api/auth/login",
     *     summary="Iniciar sesión de usuario",
     *     description="Autentica a un usuario y genera un token de acceso.",
     *     tags={"Autenticación"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 required={"email", "password"},
     *                 @OA\Property(property="email", type="string", format="email", example="juan@example.com"),
     *                 @OA\Property(property="password", type="string", format="password", example="secreto123"),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Inicio de sesión exitoso",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="user", ref="#/components/schemas/UserDTO"),
     *             @OA\Property(property="token", type="string"),
     *             @OA\Property(property="token_type", type="string"),
     *             @OA\Property(property="expires_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Credenciales inválidas"),
     *     @OA\Response(response=403, description="Cuenta no activa"),
     *     @OA\Response(response=422, description="Error de validación")
     * )
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required|string'
            ]);

            $credentials = $request->only(['email', 'password']);

            if (!Auth::attempt($credentials)) {
                return ApiResponse::error('Invalid credentials', 401);
            }

            $user = Auth::user();

            if (isset($user->status) && $user->status !== 'active') {
                Auth::logout();
                return ApiResponse::error('Account is not active', 403);
            }


            $user->tokens()->delete();

            $tokenResult = $user->createToken('personal');
            $tokenResult->token->expires_at = now()->addDay();
            $tokenResult->token->save();

            Log::info($tokenResult);

            $userDTO = UserDTO::fromModel($user);

            return ApiResponse::success([
                'user' => $userDTO->toArray(),
                'token' => $tokenResult->accessToken,
                'token_type' => 'Bearer',
                'expires_at' => $tokenResult->token->expires_at->toISOString(),
            ], 'Login successful');

        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors(), 'Validation failed');
        } catch (Exception $e) {
            return ApiResponse::exception($e);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/auth/me",
     *     summary="Obtener información del usuario autenticado",
     *     description="Devuelve los datos del usuario autenticado y sus tokens.",
     *     tags={"Autenticación"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Usuario autenticado",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="user", ref="#/components/schemas/UserDTO"),
     *             @OA\Property(property="tokens", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return ApiResponse::unauthorized('User not authenticated');
            }

            $userDTO = UserDTO::fromModel($user);
            $tokens = $user->tokens()->select('id', 'name', 'created_at', 'expires_at')->get();

            return ApiResponse::success([
                'user' => $userDTO->toArray(),
                'tokens' => $tokens,
            ], 'User information retrieved successfully');

        } catch (Exception $e) {
            return ApiResponse::exception($e);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/auth/logout",
     *     summary="Cerrar sesión",
     *     description="Revoca el token actual y cierra la sesión del usuario autenticado.",
     *     tags={"Autenticación"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Sesión cerrada correctamente"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return ApiResponse::unauthorized('User not authenticated');
            }

            $request->user()->token()->revoke();

            return ApiResponse::success(null, 'Logged out successfully');

        } catch (Exception $e) {
            return ApiResponse::exception($e);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/auth/refresh",
     *     summary="Refrescar token de acceso",
     *     description="Genera un nuevo token de acceso y revoca el anterior.",
     *     tags={"Autenticación"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Token refrescado correctamente",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="token", type="string"),
     *             @OA\Property(property="token_type", type="string"),
     *             @OA\Property(property="expires_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return ApiResponse::unauthorized('User not authenticated');
            }

            $currentToken = $request->user()->token();

            $newTokenResult = $user->createToken(
                $currentToken->name ?? 'personal'
            );
            $newTokenResult->token->expires_at = now()->addDay();
            $newTokenResult->token->save();

            $currentToken->revoke();

            return ApiResponse::success([
                'token' => $newTokenResult->accessToken,
                'token_type' => 'Bearer',
                'expires_at' => $newTokenResult->token->expires_at->toISOString(),
            ], 'Token refreshed successfully');

        } catch (Exception $e) {
            return ApiResponse::exception($e);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/auth/tokens",
     *     summary="Listar tokens del usuario",
     *     description="Devuelve la lista de tokens del usuario autenticado.",
     *     tags={"Autenticación"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de tokens",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="tokens", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function tokens(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return ApiResponse::unauthorized('User not authenticated');
            }

            $tokens = $user->tokens()
                ->select('id', 'name', 'created_at', 'expires_at', 'last_used_at')
                ->orderBy('created_at', 'desc')
                ->get();

            return ApiResponse::success($tokens, 'Tokens retrieved successfully');

        } catch (Exception $e) {
            return ApiResponse::exception($e);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/auth/tokens/{tokenId}",
     *     summary="Revocar token específico",
     *     description="Revoca un token específico del usuario autenticado.",
     *     tags={"Autenticación"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="tokenId",
     *         in="path",
     *         required=true,
     *         description="ID del token a revocar",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(response=200, description="Token revocado correctamente"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function revokeToken(Request $request, int $tokenId): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return ApiResponse::unauthorized('User not authenticated');
            }

            $token = $user->tokens()->find($tokenId);

            if (!$token) {
                return ApiResponse::notFound('Token not found');
            }

            $token->revoke();

            return ApiResponse::success(null, 'Token revoked successfully');

        } catch (Exception $e) {
            return ApiResponse::exception($e);
        }
    }
}
