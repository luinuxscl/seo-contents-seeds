<?php

namespace Luinuxscl\SeoContentSeeds\Exceptions;

use Exception;

class SeedFetchException extends Exception
{
    /**
     * La fuente que causó el error.
     *
     * @var string|null
     */
    protected $source;

    /**
     * Crea una nueva instancia.
     *
     * @param  string  $message
     * @param  int  $code
     * @param  \Throwable|null  $previous
     * @param  string|null  $source
     * @return void
     */
    public function __construct(
        string $message = "Error al obtener datos de fuente externa",
        int $code = 0,
        \Throwable $previous = null,
        string $source = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->source = $source;
    }

    /**
     * Obtiene la fuente que causó el error.
     *
     * @return string|null
     */
    public function getSource(): ?string
    {
        return $this->source;
    }

    /**
     * Establece la fuente que causó el error.
     *
     * @param  string  $source
     * @return $this
     */
    public function setSource(string $source): self
    {
        $this->source = $source;
        return $this;
    }
}
