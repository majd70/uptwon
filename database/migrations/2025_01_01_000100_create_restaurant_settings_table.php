<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('tagline_ar')->nullable();
            $table->string('tagline_en')->nullable();
            $table->string('logo')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('primary_color', 9)->default('#3d4f2f');
            $table->string('secondary_color', 9)->default('#f5f1e8');
            $table->string('phone')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('facebook_url')->nullable();
            $table->string('tiktok_url')->nullable();
            $table->string('google_maps_url')->nullable();
            $table->string('address_ar')->nullable();
            $table->string('address_en')->nullable();
            $table->json('working_hours')->nullable();
            $table->string('currency', 8)->default('EGP');
            $table->string('default_locale', 2)->default('ar');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_settings');
    }
};
