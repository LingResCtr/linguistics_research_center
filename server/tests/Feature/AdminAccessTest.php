<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\FixtureLexiconTestHelpers;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use FixtureLexiconTestHelpers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedFixtureLexicon();
    }

    public function test_guest_is_redirected_to_the_admin_login_page(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_site_manager_can_access_the_admin_panel(): void
    {
        $user = User::where('email', 'fixture-site-manager@fixturelex.test')->firstOrFail();

        $this->actingAs($user)->get('/admin')->assertOk();
    }
}
