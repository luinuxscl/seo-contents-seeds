<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuración General del Paquete SEO Content Seeds
    |--------------------------------------------------------------------------
    |
    | Este archivo de configuración controla el comportamiento del paquete
    | de recolección de ideas para contenido SEO desde diversas fuentes.
    |
    */

    // Configuración de la tabla y modelo
    'database' => [
        'table' => 'seo_content_seeds',
        'model' => Luinuxscl\SeoContentSeeds\Models\SeoContentSeed::class,
    ],

    // Configuración de SerpApi para Google Trends
    'serpapi' => [
        'api_key' => env('SERPAPI_API_KEY', 'eb4710cd6a446823150f439a1108fc898a48dca2f3a63ce61580e7dc6304c2e6'),
        'google_trends' => [
            'default_geo' => 'CL',         // Código de país en formato ISO 3166-1 alfa-2
            'default_hl' => 'es',          // Código de idioma
            'default_category' => 'all',   // Categoría por defecto (all, business, entertainment, etc.)
            'items_limit' => 20,           // Número máximo de elementos a obtener
            'cache_duration' => 60 * 24,   // Duración del caché en minutos (default: 1 día)
        ],
    ],

    // Configuración de evaluación con LLM (opcional)
    'llm_evaluation' => [
        'enabled' => false,
        'min_approval_score' => 7,         // Puntuación mínima (0-10) para aprobar una seed
        'batch_size' => 10,                // Número de seeds a evaluar por lote
        'prompt_template' => 'Evalúa la relevancia de la siguiente palabra clave o tema para crear contenido SEO: "{topic_keyword}". Proporciona una puntuación del 1 al 10, donde 10 es extremadamente relevante y 1 es irrelevante. Considera factores como el interés de la audiencia, tendencias actuales, y potencial para generar tráfico. Solo responde con un número del 1 al 10.',
    ],

    // Configuración de registros y errores
    'logging' => [
        'enabled' => true,
        'channel' => env('SEO_SEEDER_LOG_CHANNEL', 'stack'),
    ],

    // Fetchers disponibles
    'fetchers' => [
        'google_trends' => [
            'enabled' => true,
            'class' => Luinuxscl\SeoContentSeeds\Fetchers\GoogleTrendsSerpApiFetcher::class,
            'priority' => 1,
        ],
        // Aquí se pueden añadir más fetchers en el futuro
    ],

    // Configuración de colas para trabajos en segundo plano
    'queue' => [
        'connection' => env('SEO_SEEDER_QUEUE_CONNECTION', 'default'),
        'default_queue' => env('SEO_SEEDER_QUEUE', 'default'),
    ],
];
