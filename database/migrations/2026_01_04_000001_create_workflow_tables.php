<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates tables for multi-tenant loan approval and disbursement workflow engine.
     * Supports GLOBAL (super admin) default workflow and TENANT-CUSTOM workflows.
     */
    public function up(): void
    {
        // Workflow configurations - defines whether tenant uses custom or global workflow
        Schema::create('workflow_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->boolean('is_global')->default(false); // true = system default workflow
            $table->boolean('is_active')->default(true);
            $table->boolean('use_custom_workflow')->default(false); // tenant preference
            $table->boolean('allow_separation_override')->default(false); // super admin can override separation of duties
            $table->decimal('min_loan_amount', 15, 2)->default(0);
            $table->decimal('max_loan_amount', 15, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Only one global workflow allowed
            $table->unique(['is_global', 'tenant_id'], 'unique_global_workflow');
            $table->index(['tenant_id', 'is_active']);
        });

        // Workflow steps - ordered steps within a workflow
        Schema::create('workflow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_config_id')->constrained()->onDelete('cascade');
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->integer('step_order')->unsigned();
            $table->enum('action_type', ['APPROVAL', 'DISBURSEMENT']);
            $table->decimal('min_amount', 15, 2)->default(0);
            $table->decimal('max_amount', 15, 2)->nullable(); // null = unlimited
            $table->string('step_name', 100);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('require_comment')->default(false);
            $table->boolean('can_skip')->default(false); // for conditional steps
            $table->integer('timeout_hours')->nullable(); // auto-escalation timeout
            $table->timestamps();

            $table->unique(['workflow_config_id', 'step_order'], 'unique_step_order');
            $table->index(['workflow_config_id', 'is_active', 'step_order']);
        });

        // Loan workflow state - tracks current workflow position for each loan
        Schema::create('loan_workflow_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained()->onDelete('cascade');
            $table->foreignId('workflow_config_id')->constrained()->onDelete('restrict');
            $table->foreignId('current_step_id')->nullable()->constrained('workflow_steps')->onDelete('set null');
            $table->enum('status', [
                'PENDING',           // Awaiting first approval
                'IN_PROGRESS',       // Mid-workflow
                'APPROVED',          // All approvals done, awaiting disbursement
                'DISBURSEMENT_READY', // Ready for disbursement step
                'DISBURSED',         // Fully processed
                'REJECTED',          // Rejected at any step
                'CANCELLED',         // Cancelled by applicant/admin
                'ON_HOLD'            // Paused for review
            ])->default('PENDING');
            $table->integer('completed_steps')->default(0);
            $table->integer('total_steps')->default(0);
            $table->boolean('is_locked')->default(false); // prevents edits after first approval
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique('loan_id');
            $table->index(['status', 'current_step_id']);
        });

        // Loan workflow logs - full audit trail per approval step
        Schema::create('loan_workflow_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained()->onDelete('cascade');
            $table->foreignId('workflow_step_id')->constrained()->onDelete('restrict');
            $table->foreignId('user_id')->constrained()->onDelete('restrict'); // who performed action
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->enum('action', [
                'SUBMITTED',
                'APPROVED',
                'REJECTED',
                'RETURNED',      // sent back for corrections
                'ESCALATED',     // auto-escalated due to timeout
                'DISBURSED',
                'COMMENTED',
                'SKIPPED',
                'OVERRIDDEN'     // super admin override
            ]);
            $table->enum('previous_status', [
                'PENDING', 'IN_PROGRESS', 'APPROVED', 'DISBURSEMENT_READY', 
                'DISBURSED', 'REJECTED', 'CANCELLED', 'ON_HOLD'
            ])->nullable();
            $table->enum('new_status', [
                'PENDING', 'IN_PROGRESS', 'APPROVED', 'DISBURSEMENT_READY', 
                'DISBURSED', 'REJECTED', 'CANCELLED', 'ON_HOLD'
            ]);
            $table->text('comment')->nullable();
            $table->json('metadata')->nullable(); // additional context (IP, device, etc.)
            $table->decimal('loan_amount', 15, 2); // snapshot at time of action
            $table->boolean('is_override')->default(false);
            $table->string('override_reason')->nullable();
            $table->timestamp('action_at');
            $table->timestamps();

            $table->index(['loan_id', 'action_at']);
            $table->index(['user_id', 'action']);
            $table->index(['tenant_id', 'action_at']);
        });

        // Workflow step assignments - tracks who is assigned to handle each step
        Schema::create('workflow_step_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained()->onDelete('cascade');
            $table->foreignId('workflow_step_id')->constrained()->onDelete('cascade');
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('assigned_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('status', ['PENDING', 'IN_PROGRESS', 'COMPLETED', 'SKIPPED', 'REASSIGNED'])->default('PENDING');
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['loan_id', 'workflow_step_id'], 'unique_loan_step_assignment');
            $table->index(['assigned_user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_step_assignments');
        Schema::dropIfExists('loan_workflow_logs');
        Schema::dropIfExists('loan_workflow_states');
        Schema::dropIfExists('workflow_steps');
        Schema::dropIfExists('workflow_configs');
    }
};
