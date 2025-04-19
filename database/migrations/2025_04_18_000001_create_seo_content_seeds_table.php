<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('seo_content_seeds', function (Blueprint $table) {
            $table->id();
            $table->string('source_type')->index()->comment('Tipo de fuente: google_trends, rss_feed, etc.');
            $table->string('source_identifier')->nullable()->comment('Identificador de la fuente (URL, query, etc.)');
            $table->string('topic_keyword')->index()->comment('Palabra clave o tema principal');
            $table->text('title_suggestion')->nullable()->comment('Título sugerido o de la fuente');
            $table->json('details')->nullable()->comment('Datos adicionales en formato JSON (volumen de búsqueda, categorías, etc.)');
            $table->decimal('relevance_score', 5, 2)->nullable()->index()->comment('Puntuación de relevancia (0-10), potencialmente calculada por LLM');
            $table->string('status')->default('pending_evaluation')->index()->comment('Estado: pending_evaluation, approved, rejected, evaluation_failed, processed');
            $table->string('region')->nullable()->index()->comment('Región/país relacionado con la seed');
            $table->string('language')->nullable()->index()->comment('Idioma de la seed');
            $table->timestamp('fetched_at')->comment('Fecha y hora cuando se obtuvo la seed');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('seo_content_seeds');
    }
};
