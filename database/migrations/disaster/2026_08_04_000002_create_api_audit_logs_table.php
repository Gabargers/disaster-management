<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('client_id')->nullable()->index();
            $table->string('request_id', 128)->index();
            $table->string('event_reference')->nullable()->index();
            $table->string('control_number')->nullable()->index();
            $table->string('source_ip', 45)->nullable();
            $table->string('http_method', 10);
            $table->string('route');
            $table->unsignedSmallInteger('response_status');
            $table->unsignedInteger('processing_time_ms');
            $table->string('outcome', 20)->index();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_audit_logs');
    }
};
