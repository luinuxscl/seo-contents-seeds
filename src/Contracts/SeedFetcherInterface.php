<?php

namespace Luinuxscl\SeoContentSeeds\Contracts;

use Illuminate\Support\Collection;

interface SeedFetcherInterface
{
    /**
     * Obtiene las semillas desde la fuente externa.
     *
     * @param  array  $options  Opciones específicas del fetcher
     * @return \Illuminate\Support\Collection  Colección de datos estandarizados
     *
     * @throws \Luinuxscl\SeoContentSeeds\Exceptions\SeedFetchException
     */
    public function fetch(array $options = []): Collection;

    /**
     * Obtiene el identificador único del fetcher.
     *
     * @return string
     */
    public function getIdentifier(): string;

    /**
     * Obtiene el nombre para mostrar del fetcher.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Verifica si el fetcher está correctamente configurado.
     *
     * @return bool
     */
    public function isConfigured(): bool;

    /**
     * Obtiene la configuración predeterminada para este fetcher.
     *
     * @return array
     */
    public function getDefaultOptions(): array;
}
