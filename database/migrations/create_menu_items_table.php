<?php

return new class extends Illuminate\Database\Migrations\Migration
{
    public function up(): void
    {
        Illuminate\Support\Facades\Schema::create('menu_items', function (Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('menus')->onDelete('cascade');
            $table->string('title');
            $table->string('url');
            $table->integer('order')->default(0);
            $table->timestamps();
            $table->nestedSet();
        });
    }

    public function down(): void
    {
        Illuminate\Support\Facades\Schema::dropIfExists('menu_items');
    }
};
