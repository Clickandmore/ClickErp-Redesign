<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * The public homepage renders. `/` itself redirects into the localised prefix
     * (mcamara/laravel-localization), so the redirect has to be followed.
     */
    public function test_the_public_homepage_renders(): void
    {
        $this->followingRedirects()
            ->get('/')
            ->assertOk();
    }
}
