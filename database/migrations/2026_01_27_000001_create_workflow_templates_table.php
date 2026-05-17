<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates workflow templates that tenants can choose from as starting points.
     * Templates provide pre-configured approval/disbursement flows.
     */
    public function up(): void
    {
        // Workflow templates - pre-defined workflows tenants can choose from
        Schema::create('workflow_templates', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique(); // e.g., 'simple', 'standard', 'multi-tier'
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->string('category', 50)->default('general'); // general, microfinance, corporate, etc.
            $table->json('default_steps'); // JSON structure of default steps
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->string('icon', 50)->nullable(); // for UI display
            $table->json('features')->nullable(); // feature flags/capabilities
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
            $table->index('category');
        });

        // Add template reference to workflow_configs
        Schema::table('workflow_configs', function (Blueprint $table) {
            $table->foreignId('template_id')->nullable()->after('tenant_id')
                ->constrained('workflow_templates')->onDelete('set null');
            $table->string('workflow_type', 50)->default('custom')->after('template_id');
            // workflow_type: 'template', 'custom', 'global'
        });

        // Enhance workflow_steps with more action types and conditions
        Schema::table('workflow_steps', function (Blueprint $table) {
            // Change action_type to string to allow more types
            // We'll handle this in a separate statement due to enum constraints
            $table->json('conditions')->nullable()->after('timeout_hours');
            $table->json('auto_actions')->nullable()->after('conditions');
            $table->boolean('require_documents')->default(false)->after('require_comment');
            $table->json('required_document_types')->nullable()->after('require_documents');
            $table->boolean('allow_delegation')->default(false)->after('can_skip');
            $table->integer('escalation_hours')->nullable()->after('timeout_hours');
            $table->foreignId('escalation_role_id')->nullable()->after('escalation_hours')
                ->constrained('roles')->onDelete('set null');
            $table->boolean('notify_on_pending')->default(true)->after('allow_delegation');
            $table->boolean('notify_on_complete')->default(true)->after('notify_on_pending');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workflow_steps', function (Blueprint $table) {
            $table->dropForeign(['escalation_role_id']);
            $table->dropColumn([
                'conditions',
                'auto_actions', 
                'require_documents',
                'required_document_types',
                'allow_delegation',
                'escalation_hours',
                'escalation_role_id',
                'notify_on_pending',
                'notify_on_complete',
            ]);
        });

        Schema::table('workflow_configs', function (Blueprint $table) {
            $table->dropForeign(['template_id']);
            $table->dropColumn(['template_id', 'workflow_type']);
        });

        Schema::dropIfExists('workflow_templates');
    }
};
