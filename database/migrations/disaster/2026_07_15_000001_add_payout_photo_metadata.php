<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('evacuation_center_payout_sessions', function (Blueprint $table) {
            $table->decimal('default_quantity', 12, 2)->nullable()->after('assistance_type');
        });
        Schema::table('payout_releases', function (Blueprint $table) {
            $table->string('payout_photo_original_name')->nullable()->after('payout_photo_path');
            $table->string('payout_photo_mime_type', 100)->nullable()->after('payout_photo_original_name');
            $table->unsignedBigInteger('payout_photo_size')->nullable()->after('payout_photo_mime_type');
            $table->timestamp('payout_photo_uploaded_at')->nullable()->after('payout_photo_size');
        });
    }

    public function down(): void
    {
        Schema::table('payout_releases', fn (Blueprint $table) => $table->dropColumn([
            'payout_photo_original_name', 'payout_photo_mime_type', 'payout_photo_size', 'payout_photo_uploaded_at',
        ]));
        Schema::table('evacuation_center_payout_sessions', fn (Blueprint $table) => $table->dropColumn('default_quantity'));
    }
};
