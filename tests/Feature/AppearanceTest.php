<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('appearance.edit'));

    $response->assertRedirect(route('login'));
});

test('authenticated users can view the appearance controls', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('appearance.edit'));

    $response->assertOk()
        ->assertSee('data-appearance-control="light"', false)
        ->assertSee('data-appearance-control="dark"', false)
        ->assertSee('data-appearance-control="system"', false)
        ->assertSee("x-on:click=\"select('light')\"", false)
        ->assertSee("x-on:click=\"select('dark')\"", false)
        ->assertSee("x-bind:aria-pressed=\"appearance === 'system'\"", false)
        ->assertSee("document.addEventListener('livewire:navigated', handleLivewireNavigation)", false)
        ->assertSee('localStorage.setItem(appearanceKey, appearance)', false);
});
