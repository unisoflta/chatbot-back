<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="Chatbot",
 *     version="1",
 *     description="API para la gestión de usuarios, chats y mensajes de un chatbot. Documentación en español."
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Autenticación mediante token Bearer. Ingrese 'Bearer {token}' en el encabezado Authorization."
 * )
 *
 * @OA\PathItem(
 *     path="/api"
 * )
 */

abstract class Controller
{
    //
}
