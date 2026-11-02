<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FleetModuleAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_new_api_actions_require_the_existing_session_guard(): void
    {
        foreach (['cars', 'operators', 'maintenances'] as $module) {
            foreach (['', '/summary', '/1'] as $path) {
                $this->getJson('/api/'.$module.$path)->assertUnauthorized();
            }
            $this->postJson('/api/'.$module, [])->assertUnauthorized();
            $this->putJson('/api/'.$module.'/1', [])->assertUnauthorized();
            $this->deleteJson('/api/'.$module.'/1')->assertUnauthorized();
        }
        $this->getJson('/api/maintenances/options')->assertUnauthorized();
    }
}
