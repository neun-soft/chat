<?php

// Registration is disabled — logins are created manually in the admin console.
test('registration routes are not available', function () {
    $this->get('/register')->assertNotFound();
    $this->post('/register')->assertNotFound();
});
