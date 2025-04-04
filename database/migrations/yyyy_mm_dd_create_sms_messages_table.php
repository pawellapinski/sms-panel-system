<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_messages', function (Blueprint $table) {
            $table->id();
            $table->string('device_id')->nullable();
            $table->string('message_id')->nullable();
            $table->string('phone_number');
            $table->text('message');
            $table->timestamp('received_at');
            $table->integer('sim_number')->nullable();
            $table->string('webhook_id')->nullable();
            $table->json('raw_payload');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_messages');
    }
}; 