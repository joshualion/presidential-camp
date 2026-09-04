<?php

it('redirects guests from the root entry point to login after installation', function () {
    $response = $this->get('/');

    $response->assertRedirect(route('login', absolute: false));
});