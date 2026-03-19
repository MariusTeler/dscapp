<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Events\Dispatcher;
use App\Services\AuthService;

class UserEventLoginLogoutSubscriber
{
    public function __construct(

        protected AuthService $authService,

    ) {}

    /**

     * Handle user login events.

     */

    public function handleUserLogin(Login $event): void {
        
        $this->authService->login($event->user);
    }


    /**

     * Handle user logout events.

     */

    public function handleUserLogout(Logout $event): void {
        $this->authService->logout($event->user);
    }

    /**

     * Register the listeners for the subscriber.

     *

     * @return array<string, string>

     */

    public function subscribe(Dispatcher $events): array
    {

        return [
            Login::class => 'handleUserLogin',
            Logout::class => 'handleUserLogout',
        ];

    }

}