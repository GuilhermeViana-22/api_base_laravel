<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_root_reports_api_status(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertJson(['status' => 'ok', 'banco' => 'conectado']);
    }
}
