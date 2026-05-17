<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subscription_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('subscriptions')->cascadeOnDelete();
            $table->string('addon_slug');
            $table->unsignedInteger('quantity')->default(0);
            $table->unsignedInteger('unit_price');
            $table->string('currency')->default('TZS');
            $table->timestamps();

            $table->unique(['subscription_id', 'addon_slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_items');
    }
};