<?php

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = $this->post('/register', [
        'given_name' => 'Test Given',
        'name' => 'Test User',
        'preferred_pronouns' => 'test',
        'email' => 'test@example.com',
        'password' => 'Password1',
        'password_confirmation' => 'Password1',
    ]);
    
    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});
