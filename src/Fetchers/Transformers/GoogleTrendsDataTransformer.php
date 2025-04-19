<?php

namespace Luinuxscl\SeoContentSeeds\Fetchers\Transformers;

use Illuminate\Support\Collection;
use Luinuxscl\SeoContentSeeds\Contracts\SeedDataTransformerInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class GoogleTrendsDataTransformer implements SeedDataTransformerInterface
{
    /**
     * Transforma los datos crudos de Google Trends (via SerpAPI) a formato estandarizado.
     *
     * @param  mixed  $rawData  Los datos crudos de SerpAPI
     * @param  array  $options  Opciones adicionales para la transformación
     * @return \Illuminate\Support\Collection
     */
    public function transform($rawData, array $options = []): Collection
    {
        $result = new Collection();
        
        // Verificar si los datos son válidos
        if (!is_array($rawData) || !isset($rawData['trending_searches']) || !is_array($rawData['trending_searches'])) {
            return $result;
        }

        // Obtener información regional
        $geo = Arr::get($rawData, 'search_parameters.geo');
        $language = Arr::get($rawData, 'search_parameters.hl');
        
        // Transformar cada búsqueda de tendencia
        foreach ($rawData['trending_searches'] as $trend) {
            // Verificación básica de integridad
            if (!isset($trend['query'])) {
                continue;
            }
            
            // Crear estructura de datos estandarizada
            $seedData = [
                'topic_keyword' => $trend['query'],
                'title_suggestion' => $this->generateTitleSuggestion($trend),
                'region' => $geo,
                'language' => $language,
                'source_identifier' => 'google_trends_' . $geo . '_' . $language,
                'details' => $this->formatDetails($trend),
            ];
            
            // Solo incluir si pasa la validación
            if ($this->validate($seedData)) {
                $result->push($seedData);
            }
        }
        
        // Filtrar las tendencias basadas en el límite configurado
        $limit = Arr::get($options, 'items_limit', 20);
        
        return $result->take($limit);
    }

    /**
     * Genera un título sugerido basado en la tendencia.
     *
     * @param  array  $trend  Datos de la tendencia
     * @return string
     */
    protected function generateTitleSuggestion(array $trend): string
    {
        $query = $trend['query'];
        
        // Si existe un desglose de tendencia, usar variantes relevantes para enriquecer el título
        if (isset($trend['trend_breakdown']) && is_array($trend['trend_breakdown']) && count($trend['trend_breakdown']) > 0) {
            // Intentar encontrar una variante más rica para el título
            $betterQueries = array_filter($trend['trend_breakdown'], function ($variant) use ($query) {
                // Buscar variantes que contengan la consulta original pero sean más descriptivas
                return Str::contains(Str::lower($variant), Str::lower($query)) && 
                       strlen($variant) > strlen($query) && 
                       str_word_count($variant) > str_word_count($query);
            });
            
            if (!empty($betterQueries)) {
                // Usar la primera variante más descriptiva
                return Str::title(reset($betterQueries));
            }
        }
        
        // Si no hay una mejor variante, formatear la consulta original
        return Str::title($query);
    }

    /**
     * Formatea los detalles para almacenamiento en JSON.
     *
     * @param  array  $trend  Datos de la tendencia
     * @return array
     */
    protected function formatDetails(array $trend): array
    {
        $details = [
            'search_volume' => $trend['search_volume'] ?? 0,
            'increase_percentage' => $trend['increase_percentage'] ?? 0,
            'active' => $trend['active'] ?? true,
            'start_timestamp' => $trend['start_timestamp'] ?? null,
            'end_timestamp' => $trend['end_timestamp'] ?? null,
            'categories' => [],
        ];
        
        // Añadir categorías si existen
        if (isset($trend['categories']) && is_array($trend['categories'])) {
            $details['categories'] = array_map(function ($category) {
                return [
                    'id' => $category['id'] ?? 0,
                    'name' => $category['name'] ?? 'Unknown',
                ];
            }, $trend['categories']);
        }
        
        // Añadir desglose de tendencia si existe
        if (isset($trend['trend_breakdown']) && is_array($trend['trend_breakdown'])) {
            $details['trend_breakdown'] = $trend['trend_breakdown'];
        }
        
        // Añadir enlace a Google Trends si existe
        if (isset($trend['serpapi_google_trends_link'])) {
            $details['serpapi_google_trends_link'] = $trend['serpapi_google_trends_link'];
        }
        
        return $details;
    }

    /**
     * Valida los datos de la semilla.
     *
     * @param  array  $data  Datos estandarizados para validar
     * @return bool
     */
    public function validate(array $data): bool
    {
        // Verificar campos obligatorios
        if (empty($data['topic_keyword'])) {
            return false;
        }
        
        // Verificar que la keyword no sea demasiado corta
        if (strlen($data['topic_keyword']) < 2) {
            return false;
        }

        // Verificar que el volumen de búsqueda supere un umbral mínimo si existe
        if (isset($data['details']['search_volume']) && $data['details']['search_volume'] < 50) {
            return false;
        }
        
        return true;
    }

    /**
     * Obtiene el esquema de datos esperado.
     *
     * @return array
     */
    public function getSchema(): array
    {
        return [
            'topic_keyword' => 'string|required',
            'title_suggestion' => 'string|nullable',
            'region' => 'string|nullable',
            'language' => 'string|nullable',
            'source_identifier' => 'string|nullable',
            'details' => [
                'search_volume' => 'integer|nullable',
                'increase_percentage' => 'integer|nullable',
                'active' => 'boolean|nullable',
                'start_timestamp' => 'integer|nullable',
                'end_timestamp' => 'integer|nullable',
                'categories' => 'array|nullable',
                'trend_breakdown' => 'array|nullable',
                'serpapi_google_trends_link' => 'string|nullable',
            ],
        ];
    }
}
