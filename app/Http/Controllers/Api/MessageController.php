<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Domains\Messages\Services\MessageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Exception;

class MessageController extends Controller
{
    private MessageService $messageService;

    public function __construct(MessageService $messageService)
    {
        $this->messageService = $messageService;
        Log::info('📨 Message Controller initialized successfully');
    }

    /**
     * @OA\Post(
     *     path="/api/messages",
     *     summary="Enviar mensaje a un chat y obtener respuesta de IA",
     *     description="Envía un mensaje a un chat y recibe la respuesta del bot.",
     *     tags={"Mensajes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 required={"chat_id", "message"},
     *                 @OA\Property(property="chat_id", type="integer", example=1, description="ID del chat"),
     *                 @OA\Property(property="message", type="string", example="Hola, ¿cómo estás?", description="Mensaje a enviar")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Mensaje enviado y respuesta recibida",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="user_message", ref="#/components/schemas/MessageDTO"),
     *             @OA\Property(property="bot_response", type="string", example="¡Hola! ¿En qué puedo ayudarte hoy?"),
     *             @OA\Property(property="chat_id", type="integer", example=1),
     *             @OA\Property(property="timestamp", type="string", format="date-time", example="2024-06-25T12:34:56Z")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        Log::info('📨 Send message request started', [
            'user_id' => $request->user()->id,
            'chat_id' => $request->input('chat_id'),
            'message_length' => strlen($request->input('message', ''))
        ]);

        try {
            $validator = Validator::make($request->all(), [
                'chat_id' => 'required|exists:chats,id',
                'message' => 'required|string|max:1000',
            ]);

            if ($validator->fails()) {
                Log::warning('⚠️ Message validation failed', [
                    'user_id' => $request->user()->id,
                    'errors' => $validator->errors()->toArray()
                ]);
                return ApiResponse::error('Validation failed', 422,  $validator->errors());
            }

            $chatId = $request->input('chat_id');
            $message = $request->input('message');
            $user = $request->user();

            Log::info('📨 Processing message through service', [
                'user_id' => $user->id,
                'chat_id' => $chatId,
                'message_preview' => substr($message, 0, 100)
            ]);

            $result = $this->messageService->sendMessage($user, $chatId, $message);

            Log::info('✅ Message sent successfully', [
                'user_id' => $user->id,
                'chat_id' => $result['chat_id'],
                'bot_response_length' => strlen($result['bot_response'])
            ]);

            return ApiResponse::success([
                'user_message' => $result['user_message'],
                'bot_response' => $result['bot_response'],
                'chat_id' => $result['chat_id'],
                'timestamp' => $result['timestamp']
            ],'Message sent successfully');

        } catch (Exception $e) {
            Log::error('❌ Error sending message', [
                'user_id' => $request->user()->id,
                'chat_id' => $request->input('chat_id'),
                'error' => $e->getMessage()
            ]);
            return ApiResponse::exception($e);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/messages",
     *     summary="Listar mensajes paginados",
     *     description="Obtiene una lista paginada de mensajes.",
     *     tags={"Mensajes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Cantidad de mensajes por página",
     *         required=false,
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista paginada de mensajes",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="messages", type="array", @OA\Items(ref="#/components/schemas/MessageDTO")),
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
    public function index(Request $request)
    {
        Log::info('📨 Get all messages request started', [
            'per_page' => $request->input('per_page', 15)
        ]);

        try {
            $perPage = $request->input('per_page', 15);

            $messages = $this->messageService->getAll($perPage);

            Log::info('✅ All messages retrieved successfully', [
                'messages_count' => $messages->count(),
                'total_messages' => $messages->total(),
                'current_page' => $messages->currentPage()
            ]);

            return ApiResponse::success([
                'messages' => $messages->items(),
                'pagination' => [
                    'current_page' => $messages->currentPage(),
                    'last_page' => $messages->lastPage(),
                    'per_page' => $messages->perPage(),
                    'total' => $messages->total()
                ]
            ],'Messages retrieved successfully');

        } catch (Exception $e) {
            Log::error('❌ Error retrieving all messages', [
                'error' => $e->getMessage()
            ]);
            return ApiResponse::exception($e);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/messages/{id}",
     *     summary="Obtener mensaje por ID",
     *     description="Devuelve la información de un mensaje específico.",
     *     tags={"Mensajes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del mensaje",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Mensaje encontrado",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", ref="#/components/schemas/MessageDTO")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Mensaje no encontrado"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, int $id)
    {
        Log::info('📨 Get message by ID request started', [
            'message_id' => $id
        ]);

        try {
            $message = $this->messageService->findById($id);

            if (!$message) {
                Log::warning('⚠️ Message not found', [
                    'message_id' => $id
                ]);
                return ApiResponse::error('Message not found', [], 404);
            }

            Log::info('✅ Message retrieved successfully', [
                'message_id' => $id,
                'chat_id' => $message->chat_id ?? 'unknown'
            ]);

            return ApiResponse::success( [
                'message' => $message
            ],'Message retrieved successfully');

        } catch (Exception $e) {
            Log::error('❌ Error retrieving message', [
                'message_id' => $id,
                'error' => $e->getMessage()
            ]);
            return ApiResponse::exception($e);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/messages/{id}",
     *     summary="Actualizar mensaje",
     *     description="Actualiza el contenido de un mensaje específico.",
     *     tags={"Mensajes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del mensaje",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 required={"content"},
     *                 @OA\Property(property="content", type="string", example="Mensaje actualizado", description="Nuevo contenido del mensaje")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Mensaje actualizado",
     *         @OA\JsonContent(ref="#/components/schemas/MessageDTO")
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Mensaje no encontrado"),
     *     @OA\Response(response=422, description="Error de validación")
     * )
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, int $id)
    {
        Log::info('📨 Update message request started', [
            'user_id' => $request->user()->id,
            'message_id' => $id,
            'content_length' => strlen($request->input('content', ''))
        ]);

        try {
            $validator = Validator::make($request->all(), [
                'content' => 'required|string|max:1000',
            ]);

            if ($validator->fails()) {
                Log::warning('⚠️ Message update validation failed', [
                    'user_id' => $request->user()->id,
                    'message_id' => $id,
                    'errors' => $validator->errors()->toArray()
                ]);
                return ApiResponse::error('Validation failed', $validator->errors(), 422);
            }

            $data = $request->only(['content']);
            $user = $request->user();

            $message = $this->messageService->updateMessageForUser($user, $id, $data);

            if (!$message) {
                Log::warning('⚠️ Message not found for update', [
                    'user_id' => $user->id,
                    'message_id' => $id
                ]);
                return ApiResponse::error('Message not found', [], 404);
            }

            Log::info('✅ Message updated successfully', [
                'user_id' => $user->id,
                'message_id' => $id,
                'chat_id' => $message->chat_id
            ]);

            return ApiResponse::success([
                'message' => $message
            ],'Message updated successfully');

        } catch (Exception $e) {
            Log::error('❌ Error updating message', [
                'user_id' => $request->user()->id,
                'message_id' => $id,
                'error' => $e->getMessage()
            ]);
            return ApiResponse::exception($e);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/messages/{id}",
     *     summary="Eliminar mensaje",
     *     description="Elimina permanentemente un mensaje específico.",
     *     tags={"Mensajes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del mensaje",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Mensaje eliminado correctamente",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Message deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Mensaje no encontrado")
     * )
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, int $id)
    {
        Log::info('📨 Delete message request started', [
            'user_id' => $request->user()->id,
            'message_id' => $id
        ]);

        try {
            $user = $request->user();

            $result = $this->messageService->deleteMessageForUser($user, $id);

            if (!$result) {
                Log::warning('⚠️ Message not found for deletion', [
                    'user_id' => $user->id,
                    'message_id' => $id
                ]);
                return ApiResponse::error('Message not found', [], 404);
            }

            Log::info('✅ Message deleted successfully', [
                'user_id' => $user->id,
                'message_id' => $id
            ]);

            return ApiResponse::success('Message deleted successfully');

        } catch (Exception $e) {
            Log::error('❌ Error deleting message', [
                'user_id' => $request->user()->id,
                'message_id' => $id,
                'error' => $e->getMessage()
            ]);
            return ApiResponse::exception($e);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/messages/chat/{chatId}",
     *     summary="Obtener mensajes por chat",
     *     description="Devuelve todos los mensajes de un chat específico.",
     *     tags={"Mensajes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="chatId",
     *         in="path",
     *         required=true,
     *         description="ID del chat",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Mensajes del chat",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="messages", type="array", @OA\Items(ref="#/components/schemas/MessageDTO")),
     *             @OA\Property(property="count", type="integer", example=10)
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
    public function getByChat(Request $request, int $chatId)
    {
        Log::info('📨 Get messages by chat request started', [
            'user_id' => $request->user()->id,
            'chat_id' => $chatId,
            'per_page' => $request->input('per_page', 50)
        ]);

        try {
            $perPage = $request->input('per_page', 50);
            $user = $request->user();

            $result = $this->messageService->getMessagesForUser($user, $chatId, $perPage);

            Log::info('✅ Chat messages retrieved successfully', [
                'user_id' => $user->id,
                'chat_id' => $chatId,
                'messages_count' => count($result['messages'] ?? []),
                'total_messages' => $result['pagination']['total'] ?? 0
            ]);

            return ApiResponse::success($result, 'Chat messages retrieved successfully');

        } catch (Exception $e) {
            Log::error('❌ Error retrieving chat messages', [
                'user_id' => $request->user()->id,
                'chat_id' => $chatId,
                'error' => $e->getMessage()
            ]);
            return ApiResponse::exception($e);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/messages/user",
     *     summary="Obtener mensajes del usuario autenticado",
     *     description="Devuelve todos los mensajes enviados por el usuario autenticado.",
     *     tags={"Mensajes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Mensajes del usuario",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="messages", type="array", @OA\Items(ref="#/components/schemas/MessageDTO")),
     *             @OA\Property(property="count", type="integer", example=25)
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getByUser(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 50);
            $user = $request->user();

            $messages = $this->messageService->getByUserId($user->id, $perPage);

            return ApiResponse::success('User messages retrieved successfully', [
                'messages' => $messages->items(),
                'pagination' => [
                    'current_page' => $messages->currentPage(),
                    'last_page' => $messages->lastPage(),
                    'per_page' => $messages->perPage(),
                    'total' => $messages->total()
                ]
            ]);

        } catch (Exception $e) {
            return ApiResponse::exception('Error retrieving user messages', $e);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/messages/sender/{senderType}",
     *     summary="Obtener mensajes por tipo de remitente",
     *     description="Devuelve mensajes filtrados por tipo de remitente (user o bot).",
     *     tags={"Mensajes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="senderType",
     *         in="path",
     *         required=true,
     *         description="Tipo de remitente: user o bot",
     *         @OA\Schema(type="string", enum={"user", "bot"}, example="user")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Mensajes filtrados",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="messages", type="array", @OA\Items(ref="#/components/schemas/MessageDTO")),
     *             @OA\Property(property="count", type="integer", example=15)
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=422, description="Tipo de remitente inválido")
     * )
     *
     * @param Request $request
     * @param string $senderType
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBySenderType(Request $request, string $senderType)
    {
        try {
            $validator = Validator::make(['sender_type' => $senderType], [
                'sender_type' => 'required|in:user,bot',
            ]);

            if ($validator->fails()) {
                return ApiResponse::error('Validation failed', $validator->errors(), 422);
            }

            $perPage = $request->input('per_page', 50);

            $messages = $this->messageService->getBySenderType($senderType, $perPage);

            return ApiResponse::success("Messages by {$senderType} retrieved successfully", [
                'messages' => $messages->items(),
                'pagination' => [
                    'current_page' => $messages->currentPage(),
                    'last_page' => $messages->lastPage(),
                    'per_page' => $messages->perPage(),
                    'total' => $messages->total()
                ]
            ]);

        } catch (Exception $e) {
            return ApiResponse::exception('Error retrieving messages by sender type', $e);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/messages/search",
     *     summary="Buscar mensajes",
     *     description="Busca mensajes por contenido.",
     *     tags={"Mensajes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="q",
     *         in="query",
     *         required=true,
     *         description="Término de búsqueda",
     *         @OA\Schema(type="string", example="hola")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Mensajes encontrados",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="messages", type="array", @OA\Items(ref="#/components/schemas/MessageDTO")),
     *             @OA\Property(property="count", type="integer", example=5)
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=422, description="Término de búsqueda requerido")
     * )
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'query' => 'required|string|min:2|max:100',
            ]);

            if ($validator->fails()) {
                return ApiResponse::error('Validation failed', $validator->errors(), 422);
            }

            $query = $request->input('query');
            $perPage = $request->input('per_page', 50);

            $messages = $this->messageService->searchByContent($query, $perPage);

            return ApiResponse::success('Messages search completed successfully', [
                'query' => $query,
                'messages' => $messages->items(),
                'pagination' => [
                    'current_page' => $messages->currentPage(),
                    'last_page' => $messages->lastPage(),
                    'per_page' => $messages->perPage(),
                    'total' => $messages->total()
                ]
            ]);

        } catch (Exception $e) {
            return ApiResponse::exception('Error searching messages', $e);
        }
    }
}
