<?php

require_once __DIR__.'/../Support/BookingDatabase.php';

test('returns a successful response', function () {
    $this->withoutVite();
    createBookingDatabase();

    $response = $this->get(route('home'));

    $response->assertOk();
});
