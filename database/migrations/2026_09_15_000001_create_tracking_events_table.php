<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tracking_events')) {
            return;
        }

        Schema::create('tracking_events', function (Blueprint $table): void {
            $table->id();
            // Alvo polimórfico (Lead, Contact, Deal...) ou, por compatibilidade, lead_id.
            $table->nullableMorphs('trackable');
            $table->unsignedBigInteger('lead_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            // Quem estava no navegador: sessão (aba) e visitante (dispositivo).
            $table->string('session_id', 64)->nullable()->index();
            $table->string('visitor_id', 64)->nullable()->index();
            $table->string('site', 190)->nullable()->index();
            $table->string('url', 512)->nullable();
            $table->string('type', 50)->index();
            $table->text('description')->nullable();
            $table->json('meta')->nullable();
            $table->integer('elapsed_ms')->nullable();
            $table->timestamps();

            $table->index(['lead_id', 'created_at']);
            $table->index(['type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracking_events');
    }
};
