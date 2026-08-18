<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurant_settings', function (Blueprint $table) {
            // The public address of this site. When set it overrides APP_URL at
            // runtime, so the QR code and the image URLs can be pointed at the
            // live domain from the dashboard rather than by editing .env.
            $table->string('site_url')->nullable()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('restaurant_settings', function (Blueprint $table) {
            $table->dropColumn('site_url');
        });
    }
};
