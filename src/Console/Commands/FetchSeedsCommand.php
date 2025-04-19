<?php

namespace Luinuxscl\SeoContentSeeds\Console\Commands;

use Illuminate\Console\Command;
use Luinuxscl\SeoContentSeeds\Services\SeoContentSeedService;
use Luinuxscl\SeoContentSeeds\Services\FetcherManager;
use Symfony\Component\Console\Helper\Table;

class FetchSeedsCommand extends Command
{
    /**
     * El nombre y la firma del comando.
     *
     * @var string
     */
    protected $signature = 'seo:fetch-seeds
                            {--source= : El origen de los datos (por defecto, todos los habilitados)}
                            {--geo= : Región/país (por defecto, el configurado en config)}
                            {--hl= : Idioma (por defecto, el configurado en config)}
                            {--category= : Categoría específica a obtener (por defecto, todas)}
                            {--limit= : Número máximo de semillas a obtener}
                            {--no-cache : Desactivar la caché para esta ejecución}';

    /**
     * La descripción del comando.
     *
     * @var string
     */
    protected $description = 'Obtiene semillas de contenido SEO desde fuentes externas configuradas';

    /**
     * El servicio de semillas.
     *
     * @var \Luinuxscl\SeoContentSeeds\Services\SeoContentSeedService
     */
    protected $seedService;

    /**
     * El gestor de fetchers.
     *
     * @var \Luinuxscl\SeoContentSeeds\Services\FetcherManager
     */
    protected $fetcherManager;

    /**
     * Crea una nueva instancia del comando.
     *
     * @param  \Luinuxscl\SeoContentSeeds\Services\SeoContentSeedService  $seedService
     * @param  \Luinuxscl\SeoContentSeeds\Services\FetcherManager  $fetcherManager
     * @return void
     */
    public function __construct(SeoContentSeedService $seedService, FetcherManager $fetcherManager)
    {
        parent::__construct();
        $this->seedService = $seedService;
        $this->fetcherManager = $fetcherManager;
    }

    /**
     * Ejecuta el comando.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Iniciando obtención de semillas de contenido SEO...');

        // Obtener opciones del comando
        $source = $this->option('source');
        $useCacheOption = !$this->option('no-cache');
        
        // Preparar opciones globales para todos los fetchers
        $globalOptions = $this->prepareGlobalOptions();
        
        // Obtener los fetchers habilitados para mostrar información
        $enabledFetchers = $this->fetcherManager->getEnabledDrivers();
        if (empty($enabledFetchers)) {
            $this->error('No hay fetchers habilitados. Revise la configuración.');
            return 1;
        }
        
        // Si se especificó un origen, verificar que existe y está habilitado
        if ($source && !isset($enabledFetchers[$source])) {
            $this->error("La fuente '{$source}' no está disponible o habilitada.");
            $this->info('Fuentes disponibles:');
            foreach ($enabledFetchers as $driver => $config) {
                $this->line(" - {$driver}");
            }
            return 1;
        }
        
        // Mostrar información sobre la obtención
        $this->info('Configuración:');
        $this->table(
            ['Parámetro', 'Valor'],
            [
                ['Fuente', $source ?: 'Todas las habilitadas'],
                ['Región', $globalOptions['geo'] ?? 'Predeterminada'],
                ['Idioma', $globalOptions['hl'] ?? 'Predeterminado'],
                ['Categoría', $globalOptions['category'] ?? 'Todas'],
                ['Límite', $globalOptions['items_limit'] ?? 'Predeterminado'],
                ['Usar caché', $useCacheOption ? 'Sí' : 'No'],
            ]
        );
        
        try {
            $this->newLine();
            $this->info('Obteniendo datos...');
            
            // Ejecutar la obtención de semillas
            if ($source) {
                // De una fuente específica
                $result = $this->seedService->fetchAndStore($source, $globalOptions);
                $this->displaySourceResult($source, $result);
            } else {
                // De todas las fuentes habilitadas
                $results = $this->seedService->fetchAndStoreFromAllSources($globalOptions);
                foreach ($results as $driver => $result) {
                    $this->displaySourceResult($driver, $result);
                }
            }
            
            $this->newLine();
            $this->info('Proceso completado exitosamente.');
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Error al obtener semillas: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Prepara las opciones globales para los fetchers.
     *
     * @return array
     */
    protected function prepareGlobalOptions(): array
    {
        $options = [];
        
        // Opciones específicas para la fuente
        if ($geo = $this->option('geo')) {
            $options['geo'] = $geo;
        }
        
        if ($hl = $this->option('hl')) {
            $options['hl'] = $hl;
        }
        
        if ($category = $this->option('category')) {
            $options['category'] = $category;
        }
        
        if ($limit = $this->option('limit')) {
            $options['items_limit'] = (int) $limit;
        }
        
        // Opción de caché
        $options['cache_enabled'] = !$this->option('no-cache');
        
        return $options;
    }

    /**
     * Muestra los resultados de una fuente específica.
     *
     * @param  string  $source
     * @param  array  $result
     * @return void
     */
    protected function displaySourceResult(string $source, array $result): void
    {
        $this->newLine();
        $this->info("Resultados de {$source}:");
        
        $fetched = $result['fetched'] ?? 0;
        $stored = $result['stored'] ?? 0;
        $errors = $result['errors'] ?? [];
        
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Seeds obtenidas', $fetched],
                ['Seeds almacenadas', $stored],
                ['Errores', count($errors)],
            ]
        );
        
        if (!empty($errors)) {
            $this->warn('Se encontraron errores:');
            $errorTable = [];
            foreach ($errors as $index => $error) {
                $errorTable[] = [
                    $index + 1,
                    $error['message'] ?? 'Error desconocido',
                ];
                
                if ($this->getOutput()->isVerbose() && isset($error['data'])) {
                    $this->line('Datos relacionados con el error #' . ($index + 1) . ':');
                    foreach ($error['data'] as $key => $value) {
                        $this->line("  {$key}: " . (is_scalar($value) ? $value : json_encode($value)));
                    }
                }
            }
            
            $this->table(['#', 'Mensaje'], $errorTable);
        }
    }
}
