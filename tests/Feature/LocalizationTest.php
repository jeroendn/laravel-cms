<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function testLoginPageIsShownInDutch(): void
    {
        app()->setLocale('nl');

        $response = $this->get(route('login'));

        $response->assertSee('Inloggen');
        $response->assertSee('E-mailadres');
        $response->assertSee('Wachtwoord');
    }

    public function testLoginPageFallsBackToEnglish(): void
    {
        app()->setLocale('en');

        $response = $this->get(route('login'));

        $response->assertSee('Login');
        $response->assertSee('Email Address');
        $response->assertSee('Password');
    }

    public function testHomePageIsShownInDutch(): void
    {
        app()->setLocale('nl');

        $response = $this->get(route('home'));

        $response->assertSee('Binnenkort verschijnen hier de eerste blogartikelen.');
    }
}
