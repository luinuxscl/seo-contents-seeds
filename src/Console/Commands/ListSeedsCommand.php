<?php

namespace Luinuxscl\SeoContentSeeds\Console\Commands;

use Illuminate\Console\Command;
use Luinuxscl\SeoContentSeeds\Services\SeoContentSeedService;
use Luinuxscl\SeoContentSeeds\Models\SeoContentSeed;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ListSeedsCommand extends Command
{
    /**
     * El nombre y la firma del comando.
     *
     * @var string
     */
    protected $signature = 'seo:list-seeds
                            {--source= : Filtrar por tipo de fuente}
                            {--status= : Filtrar por estado (pending_evaluation, approved, rejected, evaluation_failed, processed)}
                            {--region= : Filtrar por región}
                            {--language= : Filtrar por idioma}
                            {--keyword= : Filtrar por palabra clave (búsqueda parcial)}
                            {--min-relevance= : Filtrar por puntuación mínima de relevancia}
                            {--days= : Solo semillas obtenidas en los últimos N días}
                            {--limit=15 : Número máximo de semillas a mostrar}
                            {--order=newest : Ordenar por (newest, oldest, relevance)}
                            {--format=table : Formato de salida (table, json, csv)}
                            {--stats : Mostrar solo estadísticas resumidas}';

    /**
     * La descripción del comando.
     *
     * @var string
     */
    protected $description = 'Lista las semillas de contenido SEO almacenadas en la base de datos';

    /**
     * El servicio de semillas.
     *
     * @var \Luinuxscl\SeoContentSeeds\Services\SeoContentSeedService
     */
    protected $seedService;

    /**
     * Crea una nueva instancia del comando.
     *
     * @param  \Luinuxscl\SeoContentSeeds\Services\SeoContentSeedService  $seedService
     * @return void
     */
    public function __construct(SeoContentSeedService $seedService)
    {
        parent::__construct();
        $this->seedService = $seedService;
    }

    /**
     * Ejecuta el comando.
     *
     * @return int
     */
    public function handle()
    {
        // Si solo se quieren estadísticas, mostrarlas y terminar
        if ($this->option('stats')) {
            return $this->showStats();
        }

        // Preparar filtros
        $filters = $this->prepareFilters();
        
        // Obtener límite
        $limit = (int) $this->option('limit');
        
        // Obtener semillas según los filtros
        $seeds = $this->seedService->getSeeds($filters, $limit);
        
        // Verificar si hay resultados
        if ($seeds->isEmpty()) {
            $this->info('No se encontraron semillas que coincidan con los criterios de filtrado.');
            
            // Mostrar los filtros aplicados
            $this->showAppliedFilters($filters);
            
            return 0;
        }
        
        // Mostrar resultados según el formato solicitado
        $format = $this->option('format');
        switch ($format) {
            case 'json':
                $this->output->writeln(json_encode($seeds->toArray(), JSON_PRETTY_PRINT));
                break;
                
            case 'csv':
                $this->outputCsv($seeds);
                break;
                
            case 'table':
            default:
                $this->outputTable($seeds);
                break;
        }
        
        return 0;
    }

    /**
     * Prepara los filtros basados en las opciones del comando.
     *
     * @return array
     */
    protected function prepareFilters(): array
    {
        $filters = [];
        
        // Filtros básicos
        if ($source = $this->option('source')) {
            $filters['source_type'] = $source;
        }
        
        if ($status = $this->option('status')) {
            $filters['status'] = $status;
        }
        
        if ($region = $this->option('region')) {
            $filters['region'] = $region;
        }
        
        if ($language = $this->option('language')) {
            $filters['language'] = $language;
        }
        
        if ($keyword = $this->option('keyword')) {
            $filters['keyword'] = $keyword;
        }
        
        if ($minRelevance = $this->option('min-relevance')) {
            $filters['min_relevance'] = (float) $minRelevance;
        }
        
        // Filtro por fecha
        if ($days = $this->option('days')) {
            $filters['fetched_after'] = Carbon::now()->subDays((int) $days);
        }
        
        // Ordenamiento
        $orderOption = $this->option('order');
        switch ($orderOption) {
            case 'oldest':
                $filters['order_by'] = 'fetched_at';
                $filters['order_dir'] = 'asc';
                break;
                
            case 'relevance':
                $filters['order_by'] = 'relevance_score';
                $filters['order_dir'] = 'desc';
                break;
                
            case 'newest':
            default:
                $filters['order_by'] = 'fetched_at';
                $filters['order_dir'] = 'desc';
                break;
        }
        
        return $filters;
    }

    /**
     * Muestra los filtros aplicados.
     *
     * @param  array  $filters
     * @return void
     */
    protected function showAppliedFilters(array $filters): void
    {
        if (empty($filters)) {
            $this->line('No se aplicaron filtros.');
            return;
        }
        
        $this->line('Filtros aplicados:');
        $formattedFilters = [];
        
        foreach ($filters as $key => $value) {
            if ($value instanceof Carbon) {
                $value = $value->toDateTimeString();
            } elseif (is_array($value)) {
                $value = json_encode($value);
            }
            
            $formattedFilters[] = [$key, $value];
        }
        
        $this->table(['Filtro', 'Valor'], $formattedFilters);
    }

    /**
     * Muestra la tabla de semillas.
     *
     * @param  \Illuminate\Support\Collection  $seeds
     * @return void
     */
    protected function outputTable($seeds): void
    {
        $rows = [];
        
        foreach ($seeds as $seed) {
            $rows[] = [
                $seed->id,
                $seed->topic_keyword,
                Str::limit($seed->title_suggestion, 30),
                $seed->source_type,
                $seed->status,
                $seed->relevance_score ?: 'N/A',
                $seed->region ?: 'Global',
                $seed->language ?: 'N/A',
                $seed->fetched_at->diffForHumans(),
            ];
        }
        
        $this->table(
            ['ID', 'Palabra Clave', 'Título Sugerido', 'Fuente', 'Estado', 'Relevancia', 'Región', 'Idioma', 'Obtenido'],
            $rows
        );
        
        // Mostrar total de resultados y paginación si aplica
        if (method_exists($seeds, 'total')) {
            $this->info("Mostrando {$seeds->count()} de {$seeds->total()} semillas.");
        } else {
            $this->info("Total: {$seeds->count()} semillas.");
        }
    }

    /**
     * Muestra las semillas en formato CSV.
     *
     * @param  \Illuminate\Support\Collection  $seeds
     * @return void
     */
    protected function outputCsv($seeds): void
    {
        // Encabezados
        $headers = ['id', 'topic_keyword', 'title_suggestion', 'source_type', 'status', 
                    'relevance_score', 'region', 'language', 'fetched_at'];
        
        $this->line(implode(',', $headers));
        
        // Datos
        foreach ($seeds as $seed) {
            $row = [
                $seed->id,
                '"' . str_replace('"', '""', $seed->topic_keyword) . '"',
                '"' . str_replace('"', '""', $seed->title_suggestion ?: '') . '"',
                $seed->source_type,
                $seed->status,
                $seed->relevance_score ?: 'N/A',
                $seed->region ?: 'Global',
                $seed->language ?: 'N/A',
                $seed->fetched_at->toDateTimeString(),
            ];
            
            $this->line(implode(',', $row));
        }
    }

    /**
     * Muestra estadísticas resumidas.
     *
     * @return int
     */
    protected function showStats(): int
    {
        $stats = $this->seedService->getStats();
        
        $this->info('Estadísticas de Semillas SEO:');
        $this->line('');
        
        // Total general
        $this->line('Total de semillas: ' . $stats['total']);
        $this->line('Última obtención: ' . ($stats['last_fetched'] ? Carbon::parse($stats['last_fetched'])->diffForHumans() : 'Nunca'));
        $this->line('');
        
        // Por fuente
        $this->info('Distribución por fuente:');
        $sourceStats = [];
        foreach ($stats['by_source'] as $source => $count) {
            $sourceStats[] = [$source, $count, number_format(($count / $stats['total']) * 100, 1) . '%'];
        }
        $this->table(['Fuente', 'Cantidad', 'Porcentaje'], $sourceStats);
        $this->line('');
        
        // Por estado
        $this->info('Distribución por estado:');
        $statusLabels = [
            SeoContentSeed::STATUS_PENDING_EVALUATION => 'Pendiente de evaluación',
            SeoContentSeed::STATUS_APPROVED => 'Aprobada',
            SeoContentSeed::STATUS_REJECTED => 'Rechazada',
            SeoContentSeed::STATUS_EVALUATION_FAILED => 'Evaluación fallida',
            SeoContentSeed::STATUS_PROCESSED => 'Procesada',
        ];
        
        $statusStats = [];
        foreach ($stats['by_status'] as $status => $count) {
            $label = $statusLabels[$status] ?? $status;
            $statusStats[] = [$label, $count, number_format(($count / $stats['total']) * 100, 1) . '%'];
        }
        $this->table(['Estado', 'Cantidad', 'Porcentaje'], $statusStats);
        $this->line('');
        
        // Por región e idioma
        if (!empty($stats['by_region'])) {
            $this->info('Principales regiones:');
            $regionStats = [];
            foreach ($stats['by_region'] as $region => $count) {
                $regionStats[] = [$region ?: 'Global', $count];
            }
            $this->table(['Región', 'Cantidad'], $regionStats);
            $this->line('');
        }
        
        if (!empty($stats['by_language'])) {
            $this->info('Principales idiomas:');
            $langStats = [];
            foreach ($stats['by_language'] as $lang => $count) {
                $langStats[] = [$lang ?: 'No especificado', $count];
            }
            $this->table(['Idioma', 'Cantidad'], $langStats);
        }
        
        return 0;
    }
}
