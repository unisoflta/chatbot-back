<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\BroadcastController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:api')->group(function () {
    // Auth routes
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);

    // User routes - grouped with /api/users prefix (for regular users)
    Route::prefix('users')->middleware('role:admin')->group(function () {
        // CRUD routes
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::get('/all', [UserController::class, 'all']);
        Route::get('/{id}', [UserController::class, 'show']);
        Route::put('/{id}', [UserController::class, 'update']);
        Route::delete('/{id}', [UserController::class, 'destroy']);

        // Additional user routes
        Route::get('/search', [UserController::class, 'search']);
        Route::get('/find-by-email', [UserController::class, 'findByEmail']);
        Route::patch('/{id}/status', [UserController::class, 'updateStatus']);
    });

    // Chat routes (available for all authenticated users)
    Route::prefix('chats')->group(function () {
        Route::get('/', [ChatController::class, 'getChats']);
        Route::post('/', [ChatController::class, 'store']);
        Route::put('/{chatId}', [ChatController::class, 'update']);
        Route::post('/{chatId}/close', [ChatController::class, 'closeChat']);
        Route::delete('/{chatId}', [ChatController::class, 'deleteChat']);
    });

    // Message routes (available for all authenticated users)
    Route::prefix('messages')->group(function () {
        Route::get('/', [MessageController::class, 'index']);
        Route::post('/', [MessageController::class, 'store']);
        Route::get('/search', [MessageController::class, 'search']);
        Route::get('/user', [MessageController::class, 'getByUser']);
        Route::get('/sender/{senderType}', [MessageController::class, 'getBySenderType']);
        Route::get('/chat/{chatId}', [MessageController::class, 'getByChat']);
        Route::get('/{id}', [MessageController::class, 'show']);
        Route::put('/{id}', [MessageController::class, 'update']);
        Route::delete('/{id}', [MessageController::class, 'destroy']);
    });

    // Role information route
    Route::get('/user/roles', function (Request $request) {
        return response()->json([
            'user_id' => $request->user()->id,
            'roles' => $request->user()->getRoleNames(),
            'permissions' => $request->user()->getAllPermissions()->pluck('name')
        ]);
    });
});


