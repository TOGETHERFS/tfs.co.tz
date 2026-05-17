<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 60)->unique(); // subdomain
            $table->string('contact_email', 150)->nullable();
            $table->string('phone', 40)->nullable();
            $table->enum('status', ['trial', 'active', 'past_due', 'canceled'])->default('trial');
            $table->foreignId('plan_id')->nullable()->constrained('plans');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};