<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurant_settings', function (Blueprint $table) {
            // Brass. The third colour the design needs: primary green is the
            // ground, secondary cream is the type, and this is the accent that
            // carries rules, icons and the primary call to action.
            $table->string('accent_color', 9)->default('#c8a24c')->after('secondary_color');
        });
    }

    public function down(): void
    {
        Schema::table('restaurant_settings', function (Blueprint $table) {
            $table->dropColumn('accent_color');
        });
    }
};
