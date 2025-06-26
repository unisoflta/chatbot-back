<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Domains\Chat\Services\ChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Domains\Chat\DTOs\ChatDTO;

class ChatController extends Controller
{
    private ChatService $chatService;

    public function __construct(ChatService $chatService)
    {
        $this->chatService = $chatService;
        Log::info('💬 Chat Controller initialized successfully');
    }


    /**
     * @OA\Get(
     *     path="/api/chats",
     *     summary="Listar chats del usuario autenticado",
     *     description="Obtiene una lista paginada de chats del usuario autenticado.",
     *     tags={"Chats"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Cantidad de chats por página",
     *         required=false,
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista paginada de chats",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="chats", type="array", @OA\Items(ref="#/components/schemas/ChatDTO")),
     *             @OA\Property(property="pagination", type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=5),
     *                 @OA\Property(property="per_page", type="integer", example=15),
     *                 @OA\Property(property="total", type="integer", example=50)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getChats(Request $request)
    {
        Log::info('💬 Get chats request started', [
            'user_id' => $request->user()->id,
            'per_page' => $request->input('per_page', 15)
        ]);

        try {
            $user = $request->user();
            $perPage = $request->input('per_page', 15);

            $chats = $this->chatService->getChats($user, $perPage);

            Log::info('✅ Chats retrieved successfully', [
                'user_id' => $user->id,
                'chats_count' => $chats->count(),
                'total_chats' => $chats->total(),
                'current_page' => $chats->currentPage()
            ]);

            return ApiResponse::success( [
                'chats' => $chats->items(),
                'pagination' => [
                    'current_page' => $chats->currentPage(),
                    'last_page' => $chats->lastPage(),
                    'per_page' => $chats->perPage(),
                    'total' => $chats->total()
                ]
            ],'Chats retrieved successfully');

        } catch (Exception $e) {
            Log::error('❌ Error retrieving chats', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);
            return ApiResponse::exception($e);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/chats",
     *     summary="Crear un nuevo chat",
     *     description="Crea un nuevo chat para el usuario autenticado.",
     *     tags={"Chats"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="status", type="string", example="active", description="Estado del chat: active, closed, etc.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Chat creado correctamente",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Chat created successfully"),
     *             @OA\Property(property="chat", ref="#/components/schemas/ChatDTO")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Datos de entrada inválidos"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=422, description="Error de validación")
     * )
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        Log::info('💬 Create chat request started', [
            'user_id' => $request->user()->id
        ]);

        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|string|in:active,closed'
            ]);

            if ($validator->fails()) {
                Log::warning('❌ Chat creation validation failed', [
                    'user_id' => $request->user()->id,
                    'errors' => $validator->errors()
                ]);
                return ApiResponse::error('Validation failed', $validator->errors(), 422);
            }

            $user = $request->user();
            $chatDTO = ChatDTO::fromArray([
                'user_id' => $user->id,
                'status' => $request->input('status', 'active')
            ]);

            $chat = $this->chatService->createChat($chatDTO);

            Log::info('✅ Chat created successfully', [
                'user_id' => $user->id,
                'chat_id' => $chat->user_id,
                'chat_status' => $chat->status
            ]);

            return ApiResponse::success([
                'chat' => $chat->toArray()
            ], 'Chat created successfully', 201);

        } catch (Exception $e) {
            Log::error('❌ Error creating chat', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);
            return ApiResponse::exception($e);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/chats/{chatId}",
     *     summary="Actualizar un chat",
     *     description="Actualiza un chat específico del usuario autenticado.",
     *     tags={"Chats"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="chatId",
     *         in="path",
     *         required=true,
     *         description="ID del chat a actualizar",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="closed", description="Estado del chat: active, closed, etc.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Chat actualizado correctamente",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Chat updated successfully"),
     *             @OA\Property(property="chat", ref="#/components/schemas/ChatDTO")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Datos de entrada inválidos"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Chat no encontrado"),
     *     @OA\Response(response=422, description="Error de validación")
     * )
     *
     * @param Request $request
     * @param int $chatId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, int $chatId)
    {
        Log::info('💬 Update chat request started', [
            'user_id' => $request->user()->id,
            'chat_id' => $chatId
        ]);

        try {
            $validator = Validator::make($request->all(), [
                'status' => 'sometimes|string|in:active,closed'
            ]);

            if ($validator->fails()) {
                Log::warning('❌ Chat update validation failed', [
                    'user_id' => $request->user()->id,
                    'chat_id' => $chatId,
                    'errors' => $validator->errors()
                ]);
                return ApiResponse::error('Validation failed', $validator->errors(), 422);
            }

            $user = $request->user();
            
            // Verificar que el chat pertenece al usuario
            $existingChat = $this->chatService->findChatById($chatId);
            if (!$existingChat || $existingChat->user_id !== $user->id) {
                Log::warning('❌ Chat not found or unauthorized', [
                    'user_id' => $user->id,
                    'chat_id' => $chatId
                ]);
                return ApiResponse::error('Chat not found', [], 404);
            }

            $chatDTO = ChatDTO::fromArray([
                'user_id' => $user->id,
                'status' => $request->input('status', $existingChat->status)
            ]);

            $chat = $this->chatService->updateChat($chatId, $chatDTO);

            if (!$chat) {
                Log::error('❌ Chat update failed', [
                    'user_id' => $user->id,
                    'chat_id' => $chatId
                ]);
                return ApiResponse::error('Chat not found', [], 404);
            }

            Log::info('✅ Chat updated successfully', [
                'user_id' => $user->id,
                'chat_id' => $chatId,
                'chat_status' => $chat->status
            ]);

            return ApiResponse::success([
                'chat' => $chat->toArray()
            ], 'Chat updated successfully');

        } catch (Exception $e) {
            Log::error('❌ Error updating chat', [
                'user_id' => $request->user()->id,
                'chat_id' => $chatId,
                'error' => $e->getMessage()
            ]);
            return ApiResponse::exception($e);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/chats/{chatId}/close",
     *     summary="Cerrar un chat",
     *     description="Cierra un chat específico del usuario autenticado.",
     *     tags={"Chats"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="chatId",
     *         in="path",
     *         required=true,
     *         description="ID del chat a cerrar",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Chat cerrado correctamente",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Chat closed successfully"),
     *             @OA\Property(property="chat", ref="#/components/schemas/ChatDTO")
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Chat no encontrado")
     * )
     *
     * @param Request $request
     * @param int $chatId
     * @return \Illuminate\Http\JsonResponse
     */
    public function closeChat(Request $request, int $chatId)
    {
        Log::info('💬 Close chat request started', [
            'user_id' => $request->user()->id,
            'chat_id' => $chatId
        ]);

        try {
            $user = $request->user();

            $chat = $this->chatService->closeChatForUser($user, $chatId);

            Log::info('✅ Chat closed successfully', [
                'user_id' => $user->id,
                'chat_id' => $chat->id,
                'chat_status' => $chat->status
            ]);

            return ApiResponse::success([
                'chat_id' => $chat->id,
                'status' => $chat->status
            ],'Chat closed successfully');

        } catch (Exception $e) {
            Log::error('❌ Error closing chat', [
                'user_id' => $request->user()->id,
                'chat_id' => $chatId,
                'error' => $e->getMessage()
            ]);
            return ApiResponse::exception($e);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/chats/{chatId}",
     *     summary="Eliminar un chat",
     *     description="Elimina permanentemente un chat específico del usuario autenticado.",
     *     tags={"Chats"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="chatId",
     *         in="path",
     *         required=true,
     *         description="ID del chat a eliminar",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Chat eliminado correctamente",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Chat deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Chat no encontrado")
     * )
     *
     * @param Request $request
     * @param int $chatId
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteChat(Request $request, int $chatId)
    {
        Log::info('💬 Delete chat request started', [
            'user_id' => $request->user()->id,
            'chat_id' => $chatId
        ]);

        try {
            $user = $request->user();

            $this->chatService->deleteChatForUser($user, $chatId);

            Log::info('✅ Chat deleted successfully', [
                'user_id' => $user->id,
                'chat_id' => $chatId
            ]);

            return ApiResponse::success('Chat deleted successfully');

        } catch (Exception $e) {
            Log::error('❌ Error deleting chat', [
                'user_id' => $request->user()->id,
                'chat_id' => $chatId,
                'error' => $e->getMessage()
            ]);
            return ApiResponse::exception($e);
        }
    }
}
