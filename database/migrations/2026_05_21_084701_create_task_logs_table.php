<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        // Catatan: Gak ada updated_at karena log ini sifatnya "Immutable" (Ga boleh diubah)
        Schema::create('task_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained(); // Siapa yang beraksi
            $table->string('action_type', 50);
            $table->string('old_status', 30)->nullable();
            $table->string('new_status', 30)->nullable();
            $table->foreignId('old_assigned_to')->nullable()->constrained('users');
            $table->foreignId('new_assigned_to')->nullable()->constrained('users');
            $table->text('note')->nullable();
            $table->json('metadata')->nullable(); // Simpen data exta, ex: IP, agent
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['task_id', 'created_at']);
            $table->index('action_type');
        });
    }
    public function down(): void { Schema::dropIfExists('task_logs'); }
};
