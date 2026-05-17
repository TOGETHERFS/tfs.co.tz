<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Admin roles table (separate from tenant roles)
        Schema::create('admin_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Accountant, IT Technician, Manager, CEO, Admin, Marketing Manager, Sales Manager
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Admin permissions table
        Schema::create('admin_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // View Dashboard, Manage Users, etc.
            $table->string('slug')->unique();
            $table->string('module'); // dashboard, users, tenants, billing, sms, reports, settings
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Admin role-permission pivot table
        Schema::create('admin_role_permission', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_role_id')->constrained('admin_roles')->onDelete('cascade');
            $table->foreignId('admin_permission_id')->constrained('admin_permissions')->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['admin_role_id', 'admin_permission_id'], 'role_permission_unique');
        });

        // Add admin_role_id to users table for admin staff
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('admin_role_id')->nullable()->after('role')->constrained('admin_roles')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['admin_role_id']);
            $table->dropColumn('admin_role_id');
        });
        
        Schema::dropIfExists('admin_role_permission');
        Schema::dropIfExists('admin_permissions');
        Schema::dropIfExists('admin_roles');
    }
};
