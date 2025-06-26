<?php

namespace App\Domains\User\DTOs;

use App\Models\User;

/**
 * @OA\Schema(
 *     schema="UserDTO",
 *     title="Usuario",
 *     description="DTO que representa a un usuario del sistema.",
 *     required={"name", "email"},
 *     @OA\Property(property="id", type="integer", example=1, description="ID del usuario (solo lectura)"),
 *     @OA\Property(property="name", type="string", example="Juan Pérez", description="Nombre del usuario"),
 *     @OA\Property(property="email", type="string", format="email", example="juan@example.com", description="Correo electrónico del usuario"),
 *     @OA\Property(property="password", type="string", format="password", example="secreto123", description="Contraseña del usuario (solo para registro o actualización)"),
 *     @OA\Property(property="role", type="string", example="admin", description="Rol asignado al usuario")
 * )
 */
class UserDTO
{
    /**
     * UserDTO constructor
     *
     * @param int|null $id
     * @param string $name
     * @param string $email
     * @param string|null $password
     * @param string|null $role
     */
    public function __construct(
        public readonly ?int $id = null,
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $password = null,
        public readonly ?string $role = null
    ) {}

    /**
     * Crear UserDTO desde un array (para creación o actualización)
     *
     * @param array $data
     * @return UserDTO
     */
    public static function fromArray(array $data): UserDTO
    {
        return new self(
            name: $data['name'] ?? '',
            email: $data['email'] ?? '',
            password: $data['password'] ?? null,
            role: $data['role'] ?? null
        );
    }

    /**
     * Crear UserDTO desde el modelo User (para lectura)
     *
     * @param User $user
     * @return UserDTO
     */
    public static function fromModel(User $user): UserDTO
    {
        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            password: null, // No se expone la contraseña
            role: $user->getRoleNames()->first()
        );
    }

    /**
     * Convertir el DTO a array, excluyendo nulos
     *
     * @return array
     */
    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'role' => $this->role,
        ], fn($value) => $value !== null);
    }
}
