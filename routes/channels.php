<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::routes([
    'middleware' => ['auth:sanctum'],
]);

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('countProject', function () {
    return true;
});

Broadcast::channel('channel-task', function () {
    return true;
});

Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
