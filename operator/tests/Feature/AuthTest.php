<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_can_login(): void
    {
        User::create([
            'user' => 'testop',
            'hashParola' => Hash::make('pass123'),
            'nivel_acces' => 10,
            'activ' => 1,
            'expeditor_id' => 0,
        ]);

        $response = $this->post('/login', [
            'user' => 'testop',
            'password' => 'pass123',
        ]);

        $response->assertRedirect('/expeditii/cautare');
        $this->assertAuthenticated();
    }

    public function test_nivel_acces_9_can_login(): void
    {
        // nivel_acces >= 5 permite login; 9 este valid
        User::create([
            'user' => 'usernivel9',
            'hashParola' => Hash::make('pass123'),
            'nivel_acces' => 9,
            'activ' => 1,
            'expeditor_id' => 123,
        ]);

        $this->post('/login', [
            'user' => 'usernivel9',
            'password' => 'pass123',
        ]);

        $this->assertAuthenticated();
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::create([
            'user' => 'inactiv',
            'hashParola' => Hash::make('pass123'),
            'nivel_acces' => 10,
            'activ' => 0,
        ]);

        $this->post('/login', [
            'user' => 'inactiv',
            'password' => 'pass123',
        ]);

        $this->assertGuest();
    }
}
