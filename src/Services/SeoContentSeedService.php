<?php

namespace Luinuxscl\SeoContentSeeds\Services;

use Illuminate\Support\Collection;
use Luinuxscl\SeoContentSeeds\Models\SeoContentSeed;
use Luinuxscl\SeoContentSeeds\Exceptions\SeedFetchException;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Arr;

class SeoContentSeedService
{
    /**
     * La instancia de aplicación.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * El gestor de fetchers.
     *
     * @var \Luinuxscl\SeoContentSeeds\Services\FetcherManager
     */
    protected $fetcherManager;

    /**
     * Crea una nueva instancia del servicio.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->fetcherManager = $app->make(FetcherManager::class);
    }

    /**
     * Obtiene y almacena seeds desde una fuente específica.
     *
     * @param  string|null  $source  Identificador de la fuente. Si es null, usa la predeterminada.
     * @param  array  $options  Opciones adicionales para el fetcher
     * @return array  ['fetched' => int, 'stored' => int, 'errors' => array]
     *
     * @throws \InvalidArgumentException
     */
    public function fetchAndStore(?string $source = null, array $options = []): array
    {
        $stats = [
            'fetched' => 0,
            'stored' => 0,
            'errors' => [],
        ];

        try {
            $fetcher = $source 
                ? $this->fetcherManager->driver($source)
                : $this->fetcherManager->driver();

            if (!$fetcher->isConfigured()) {
                throw new \InvalidArgumentException("El fetcher [{$fetcher->getName()}] no está correctamente configurado.");
            }

            // Obtener datos desde la fuente
            $seeds = $fetcher->fetch($options);
            $stats['fetched'] = $seeds->count();

            // Almacenar en la base de datos
            foreach ($seeds as $seedData) {
                try {
                    $this->storeSeed($seedData, $fetcher->getIdentifier());
                    $stats['stored']++;
                } catch (\Exception $e) {
                    $stats['errors'][] = [
                        'message' => $e->getMessage(),
                        'data' => Arr::except($seedData, ['details']),
                    ];
                    $this->logError($e, "Error al almacenar seed desde {$fetcher->getName()}", $seedData);
                }
            }

            return $stats;
        } catch (\Exception $e) {
            $this->logError($e, "Error al obtener seeds");
            throw $e;
        }
    }

    /**
     * Obtiene y almacena seeds desde todas las fuentes habilitadas.
     *
     * @param  array  $globalOptions  Opciones generales para todos los fetchers
     * @param  array  $sourcesOptions  Opciones específicas por fuente (clave = fuente, valor = opciones)
     * @return array  Resultados por fuente
     */
    public function fetchAndStoreFromAllSources(array $globalOptions = [], array $sourcesOptions = []): array
    {
        $results = [];
        $enabledDrivers = $this->fetcherManager->getEnabledDrivers();

        foreach ($enabledDrivers as $driver => $config) {
            try {
                // Combinar opciones globales con opciones específicas de esta fuente
                $options = array_merge(
                    $globalOptions,
                    $sourcesOptions[$driver] ?? []
                );

                $results[$driver] = $this->fetchAndStore($driver, $options);
            } catch (\Exception $e) {
                $results[$driver] = [
                    'fetched' => 0,
                    'stored' => 0,
                    'errors' => [['message' => $e->getMessage()]],
                ];
            }
        }

        return $results;
    }

