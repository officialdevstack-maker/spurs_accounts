<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\SpursSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirects_signed_in_user_to_me(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/')->assertRedirect('/me');
    }

    public function test_me_requires_authentication(): void
    {
        $this->get('/me')->assertRedirect('/login');
    }

    public function test_account_page_renders_with_user(): void
    {
        $user = User::factory()->create(['name' => 'Azuka Genius', 'username' => 'azuka']);

        // /me is the overview now; connected apps live on their own page.
        $this->actingAs($user)->get('/me')
            ->assertStatus(200)
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Account/Home')
                ->where('user.name', 'Azuka Genius')
                ->where('user.username', 'azuka')
                ->has('appCount'));
    }

    public function test_shared_session_includes_verification_claims(): void
    {
        $user = User::factory()->unverified()->create();
        $payload = explode('.', SpursSession::issue($user))[1];
        $claims = json_decode(base64_decode(strtr($payload, '-_', '+/')), true, flags: JSON_THROW_ON_ERROR);

        $this->assertFalse($claims['email_verified']);
        $this->assertSame(0, $claims['kyc_level']);
        $this->assertSame('unverified', $claims['kyc_status']);
    }

    public function test_connected_apps_has_its_own_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/me/apps')
            ->assertStatus(200)
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Account/Apps')
                ->has('connectedApps'));
    }

    public function test_user_can_update_their_name(): void
    {
        $user = User::factory()->create(['name' => 'Old Name', 'username' => 'old_name']);

        $this->actingAs($user)->put('/me/profile', [
            'name' => 'New Name',
            'username' => 'new_name',
        ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
            'username' => 'new_name',
        ]);
    }

    public function test_user_can_change_password_with_correct_current_password(): void
    {
        $user = User::factory()->create(['password' => bcrypt('OldPassword1!')]);

        $this->actingAs($user)->put('/me/password', [
            'current_password' => 'OldPassword1!',
            'password' => 'BrandNew123!',
            'password_confirmation' => 'BrandNew123!',
        ])->assertRedirect()->assertSessionHasNoErrors();
    }

    public function test_password_change_rejects_wrong_current_password(): void
    {
        $user = User::factory()->create(['password' => bcrypt('OldPassword1!')]);

        $this->actingAs($user)->put('/me/password', [
            'current_password' => 'wrong',
            'password' => 'BrandNew123!',
            'password_confirmation' => 'BrandNew123!',
        ])->assertSessionHasErrors('current_password');
    }
}
