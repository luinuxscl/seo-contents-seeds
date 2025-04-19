<?php

namespace Luinuxscl\SeoContentSeeds\Fetchers;

use Illuminate\Support\Collection;
use Luinuxscl\SeoContentSeeds\Contracts\SeedFetcherInterface;
use Luinuxscl\SeoContentSeeds\Exceptions\SeedFetchException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Arr;

abstract class AbstractSeedFetcher implements SeedFetcherInterface
{
    /**
     * La instancia de aplicación.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * Las opciones del fetcher.
     *
     * @var array
     */
    protected $options = [];
    
    /**
     * Crea una nueva instancia del fetcher.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  array  $options
     * @return void
     */
    public function __construct($app, array $options = [])
    {
        $this->app = $app;
        $this->options = array_merge($this->getDefaultOptions(), $options);
    }

    /**
     * Obtiene el nombre del fetcher.
     *
     * @return string
     */
    public function getName(): string
    {
        return class_basename($this);
    }

    /**
     * {@inheritdoc}
     */
    public function isConfigured(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultOptions(): array
    {
        return [
            'cache_enabled' => true,
            'cache_duration' => 60, // minutos
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function fetch(array $options = []): Collection
    {
        $this->options = array_merge($this->options, $options);

        try {
            $cacheKey = $this->getCacheKey();
            
            if ($this->shouldUseCache() && Cache::has($cacheKey)) {
                return Cache::get($cacheKey);
            }

            $data = $this->fetchFromSource();
            $standardizedData = $this->standardizeData($data);
            
            if ($this->shouldUseCache()) {
                $cacheDuration = Arr::get($this->options, 'cache_duration', 60);
                Cache::put($cacheKey, $standardizedData, now()->addMinutes($cacheDuration));
            }
            
            return $standardizedData;
        } catch (\Exception $e) {
            $this->logError($e);
            throw new SeedFetchException('Error al obtener datos de ' . $this->getName() . ': ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Genera una clave de caché única para este fetcher y sus opciones.
     *
     * @return string
     */
    protected function getCacheKey(): string
    {
        $identifier = $this->getIdentifier();
        $optionsHash = md5(json_encode(Arr::except($this->options, ['cache_enabled', 'cache_duration'])));
        
        return "seo_seeds_fetcher_{$identifier}_{$optionsHash}";
    }

    /**
     * Determina si se debe usar la caché.
     *
     * @return bool
     */
    protected function shouldUseCache(): bool
    {
        return Arr::get($this->options, 'cache_enabled', true);
    }

    /**
     * Registra un error en el log.
     *
     * @param  \Exception  $exception
     * @return void
     */
    protected function logError(\Exception $exception): void
    {
        $channel = config('seo_seeder.logging.channel', 'stack');
        $isLoggingEnabled = config('seo_seeder.logging.enabled', true);
        
        if ($isLoggingEnabled) {
            Log::channel($channel)->error('Error en ' . $this->getName() . ': ' . $exception->getMessage(), [
                'exception' => $exception,
                'fetcher' => $this->getIdentifier(),
                'options' => Arr::except($this->options, ['api_key']), // No logear API keys
            ]);
        }
    }

    /**
     * Obtiene datos en bruto de la fuente.
     *
     * @return mixed
     * 
     * @throws \Exception
     */
    abstract protected function fetchFromSource();

    /**
     * Estandariza los datos crudos en un formato común.
     *
     * @param  mixed  $data
     * @return \Illuminate\Support\Collection
     */
    abstract protected function standardizeData($data): Collection;
}
