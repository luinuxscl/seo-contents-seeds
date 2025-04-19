<?php

namespace Luinuxscl\SeoContentSeeds\Services;

use Illuminate\Support\Manager;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Luinuxscl\SeoContentSeeds\Contracts\SeedFetcherInterface;
use Luinuxscl\SeoContentSeeds\Exceptions\SeedFetchException;

class FetcherManager extends Manager
{
    /**
     * Crea una instancia del fetcher especificado.
     *
     * @param  string  $driver
     * @return \Luinuxscl\SeoContentSeeds\Contracts\SeedFetcherInterface
     *
     * @throws \InvalidArgumentException
     */
    public function driver($driver = null)
    {
        $driver = $driver ?: $this->getDefaultDriver();

        if (is_null($driver)) {
            throw new InvalidArgumentException("No se ha especificado un fetcher y no hay uno predeterminado.");
        }

        // Si el driver ya ha sido creado, lo devolvemos
        if (isset($this->drivers[$driver])) {
            return $this->drivers[$driver];
        }

        // Comprobamos si el fetcher está habilitado
        if (!$this->isDriverEnabled($driver)) {
            throw new InvalidArgumentException("El fetcher [{$driver}] no está habilitado.");
        }

        return $this->drivers[$driver] = $this->createDriver($driver);
    }

    /**
     * Crea una instancia del fetcher.
     *
     * @param  string  $driver
     * @return \Luinuxscl\SeoContentSeeds\Contracts\SeedFetcherInterface
     *
     * @throws \InvalidArgumentException
     */
    protected function createDriver($driver)
    {
        $method = 'create' . Str::studly($driver) . 'Driver';

        if (method_exists($this, $method)) {
            return $this->$method();
        }

        // Verificamos si la clase del driver está definida en la configuración
        $config = $this->config->get("seo_seeder.fetchers.{$driver}");

        if (isset($config['class']) && class_exists($config['class'])) {
            return $this->container->make($config['class']);
        }

        throw new InvalidArgumentException("El fetcher [{$driver}] no es compatible.");
    }

    /**
     * Obtiene todos los fetchers habilitados.
     *
     * @return array
     */
    public function getEnabledDrivers(): array
    {
        $drivers = [];
        $fetchers = $this->config->get('seo_seeder.fetchers', []);

        foreach ($fetchers as $key => $config) {
            if (isset($config['enabled']) && $config['enabled'] === true) {
                $drivers[$key] = $config;
            }
        }

        // Ordenar por prioridad (si está definida)
        uasort($drivers, function ($a, $b) {
            $priorityA = $a['priority'] ?? 999;
            $priorityB = $b['priority'] ?? 999;
            return $priorityA <=> $priorityB;
        });

        return $drivers;
    }

    /**
     * Verifica si un driver está habilitado.
     *
     * @param  string  $driver
     * @return bool
     */
    public function isDriverEnabled(string $driver): bool
    {
        $config = $this->config->get("seo_seeder.fetchers.{$driver}");
        
        return isset($config['enabled']) && $config['enabled'] === true;
    }

    /**
     * Obtiene la instancia del fetcher a partir de un identificador de fuente.
     *
     * @param  string  $sourceType
     * @return \Luinuxscl\SeoContentSeeds\Contracts\SeedFetcherInterface
     *
     * @throws \InvalidArgumentException
     */
    public function resolveBySourceType(string $sourceType): SeedFetcherInterface
    {
        $fetchers = $this->getEnabledDrivers();
        
        foreach ($fetchers as $driver => $config) {
            try {
                $fetcher = $this->driver($driver);
                
                if ($fetcher->getIdentifier() === $sourceType) {
                    return $fetcher;
                }
            } catch (\Exception $e) {
                // Ignoramos los fetchers que no se puedan instanciar
                continue;
            }
        }
        
        throw new InvalidArgumentException("No se encontró un fetcher para el tipo de fuente [{$sourceType}].");
    }

    /**
     * Obtiene el driver predeterminado.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        $fetchers = $this->getEnabledDrivers();
        
        if (empty($fetchers)) {
            throw new InvalidArgumentException("No hay fetchers habilitados.");
        }
        
        // Retorna el primer fetcher habilitado (ya están ordenados por prioridad)
        return array_key_first($fetchers);
    }
}
