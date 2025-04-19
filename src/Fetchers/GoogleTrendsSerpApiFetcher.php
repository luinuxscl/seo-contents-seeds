<?php

namespace Luinuxscl\SeoContentSeeds\Fetchers;

use Illuminate\Support\Collection;
use Luinuxscl\SeoContentSeeds\Exceptions\SeedFetchException;
use Luinuxscl\SeoContentSeeds\Fetchers\Transformers\GoogleTrendsDataTransformer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Arr;
use Illuminate\Http\Client\RequestException;

class GoogleTrendsSerpApiFetcher extends AbstractSeedFetcher
{
    /**
     * URL base de la API de SerpApi.
     *
     * @var string
     */
    protected $baseUrl = 'https://serpapi.com/search.json';

    /**
     * El transformador de datos.
     *
     * @var \Luinuxscl\SeoContentSeeds\Fetchers\Transformers\GoogleTrendsDataTransformer
     */
    protected $transformer;

    /**
     * Crea una nueva instancia del fetcher.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  array  $options
     * @return void
     */
    public function __construct($app, array $options = [])
    {
        parent::__construct($app, $options);
        $this->transformer = new GoogleTrendsDataTransformer();
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier(): string
    {
        return 'google_trends';
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Google Trends (SerpApi)';
    }

    /**
     * {@inheritdoc}
     */
    public function isConfigured(): bool
    {
        $apiKey = $this->getApiKey();
        return !empty($apiKey);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultOptions(): array
    {
        $options = parent::getDefaultOptions();
        
        return array_merge($options, [
            'geo' => config('seo_seeder.serpapi.google_trends.default_geo', 'CL'),
            'hl' => config('seo_seeder.serpapi.google_trends.default_hl', 'es'),
            'category' => config('seo_seeder.serpapi.google_trends.default_category', 'all'),
            'items_limit' => config('seo_seeder.serpapi.google_trends.items_limit', 20),
            'cache_duration' => config('seo_seeder.serpapi.google_trends.cache_duration', 60 * 24),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function fetchFromSource()
    {
        $apiKey = $this->getApiKey();
        
        if (empty($apiKey)) {
            throw new SeedFetchException('No se ha configurado una API Key para SerpApi');
        }
        
        try {
            $params = [
                'api_key' => $apiKey,
                'engine' => 'google_trends_trending_now',
                'geo' => Arr::get($this->options, 'geo'),
                'hl' => Arr::get($this->options, 'hl'),
            ];

            // Si hay una categoría específica, añadirla a la solicitud
            $category = Arr::get($this->options, 'category');
            if (!empty($category) && $category !== 'all') {
                $params['cat'] = $category;
            }
            
            // Realizar la petición HTTP a SerpApi
            $response = Http::timeout(10)
                ->retry(3, 1000)
                ->get($this->baseUrl, $params);
            
            // Verificar si la respuesta fue exitosa
            if ($response->successful()) {
                return $response->json();
            }
            
            // Si hubo un error, obtener detalles
            $errorData = $response->json();
            $errorMessage = $errorData['error'] ?? 'Error desconocido en la respuesta de SerpApi';
            
            throw new SeedFetchException('Error en SerpApi: ' . $errorMessage);
        } catch (RequestException $e) {
            throw new SeedFetchException('Error de conexión con SerpApi: ' . $e->getMessage(), 0, $e);
        } catch (\Exception $e) {
            if (!$e instanceof SeedFetchException) {
                throw new SeedFetchException('Error al obtener datos de Google Trends: ' . $e->getMessage(), 0, $e);
            }
            
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function standardizeData($data): Collection
    {
        if (empty($data) || !is_array($data)) {
            return new Collection();
        }
        
        try {
            return $this->transformer->transform($data, $this->options);
        } catch (\Exception $e) {
            throw new SeedFetchException('Error al estandarizar datos de Google Trends: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Obtiene la API Key de SerpApi.
     *
     * @return string|null
     */
    protected function getApiKey(): ?string
    {
        // Usar la API key proporcionada en el ROADMAP
        return config('seo_seeder.serpapi.api_key', 'eb4710cd6a446823150f439a1108fc898a48dca2f3a63ce61580e7dc6304c2e6');
    }
}
