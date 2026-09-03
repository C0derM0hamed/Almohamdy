<?php

namespace Tests\Feature;

use Tests\TestCase;

class LocaleSwitchTest extends TestCase
{
    public function test_switching_locale_updates_the_session_and_returns_to_the_current_page(): void
    {
        $this->from('/login')
            ->get(route('lang.en'))
            ->assertRedirect('/login')
            ->assertSessionHas('locale', 'en');

        $this->from('/login')
            ->get(route('lang.ar'))
            ->assertRedirect('/login')
            ->assertSessionHas('locale', 'ar');
    }
}
