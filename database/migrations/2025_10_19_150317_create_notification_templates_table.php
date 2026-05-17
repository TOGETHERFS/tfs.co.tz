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
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_type_id')->constrained()->onDelete('cascade');
            $table->string('channel'); // 'database', 'mail', 'sms'
            $table->string('subject')->nullable(); // For email and in-app notifications
            $table->text('body'); // Template body with placeholders
            $table->json('variables')->nullable(); // Available template variables
            $table->boolean('is_active')->default(true);
            $table->string('locale')->default('en'); // For multi-language support
            $table->timestamps();

            // Use a shorter, explicit index name to satisfy MySQL's 64-char limit
            $table->unique(['notification_type_id', 'channel', 'locale'], 'ntype_chan_locale_unique');
            $table->index(['channel', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
