<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// ============================================================================
// PUBLIC CHANNELS (No authentication required)
// ============================================================================

// Global rental notifications channel
Broadcast::channel('rentals', function () {
    return true;
});

// User-specific channels (public for this app)
Broadcast::channel('user.{userId}', function () {
    return true;
});

// Property-specific channels for IoT devices
Broadcast::channel('inmueble.{inmuebleId}', function () {
    return true;
});

// Global devices channel
Broadcast::channel('devices', function () {
    return true;
});
