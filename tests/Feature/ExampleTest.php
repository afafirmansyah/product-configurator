<?php

test('the root route redirects guests to the login page', function () {
    $this->get('/')->assertRedirect(route('login'));
});
