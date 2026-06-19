<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class TaskDiscussionTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_discussion_component_renders()
    {
        $user = User::create([
            'name' => 'Doni',
            'email' => 'doni@test.com',
            'password' => bcrypt('password'),
        ]);
        $this->actingAs($user);

        $task = Task::create([
            'title' => 'Test Task',
            'status' => TaskStatus::Open,
            'priority' => TaskPriority::Medium,
            'assigned_by' => $user->id,
        ]);

        $test = Volt::test('task-discussion', ['taskId' => $task->id]);
        $test->assertStatus(200);
    }
}
