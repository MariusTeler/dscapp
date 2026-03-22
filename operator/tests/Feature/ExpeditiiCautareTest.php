<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ExpeditiiCautareTest extends TestCase
{
    use RefreshDatabase;

    private function loginAsOperator(): User
    {
        $user = User::create([
            'user' => 'op1',
            'hashParola' => Hash::make('pass123'),
            'nivel_acces' => 10,
            'activ' => 1,
        ]);
        $this->actingAs($user);
        return $user;
    }

    public function test_cautare_page_requires_auth(): void
    {
        $response = $this->get('/expeditii/cautare');
        $response->assertRedirect('/login');
    }

    public function test_cautare_page_loads_for_operator(): void
    {
        $this->loginAsOperator();
        $response = $this->get('/expeditii/cautare');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('expeditii/Cautare'));
    }

    public function test_cautare_returns_empty_without_filters(): void
    {
        $this->loginAsOperator();
        $response = $this->get('/expeditii/cautare');
        $response->assertInertia(fn ($page) =>
            $page->has('expeditii')
                 ->where('expeditii.total', 0)
        );
    }

    public function test_export_returns_501(): void
    {
        $this->loginAsOperator();
        $response = $this->get('/expeditii/export');
        $response->assertStatus(501);
    }
}
