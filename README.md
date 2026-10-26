# SEO Content Seeds

![Laravel 11.x](https://img.shields.io/badge/Laravel-11.x-red.svg)
![PHP 8.1](https://img.shields.io/badge/PHP-8.1-blue.svg)
![Licencia MIT](https://img.shields.io/badge/Licencia-MIT-green.svg)

Un paquete Laravel para obtener ideas ("seeds") para la creación de contenido SEO desde diversas fuentes de datos externas. Estas ideas son estandarizadas y almacenadas en una base de datos para que la aplicación Laravel principal pueda utilizarlas posteriormente.

## Características principales

- **Arquitectura basada en Drivers**: Sistema flexible que permite añadir nuevas fuentes de datos fácilmente.
- **Google Trends vía SerpAPI**: Obtención de tendencias actuales (implementación inicial).
- **Estandarización de datos**: Transformación de datos de diferentes fuentes a un formato común.
- **Evaluación opcional con LLM**: Capacidad para evaluar la relevancia de las semillas utilizando modelos de lenguaje (requiere integración con otro paquete OpenRouter).
- **Interfaz CLI**: Comandos Artisan para gestionar la obtención y manejo de semillas.

## Instalación

### Requisitos

- PHP 8.1 o superior
- Laravel 10.x o 11.x
- Cuenta en SerpAPI (para Google Trends)

### Pasos de instalación

```bash
composer require luinuxscl/seo-contents-seeds
```

Publica los archivos de configuración y migraciones:

```bash
php artisan vendor:publish --provider="Luinuxscl\SeoContentSeeds\Providers\SeoContentSeedsServiceProvider"
```

Ejecuta las migraciones:

```bash
php artisan migrate
```

## Configuración

Después de publicar la configuración, puedes encontrar el archivo `config/seo_seeder.php`. Asegúrate de configurar tu API key de SerpAPI en tu archivo `.env`:

```
SERPAPI_API_KEY=tu_api_key_aquí
```

Otras configuraciones importantes incluyen:
- Configuración regional e idioma para Google Trends
- Activación/desactivación de la evaluación LLM
- Configuración de colas y logging

## Uso básico

### Obtener semillas de Google Trends

```bash
php artisan seo:fetch-seeds --source=google_trends
```

### Listar semillas almacenadas

```bash
php artisan seo:list-seeds
```

## Extensibilidad

El paquete está diseñado para ser fácilmente extensible. Para añadir una nueva fuente de datos:

1. Crea una nueva clase que implemente `SeedFetcherInterface`
2. Registra tu nueva fuente en el archivo de configuración

## Evaluación con LLM (Opcional)

Para utilizar la funcionalidad de evaluación de semillas con LLM, debes:

1. Activar la opción en la configuración (`llm_evaluation.enabled = true`)
2. Tener instalado y configurado un paquete de integración con OpenRouter
3. Configurar la plantilla de prompt y puntuación mínima según tus necesidades

## Licencia

Este paquete está disponible bajo la licencia MIT. Ver archivo LICENSE para más detalles.
