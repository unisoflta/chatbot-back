<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

# Chatbot Backend

Un backend robusto para un chatbot con integración de GPT y API de clima, construido con Laravel y siguiendo principios de Domain-Driven Design. **Sistema completamente asíncrono con Jobs y Pusher para tiempo real.**

## 🚀 Características

- **🤖 Integración con GPT**: Chat inteligente con OpenAI
- **🌤️ API de Clima**: Datos meteorológicos en tiempo real
- **🔄 Sistema Asíncrono**: Jobs en segundo plano para procesamiento de IA
- **📡 Broadcasting en Tiempo Real**: Pusher para respuestas instantáneas
- **🔐 Sistema de Roles**: Roles de admin y user con Spatie Laravel Permission
- **💬 Gestión de Chats**: Historial y gestión de conversaciones
- **📨 Sistema de Mensajes**: CRUD completo de mensajes
- **👥 Gestión de Usuarios**: Sistema de autenticación con Sanctum
- **📊 Logs Detallados**: Sistema de logging con emojis para debugging
- **🏗️ Arquitectura DDD**: Separación clara de dominios y responsabilidades

## 🛠️ Tecnologías

- **Laravel 11** - Framework PHP
- **OpenAI API** - Integración con GPT
- **Open-Meteo API** - Datos meteorológicos
- **Pusher** - Broadcasting en tiempo real
- **Laravel Jobs** - Procesamiento asíncrono
- **Spatie Laravel Permission** - Sistema de roles y permisos
- **Laravel Sanctum** - Autenticación API
- **MySQL/PostgreSQL** - Base de datos

## 📋 Requisitos

- PHP 8.2+
- Composer
- MySQL/PostgreSQL
- Clave API de OpenAI
- Cuenta de Pusher

## 🔧 Instalación

1. **Clonar el repositorio**
```bash
git clone <repository-url>
cd chatbot-backend
```

2. **Instalar dependencias**
```bash
composer install
```

3. **Configurar variables de entorno**
```bash
cp .env.example .env
```

Editar `.env` con tus configuraciones:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=chatbot_backend
DB_USERNAME=root
DB_PASSWORD=

# OpenAI
OPENAI_API_KEY=tu_clave_api_de_openai

# Broadcasting
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=tu_app_id
PUSHER_APP_KEY=tu_app_key
PUSHER_APP_SECRET=tu_app_secret
PUSHER_APP_CLUSTER=tu_cluster

# Queue
QUEUE_CONNECTION=database
QUEUE_FAILED_DRIVER=database-uuids
```

4. **Generar clave de aplicación**
```bash
php artisan key:generate
```

5. **Ejecutar migraciones**
```bash
php artisan migrate
```

6. **Ejecutar seeders**
```bash
php artisan db:seed
```

7. **Iniciar queue worker**
```bash
php artisan queue:work
```

## 👥 Usuarios Predefinidos

### Administrador
- **Email**: `admin@chatbot.com`
- **Password**: `password123`
- **Rol**: `admin`

### Usuario Regular
- **Email**: `user@chatbot.com`
- **Password**: `password123`
- **Rol**: `user`

### Usuarios Adicionales
- `alice@example.com` (rol: user)
- `bob@example.com` (rol: user)
- `carol@example.com` (rol: user)

## 🔄 Sistema Asíncrono

### Flujo de Procesamiento
1. **Usuario envía mensaje** → Respuesta inmediata
2. **Job se despacha** → Procesamiento en segundo plano
3. **IA procesa mensaje** → GPT + Weather API
4. **Pusher envía respuesta** → Tiempo real al frontend

### Comandos de Queue
```bash
# Procesar jobs
php artisan queue:work

# Ver jobs fallidos
php artisan queue:failed

