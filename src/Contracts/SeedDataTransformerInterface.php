<?php

namespace Luinuxscl\SeoContentSeeds\Contracts;

use Illuminate\Support\Collection;

interface SeedDataTransformerInterface
{
    /**
     * Transforma los datos crudos de una fuente a un formato estandarizado.
     *
     * @param  mixed  $rawData  Datos crudos obtenidos de la fuente
     * @param  array  $options  Opciones adicionales para la transformación
     * @return \Illuminate\Support\Collection  Colección de datos estandarizados
     */
    public function transform($rawData, array $options = []): Collection;

    /**
     * Valida los datos estandarizados.
     *
     * @param  array  $data  Datos estandarizados para una seed
     * @return bool
     */
    public function validate(array $data): bool;

    /**
     * Obtiene la estructura esperada para los datos estandarizados.
     *
     * @return array  Esquema con tipos de datos y requisitos
     */
    public function getSchema(): array;
}
