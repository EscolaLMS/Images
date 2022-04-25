<?php

use EscolaLms\Core\Migrations\EscolaMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateImageCachesTable extends EscolaMigration
{
    public function up(): void
    {
        Schema::create('image_caches', function (Blueprint $table) {
            $table->id();
            $table->string('path');
            $table->string('hash_path');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('image_caches');
    }
}
