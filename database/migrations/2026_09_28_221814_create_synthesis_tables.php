<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Таблица пользователей-химиков
        Schema::create('chemists', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });

        // 2. Таблица услуг (Этапы синтеза)
        Schema::create('synthesis_stages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('brief_description')->nullable();

            // Статус (черновик, опубликован, удален)
            $table->enum('lifecycle_status', ['draft', 'published', 'deleted'])->default('draft');

            // Медиа из MinIO
            $table->string('media_image_url')->nullable();
            $table->string('media_video_url')->nullable();

            // Поля по предметной области (числовые)
            $table->decimal('yield_percentage', 5, 2)->default(0.00); // Объем выхода
            $table->integer('reaction_temperature_celsius')->default(20);   // Температура

            // Внешний ключ создателя (каскадное удаление запрещено)
            $table->foreignId('chemist_id')->constrained('chemists', 'id')->onDelete('restrict');
            $table->timestamps();
        });

        // 3. Таблица лайков (многие-ко-многим)
        Schema::create('synthesis_stage_likes', function (Blueprint $table) {
            $table->foreignId('synthesis_stage_id')->constrained('synthesis_stages', 'id')->onDelete('restrict');
            $table->foreignId('chemist_id')->constrained('chemists', 'id')->onDelete('restrict');
            $table->primary(['synthesis_stage_id', 'chemist_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('synthesis_stage_likes');
        Schema::dropIfExists('synthesis_stages');
        Schema::dropIfExists('chemists');
    }
};
