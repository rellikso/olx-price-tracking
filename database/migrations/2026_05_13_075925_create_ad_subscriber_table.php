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
        Schema::create('ad_subscriber', function (Blueprint $table) {
            $table
                ->foreignId('ad_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table
                ->foreignId('subscriber_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table
                ->string('token')
                ->nullable();

            $table
                ->timestamp('verified_at')
                ->nullable();

            $table->timestamps();

            $table->primary(['ad_id', 'subscriber_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_subscriber');
    }
};
