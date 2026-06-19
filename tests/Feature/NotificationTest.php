<?php

namespace Tests\Feature;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use App\Notifications\TaskAssignedNotification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_sent_on_reassignment()
    {
        Notification::fake();

        $admin = User::create(['name' => 'Admin', 'email' => 'admin@test.com', 'password' => 'password']);
        $andi = User::create(['name' => 'Andi', 'email' => 'andi@test.com', 'password' => 'password']);
        $doni = User::create(['name' => 'Doni', 'email' => 'doni@test.com', 'password' => 'password']);

        $task = Task::create([
            'title' => 'Test Task',
            'status' => TaskStatus::Assigned,
            'priority' => TaskPriority::Medium,
            'assigned_by' => $admin->id,
        ]);
        $task->assignees()->attach($andi->id);

        // Simulate EditTask logic
        $oldAssigneeIds = $task->assignees->pluck('id')->toArray();
        
        // Reassign to Doni
        $newAssignees = [$doni->id];
        $changes = $task->assignees()->sync($newAssignees);
        $added = $changes['attached'];

        foreach ($added as $id) {
            $staff = User::find($id);
            event(new \App\Events\TaskAssigned($task->fresh(), $admin, $staff));
        }

        Notification::assertSentTo($doni, TaskAssignedNotification::class);
        Notification::assertNotSentTo($andi, TaskAssignedNotification::class);
    }

    public function test_notification_sent_on_status_change_to_assigned()
    {
        Notification::fake();

        $admin = User::create(['name' => 'Admin', 'email' => 'admin2@test.com', 'password' => 'password']);
        $andi = User::create(['name' => 'Andi', 'email' => 'andi2@test.com', 'password' => 'password']);

        $task = Task::create([
            'title' => 'Test Task Status',
            'status' => TaskStatus::Open,
            'priority' => TaskPriority::Medium,
            'assigned_by' => $admin->id,
        ]);
        $task->assignees()->attach($andi->id);

        // Update status via service
        $service = app(\App\Services\TaskService::class);
        $service->updateStatus($task, TaskStatus::Assigned, $admin, 'Moving to assigned');

        Notification::assertSentTo($andi, TaskAssignedNotification::class);
    }
}
