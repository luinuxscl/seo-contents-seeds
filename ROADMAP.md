# ROADMAP: Desarrollo del Paquete SEO Content Seeds

Este documento proporciona una planificación detallada para el desarrollo del paquete `luinuxscl/seo-contents-seeds`. Servirá como guía durante todo el proceso de desarrollo y se actualizará periódicamente para reflejar el progreso.

## Estado Actual

-   [x] Definir principios de desarrollo

## Fase 1: Configuración Inicial del Paquete

-   [x] Crear estructura base de directorios siguiendo PSR-4
-   [x] Configurar composer.json con dependencias y autoloading
-   [x] Implementar ServiceProvider básico
-   [x] Crear archivo de configuración (config/seo_seeder.php)
-   [x] Escribir README.md inicial con descripción y guía de instalación

## Fase 2: Implementación del Modelo y Base de Datos

-   [x] Crear migración para la tabla seo_content_seeds
-   [x] Implementar modelo Eloquent SeoContentSeed
-   [x] Definir scopes útiles para consultas comunes
-   [x] Agregar métodos de ayuda en el modelo

## Fase 3: Arquitectura de Fetchers

-   [x] Definir interfaz SeedFetcherInterface
-   [x] Crear clase abstracta AbstractSeedFetcher
-   [x] Implementar clase FetcherManager para gestionar drivers
-   [x] Desarrollar servicio principal SeoContentSeedService
-   [x] Definir contratos para transformación de datos

## Fase 4: Implementación de Google Trends Fetcher

**NOTA:** Mi API key de SerpAPI es :
eb4710cd6a446823150f439a1108fc898a48dca2f3a63ce61580e7dc6304c2e6

-   [x] Crear GoogleTrendsSerpApiFetcher implementando la interfaz
-   [x] Implementar manejo de errores robusto para llamadas a SerpAPI
-   [x] Desarrollar transformador de datos para estandarizar respuestas
-   [x] Añadir mecanismos de caché para optimizar llamadas API

## Fase 5: Comandos Artisan y Tareas Programadas

-   [x] Implementar comando principal FetchSeedsCommand
-   [x] Crear comando para listar semillas y su estado
-   [x] Añadir soporte para programación mediante Schedule
-   [x] Configurar opciones y argumentos para mayor flexibilidad

## Fase 6: Evaluación con LLM (Opcional)

-   [ ] Crear EvaluateSeedRelevanceJob para procesamiento asíncrono
-   [ ] Implementar integración con paquete OpenRouter externo
-   [ ] Desarrollar generador de prompts basado en configuración
-   [ ] Añadir capacidad de procesamiento por lotes
-   [ ] Implementar manejo de errores para llamadas LLM

## Fase 7: Refactorización y Optimización

-   [ ] Revisar y optimizar consultas a la base de datos
-   [ ] Refactorizar código para mayor legibilidad y mantenibilidad
-   [ ] Añadir caché estratégica donde sea beneficioso
-   [ ] Optimizar uso de memoria en procesos por lotes

## Fase 8: Documentación y Preparación para Publicación

-   [ ] Completar documentación en código (PHPDoc)
-   [ ] Crear guía de uso detallada en README.md
-   [ ] Documentar proceso de extensión para nuevos fetchers
-   [ ] Añadir ejemplos de configuración y uso
-   [ ] Crear guía de contribución (CONTRIBUTING.md)

## Fase 9: Control de Calidad

-   [ ] Realizar verificación estática de código
-   [ ] Verificar compatibilidad con diferentes versiones de Laravel
-   [ ] Revisar potenciales problemas de seguridad

## Fase 10: Extensibilidad y Mejoras Futuras

-   [ ] Preparar estructura para nuevos fetchers (RSS, Twitter, etc.)
-   [ ] Implementar endpoints API básicos (opcional)
-   [ ] Añadir panel de administración sencillo (opcional)
-   [ ] Desarrollar sistema de métricas para evaluar fuentes (opcional)

---

**Notas**:

-   Este roadmap se irá actualizando conforme avance el desarrollo.
-   Las tareas no son estrictamente secuenciales; algunas pueden desarrollarse en paralelo.
-   La prioridad es entregar primero un MVP funcional con las capacidades básicas.