    /**
     * Almacena una seed en la base de datos.
     *
     * @param  array  $data  Datos estandarizados de la seed
     * @param  string  $sourceType  Tipo de fuente
     * @return \Luinuxscl\SeoContentSeeds\Models\SeoContentSeed
     */
    protected function storeSeed(array $data, string $sourceType): SeoContentSeed
    {
        // Datos requeridos
        if (empty($data['topic_keyword'])) {
            throw new \InvalidArgumentException("La seed debe tener una palabra clave o tema.");
        }

        // Verificar si ya existe para evitar duplicados
        $existingSeed = SeoContentSeed::where('source_type', $sourceType)
            ->where('topic_keyword', $data['topic_keyword'])
            ->when(isset($data['region']), function ($query) use ($data) {
                return $query->where('region', $data['region']);
            })
            ->when(isset($data['language']), function ($query) use ($data) {
                return $query->where('language', $data['language']);
            })
            ->first();

        if ($existingSeed) {
            // Actualizar la seed existente con los nuevos datos
            $existingSeed->update([
                'title_suggestion' => $data['title_suggestion'] ?? $existingSeed->title_suggestion,
                'details' => $data['details'] ?? $existingSeed->details,
                'source_identifier' => $data['source_identifier'] ?? $existingSeed->source_identifier,
                'fetched_at' => now(),
            ]);

            return $existingSeed;
        }

        // Crear una nueva seed
        return SeoContentSeed::create([
            'source_type' => $sourceType,
            'source_identifier' => $data['source_identifier'] ?? null,
            'topic_keyword' => $data['topic_keyword'],
            'title_suggestion' => $data['title_suggestion'] ?? null,
            'details' => $data['details'] ?? [],
            'relevance_score' => $data['relevance_score'] ?? null,
            'status' => SeoContentSeed::STATUS_PENDING_EVALUATION,
            'region' => $data['region'] ?? null,
            'language' => $data['language'] ?? null,
            'fetched_at' => now(),
        ]);
    }

    /**
     * Registra un error en el log.
     *
     * @param  \Exception  $exception
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    protected function logError(\Exception $exception, string $message, array $context = []): void
    {
        $channel = config('seo_seeder.logging.channel', 'stack');
        $isLoggingEnabled = config('seo_seeder.logging.enabled', true);
        
        if ($isLoggingEnabled) {
            Log::channel($channel)->error($message . ': ' . $exception->getMessage(), array_merge([
                'exception' => $exception,
            ], $context));
        }
    }

    /**
     * Obtiene las seeds almacenadas según criterios opcionales.
     *
     * @param  array  $filters  Criterios de filtrado
     * @param  int  $perPage  Cantidad por página (0 para todos)
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Database\Eloquent\Collection
     */
    public function getSeeds(array $filters = [], int $perPage = 15)
    {
        $query = SeoContentSeed::query();

        // Aplicar filtros
        if (isset($filters['source_type'])) {
            $query->where('source_type', $filters['source_type']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['region'])) {
            $query->where('region', $filters['region']);
        }

        if (isset($filters['language'])) {
            $query->where('language', $filters['language']);
        }

        if (isset($filters['keyword'])) {
            $query->where('topic_keyword', 'LIKE', '%' . $filters['keyword'] . '%');
        }

        if (isset($filters['min_relevance'])) {
            $query->where('relevance_score', '>=', $filters['min_relevance']);
        }

        if (isset($filters['fetched_after'])) {
            $query->where('fetched_at', '>=', $filters['fetched_after']);
        }

        if (isset($filters['fetched_before'])) {
            $query->where('fetched_at', '<=', $filters['fetched_before']);
        }

        // Ordenar
        $orderBy = $filters['order_by'] ?? 'fetched_at';
        $orderDir = $filters['order_dir'] ?? 'desc';
        $query->orderBy($orderBy, $orderDir);

        // Paginación o recuperación completa
        if ($perPage > 0) {
            return $query->paginate($perPage);
        }

        return $query->get();
    }

    /**
     * Obtiene estadísticas básicas de las seeds almacenadas.
     *
     * @return array
     */
    public function getStats(): array
    {
        return [
            'total' => SeoContentSeed::count(),
            'by_source' => SeoContentSeed::selectRaw('source_type, count(*) as count')
                ->groupBy('source_type')
                ->pluck('count', 'source_type')
                ->toArray(),
            'by_status' => SeoContentSeed::selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray(),
            'by_region' => SeoContentSeed::selectRaw('region, count(*) as count')
                ->groupBy('region')
                ->pluck('count', 'region')
                ->toArray(),
            'by_language' => SeoContentSeed::selectRaw('language, count(*) as count')
                ->groupBy('language')
                ->pluck('count', 'language')
                ->toArray(),
            'last_fetched' => SeoContentSeed::max('fetched_at'),
        ];
    }

    /**
     * Obtiene el gestor de fetchers.
     *
     * @return \Luinuxscl\SeoContentSeeds\Services\FetcherManager
     */
    public function getFetcherManager(): FetcherManager
    {
        return $this->fetcherManager;
    }
}
