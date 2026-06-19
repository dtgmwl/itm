<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('priority', 20)->default(TaskPriority::Medium->value);
            $table->string('status', 30)->default(TaskStatus::Open->value);
            $table->foreignId('assigned_by')->constrained('users');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Composite index untuk ngebantu ngeringanin query saat bikin report/filter
            $table->index(['status', 'assigned_to', 'department_id', 'due_date'], 'tasks_report_idx');
            $table->index('due_date');
        });
    }
    public function down(): void { Schema::dropIfExists('tasks'); }
};
