<?php

namespace App\Http\Controllers\Api;

use App\Domains\User\DTOs\UserDTO;
use App\Domains\User\Services\UserService;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Exception;

class UserController extends Controller
{
    /**
     * Constructor
     *
     * @param UserService $userService
     */
    public function __construct(
        private UserService $userService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/users",
     *     summary="Listar usuarios paginados",
     *     description="Obtiene una lista paginada de usuarios.",
     *     tags={"Usuarios"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Cantidad de usuarios por página",
     *         required=false,
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Parameter(
     *         name="columns",
     *         in="query",
     *         description="Columnas a seleccionar (separadas por coma)",
     *         required=false,
     *         @OA\Schema(type="string", example="name,email")
     *     ),
     *     @OA\Parameter(
     *         name="with",
     *         in="query",
     *         description="Relaciones a cargar (separadas por coma)",
     *         required=false,
     *         @OA\Schema(type="string", example="roles")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista paginada de usuarios",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/UserDTO")),
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=5),
     *             @OA\Property(property="per_page", type="integer", example=15),
     *             @OA\Property(property="total", type="integer", example=50)
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $columns = $request->get('columns', ['*']);
            $with = $request->get('with', []);

            if (is_string($columns)) $columns = explode(',', $columns);
            if (is_string($with)) $with = explode(',', $with);

            $users = $this->userService->getPaginatedUsers($perPage, $columns, $with);

            return ApiResponse::success($users, 'Users retrieved successfully');
        } catch (Exception $e) {
            return ApiResponse::exception($e);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/users",
     *     summary="Crear un nuevo usuario",
     *     description="Crea un usuario en el sistema.",
     *     tags={"Usuarios"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(ref="#/components/schemas/UserDTO")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Usuario creado exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/UserDTO")
     *     ),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:8',
                'role' => 'nullable|string|exists:roles,name'
            ]);

            $userDTO = UserDTO::fromArray($validated);
            $createdUser = $this->userService->createUser($userDTO);

            return ApiResponse::success($createdUser->toArray(), 'User created successfully', 201);
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors(), 'Validation failed');
        } catch (Exception $e) {
            return ApiResponse::exception($e);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/users/{id}",
     *     summary="Obtener usuario por ID",
     *     description="Devuelve la información de un usuario específico.",
     *     tags={"Usuarios"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Usuario encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/UserDTO")
     *     ),
     *     @OA\Response(response=404, description="Usuario no encontrado"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function show(int $id, Request $request): JsonResponse
    {
        try {
            $columns = $request->get('columns', ['*']);
            $with = $request->get('with', []);

            if (is_string($columns)) $columns = explode(',', $columns);
            if (is_string($with)) $with = explode(',', $with);

            $user = $this->userService->findUserById($id, $columns, $with);

            if (!$user) return ApiResponse::notFound('User not found');

            return ApiResponse::success($user->toArray(), 'User retrieved successfully');
        } catch (Exception $e) {
            return ApiResponse::exception($e);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/users/{id}",
     *     summary="Actualizar usuario",
     *     description="Actualiza la información de un usuario existente.",
     *     tags={"Usuarios"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(ref="#/components/schemas/UserDTO")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Usuario actualizado exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/UserDTO")
     *     ),
     *     @OA\Response(response=404, description="Usuario no encontrado"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|email|unique:users,email,' . $id,
                'password' => 'sometimes|required|string|min:8',
                'role' => 'nullable|string|exists:roles,name'
            ]);

            $userDTO = UserDTO::fromArray($validated);
            $updatedUser = $this->userService->updateUser($id, $userDTO);

            if (!$updatedUser) return ApiResponse::notFound('User not found');

            return ApiResponse::success($updatedUser->toArray(), 'User updated successfully');
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors(), 'Validation failed');
        } catch (Exception $e) {
            return ApiResponse::exception($e);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/users/{id}",
     *     summary="Eliminar usuario",
     *     description="Elimina un usuario del sistema.",
     *     tags={"Usuarios"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(response=200, description="Usuario eliminado exitosamente"),
     *     @OA\Response(response=404, description="Usuario no encontrado"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $deleted = $this->userService->deleteUser($id);
            if (!$deleted) return ApiResponse::notFound('User not found');
            return ApiResponse::success(null, 'User deleted successfully');
        } catch (Exception $e) {
            return ApiResponse::exception($e);
        }
    }

    /**
     * Search users
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'q' => 'required|string|min:2'
            ]);

            $search = $request->get('q');
            $columns = $request->get('columns', ['*']);
            $with = $request->get('with', []);

            // Convert string to array if needed
            if (is_string($columns)) {
                $columns = explode(',', $columns);
            }
            if (is_string($with)) {
                $with = explode(',', $with);
            }

            $users = $this->userService->searchUsers($search, $columns, $with);

            return ApiResponse::success($users, 'Search completed successfully');

        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors(), 'Validation failed');
        } catch (Exception $e) {
            return ApiResponse::exception($e);
        }
    }

    /**
     * Get all users (non-paginated)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function all(Request $request): JsonResponse
    {
        try {
            $columns = $request->get('columns', ['*']);
            $with = $request->get('with', []);

            // Convert string to array if needed
            if (is_string($columns)) {
                $columns = explode(',', $columns);
            }
            if (is_string($with)) {
                $with = explode(',', $with);
            }

            $users = $this->userService->getAllUsers($columns, $with);

            return ApiResponse::success($users, 'Users retrieved successfully');

        } catch (Exception $e) {
            return ApiResponse::exception($e);
        }
    }

    /**
     * Find user by email
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function findByEmail(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|email'
            ]);

            $email = $request->get('email');
            $columns = $request->get('columns', ['*']);
            $with = $request->get('with', []);

            // Convert string to array if needed
            if (is_string($columns)) {
                $columns = explode(',', $columns);
            }
            if (is_string($with)) {
                $with = explode(',', $with);
            }

            $user = $this->userService->findUserByEmail($email, $columns, $with);

            if (!$user) {
                return ApiResponse::notFound('User not found');
            }

            return ApiResponse::success($user->toArray(), 'User retrieved successfully');

        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors(), 'Validation failed');
        } catch (Exception $e) {
            return ApiResponse::exception($e);
        }
    }

    /**
     * Update user status
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate([
                'status' => 'required|string|in:active,inactive,suspended'
            ]);

            $status = $request->get('status');
            $updatedUser = $this->userService->updateUserStatus($id, $status);

            if (!$updatedUser) {
                return ApiResponse::notFound('User not found');
            }

            return ApiResponse::success(
                $updatedUser->toArray(),
                'User status updated successfully'
            );

        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors(), 'Validation failed');
        } catch (Exception $e) {
            return ApiResponse::exception($e);
        }
    }
}
