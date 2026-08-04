<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_idempotency_records', function (Blueprint $table) {
            $table->id();
            $table->string('client_id');
            $table->string('idempotency_key');
            $table->char('request_hash', 64);
            $table->unsignedSmallInteger('response_status');
            $table->json('response_body');
            $table->timestamps();

            $table->unique(['client_id', 'idempotency_key'], 'api_client_idempotency_key_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_idempotency_records');
    }
};
