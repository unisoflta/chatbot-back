<?php

namespace App\Providers;

use App\Domains\Chat\Models\Chat;
use App\Domains\Chat\Repositories\Interfaces\ChatRepositoryInterface;
use App\Domains\Chat\Repositories\ChatRepository;
use App\Domains\Messages\Models\Message;
use App\Models\User;
use App\Repositories\Interfaces\MessageRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\MessageRepository;
use App\Domains\User\Repositories\UserRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(MessageRepositoryInterface::class, function ($app) {
            return new MessageRepository(new Message());
        });

        $this->app->bind(UserRepositoryInterface::class, function ($app) {
            return new UserRepository(new User());
        });

        $this->app->bind(ChatRepositoryInterface::class, function ($app) {
            return new ChatRepository(new Chat());
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