# Reintentar jobs fallidos
php artisan queue:retry all
```

## 🔐 Sistema de Roles

El sistema utiliza Spatie Laravel Permission con dos roles principales:

### Roles Disponibles
- **admin**: Acceso completo a todas las funcionalidades
- **user**: Acceso limitado a funcionalidades básicas

### Rutas Protegidas por Roles

#### Rutas de Administrador (`/admin/*`)
```bash
GET    /api/admin/users          # Listar usuarios (solo admin)
GET    /api/admin/messages       # Listar mensajes (solo admin)
# ... más rutas administrativas
```

#### Rutas de Usuario Regular
```bash
GET    /api/chat/history         # Historial de chat
POST   /api/messages             # Enviar mensaje
GET    /api/user/roles           # Ver roles del usuario
# ... más rutas de usuario
```

## 🚀 Uso

### 1. Autenticación
```bash
# Login
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@chatbot.com", "password": "password123"}'
```

### 2. Enviar Mensaje (Asíncrono)
```bash
# Enviar mensaje al chatbot
curl -X POST http://localhost:8000/api/messages \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"chat_id": 1, "message": "¿Cómo está el clima en Madrid?"}'
```

**Respuesta Inmediata:**
```json
{
    "success": true,
    "data": {
        "user_message": {...},
        "bot_response": null,
        "status": "processing",
        "message": "Tu mensaje ha sido enviado. La respuesta del bot se procesará en segundo plano."
    }
}
```

### 3. Escuchar Respuestas (Frontend)
```javascript
// Configurar Pusher
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: 'your-pusher-key',
    cluster: 'your-cluster',
    authEndpoint: '/api/broadcasting/auth',
    auth: {
        headers: {
            'Authorization': `Bearer ${token}`,
        },
    },
});

// Escuchar respuestas del bot
Echo.private(`user.${userId}.chat.${chatId}`)
    .listen('bot.response', (e) => {
        console.log('Bot response:', e.message);
        // Actualizar UI
    })
    .listen('bot.error', (e) => {
        console.log('Bot error:', e.error);
        // Mostrar error
    });
```

### 4. Ver Historial de Chat
```bash
# Obtener historial
curl -X GET http://localhost:8000/api/chat/history \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 5. Verificar Roles
```bash
# Ver roles del usuario
curl -X GET http://localhost:8000/api/user/roles \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## 📊 Logs del Sistema

El sistema incluye logs detallados con emojis para facilitar el debugging:

### Emojis Utilizados
- **🤖** - Servicios GPT
- **🌤️** - Servicios de clima
- **🔄** - Jobs y procesamiento
- **📡** - Broadcasting y Pusher
- **💬** - Controlador de chat
- **📨** - Controlador de mensajes
- **🔐** - Sistema de roles
- **✅** - Operaciones exitosas
- **❌** - Errores
- **⚠️** - Advertencias

### Filtrado de Logs
```bash
# Logs de Jobs
grep "🔄" storage/logs/laravel.log

# Logs de Broadcasting
grep "📡" storage/logs/laravel.log

# Logs de GPT
grep "🤖" storage/logs/laravel.log

# Solo errores
grep "❌" storage/logs/laravel.log
```

## 🏗️ Arquitectura

### Estructura de Dominios
```
app/
├── Domains/
│   ├── Chat/
│   │   ├── DTOs/
│   │   ├── Models/
│   │   └── Services/
│   ├── Messages/
│   │   ├── DTOs/
│   │   ├── Models/
│   │   └── Services/
│   └── User/
│       ├── DTOs/
│       ├── Repositories/
│       └── Services/
├── Jobs/
│   └── ProcessChatMessage.php
├── Events/
│   ├── BotResponseReceived.php
│   └── BotErrorOccurred.php
├── Services/
│   ├── GPTChatService.php
│   └── WeatherApiService.php
└── Http/Controllers/Api/
    ├── AuthController.php
    ├── ChatController.php
    ├── MessageController.php
    └── UserController.php
```

### Servicios Principales

#### GPTChatService
- Maneja conversaciones con GPT
- Detecta requerimientos de API externa
- Integra datos de clima cuando es necesario

#### WeatherApiService
- Obtiene datos meteorológicos
- Geocodificación de ciudades
- Pronósticos semanales

#### ProcessChatMessage Job
- Procesa mensajes en segundo plano
- Timeout de 2 minutos
- 3 intentos de reintento
- Broadcasting de respuestas

## 🧪 Testing

```bash
# Ejecutar tests
php artisan test

# Tests específicos
php artisan test --filter=ChatControllerTest
php artisan test --filter=GPTChatServiceTest

# Probar job manualmente
php artisan test:chat-job 1 5 "¿Cómo está el clima en Madrid?"
```

## 📚 Documentación

- [Sistema de Logs](docs/LoggingSystem.md)
- [Sistema de Roles](docs/RoleSystem.md)
- [Sistema Asíncrono](docs/AsyncChatSystem.md)
- [Servicio GPT](docs/GPTChatService.md)

## 🔧 Comandos Útiles

```bash
# Ejecutar seeder de roles
php artisan db:seed --class=RoleAndUserSeeder

# Procesar jobs
php artisan queue:work

# Ver jobs fallidos
php artisan queue:failed

# Verificar roles en Tinker
php artisan tinker
# \Spatie\Permission\Models\Role::all();

# Limpiar cache
php artisan cache:clear
php artisan config:clear
```

## 🔧 Configuración de Producción

### Queue Worker con Supervisor
```bash
# /etc/supervisor/conf.d/laravel-worker.conf
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/project/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=8
redirect_stderr=true
stdout_logfile=/path/to/your/project/storage/logs/worker.log
stopwaitsecs=3600
```

### Variables de Entorno de Producción
```env
# Queue
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Broadcasting
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your_production_app_id
PUSHER_APP_KEY=your_production_app_key
PUSHER_APP_SECRET=your_production_app_secret
PUSHER_APP_CLUSTER=your_production_cluster
```

## 🤝 Contribución

1. Fork el proyecto
2. Crear una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abrir un Pull Request

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo `LICENSE` para más detalles.

## 🆘 Soporte

Si tienes problemas o preguntas:

1. Revisa los logs en `storage/logs/laravel.log`
2. Verifica la configuración en `.env`
3. Asegúrate de que las migraciones estén ejecutadas
4. Verifica que el queue worker esté ejecutándose
5. Comprueba la configuración de Pusher
6. Verifica que las APIs externas estén configuradas correctamente
