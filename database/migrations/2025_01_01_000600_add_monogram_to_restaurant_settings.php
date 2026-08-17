<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurant_settings', function (Blueprint $table) {
            // The letters drawn inside the gold ring when no logo image has been
            // uploaded. Kept separate from the name so a long name can still be
            // reduced to a mark.
            $table->string('monogram', 8)->nullable()->after('logo');
        });
    }

    public function down(): void
    {
        Schema::table('restaurant_settings', function (Blueprint $table) {
            $table->dropColumn('monogram');
        });
    }
};
