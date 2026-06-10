<?php

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Str;

test('users can mark their own notifications as read', function () {
    $user = User::factory()->create();
    $notification = Notification::create([
        'id' => (string) Str::uuid(),
        'user_id' => $user->id,
        'type' => 'task_assigned',
        'title' => 'Task assigned',
    ]);

    $this->actingAs($user)
        ->patch(route('my-notifications.read', $notification))
        ->assertRedirect();

    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('users cannot modify other users notifications', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $notification = Notification::create([
        'id' => (string) Str::uuid(),
        'user_id' => $other->id,
        'type' => 'task_assigned',
        'title' => 'Task assigned',
    ]);

    $this->actingAs($user)
        ->patch(route('my-notifications.read', $notification))
        ->assertForbidden();
});
