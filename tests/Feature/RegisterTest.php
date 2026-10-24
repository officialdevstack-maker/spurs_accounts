<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_screen_renders(): void
    {
        $this->get('/register')->assertStatus(200)->assertInertia(
            fn (AssertableInertia $page) => $page->component('Auth/Register')
        );
    }

    public function test_new_users_can_register_and_are_signed_in(): void
    {
        $response = $this->post('/register', [
            'name' => 'Ada Lovelace',
            'username' => 'Ada_Lovelace',
            'email' => 'ada@spurs.com.ng',
            'phone' => '+2348012345678',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'ada@spurs.com.ng',
            'name' => 'Ada Lovelace',
            'username' => 'ada_lovelace',
        ]);
    }

    public function test_registration_requires_a_unique_username(): void
    {
        User::factory()->create(['username' => 'taken']);

        $this->post('/register', [
            'name' => 'New User',
            'username' => 'TAKEN',
            'email' => 'new@spurs.com.ng',
            'phone' => '+2348012345678',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertSessionHasErrors('username');

        $this->assertDatabaseMissing('users', ['email' => 'new@spurs.com.ng']);
    }

    public function test_registration_requires_matching_passwords(): void
    {
        $this->post('/register', [
            'name' => 'Bad Match',
            'username' => 'bad_match',
            'email' => 'bad@spurs.com.ng',
            'phone' => '+2348012345678',
            'password' => 'Password123!',
            'password_confirmation' => 'different',
        ])->assertSessionHasErrors('password');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'bad@spurs.com.ng']);
    }
}
