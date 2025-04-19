# Principios de Desarrollo para el Paquete "Generador de Seeds SEO"

Este documento establece las reglas y principios fundamentales que guiarán el desarrollo del paquete "Generador de Seeds SEO".

## Principios Fundamentales

1.  **Simplicidad como prioridad:**

    -   El código más sencillo es el mejor.
    -   Implementar solo lo estrictamente necesario para:
        -   Obtener datos de fuentes configuradas (Trends, Feeds, etc.).
        -   Estandarizar los datos en el modelo `SeoContentSeed`.
        -   Almacenar los datos en la base de datos.
        -   Gestionar (opcionalmente) la **delegación** de la evaluación de relevancia a un paquete externo.
    -   Evitar características accesorias que no aporten al flujo central.

2.  **Código limpio y mantenible:**

    -   Seguir principios SOLID (ej: Responsabilidad Única para cada "Fetcher", Abierto/Cerrado para añadir nuevas fuentes).
    -   Utilizar patrones de diseño claros (ej: Strategy/Driver para Fetchers).
    -   Priorizar legibilidad sobre complejidad innecesaria.

3.  **Estándares PSR:**

    -   Adherirse estrictamente a `PSR-4` para autoloading.
    -   Seguir `PSR-12` para el estilo de código.
    -   Mantener consistencia en todo el código base.

4.  **Documentación concisa:**
    -   Comentarios claros donde sean necesarios (explicar el _por qué_).
    -   Documentar la configuración, el uso de comandos Artisan, la estructura del modelo `SeoContentSeed` (esp. `details`), cómo extender con `Workspaceers`, y la **interacción requerida con el paquete OpenRouter** si se usa la evaluación LLM.
    -   Nombres auto-descriptivos para clases, métodos y variables.

## Estructura y Arquitectura

1.  **Un solo modelo central (`SeoContentSeed`):**

    -   Implementar únicamente el modelo `SeoContentSeed` con los campos esenciales estandarizados.
    -   Mantener la tabla de base de datos simple y enfocada.

2.  **Arquitectura Basada en Drivers/Fetchers:**

    -   Diseñar para que cada fuente de datos (Google Trends, RSS) tenga su `Workspaceer` implementando una interfaz común (`SeedFetcherInterface`).
    -   Facilitar la extensión con nuevas fuentes.

3.  **Interfaz de Usuario Mínima (Comandos Artisan):**

    -   La interacción principal será vía comandos de Artisan claros (`seo:fetch-seeds`, `seo:evaluate-seeds` si aplica).
    -   Evitar API programática compleja (Facades) a menos que sea estrictamente necesario.

4.  **Minimizar Dependencias Externas (Excepto Clientes API Fuentes y Paquete OpenRouter Opcional):**
    -   Usar principalmente características de Laravel Core.
    -   Dependencia necesaria: Un cliente HTTP (Facade `Http` de Laravel o Guzzle) para llamar a APIs de _fuentes_ de datos (SerpApi).
    -   Dependencia Opcional: Para la evaluación LLM, el paquete **interactuará con la interfaz pública** (Facade, Service Contract) de un **paquete Laravel-OpenRouter externo** que el usuario deberá instalar y configurar. **No implementar llamadas directas a OpenRouter** en este paquete. Documentar claramente esta dependencia/interacción opcional.

## Detalles de Implementación

1.  **Nombres descriptivos:**

    -   Nomenclatura clara, consistente (preferiblemente inglés por convención Laravel).
    -   Evitar abreviaturas confusas.

2.  **Evitar complejidad oculta:**

    -   No "sobre-ingenierizar".
    -   Preferir soluciones directas y legibles.

3.  **Manejo Robusto de APIs Externas y Paquetes Delegados:**

    -   Implementar manejo de errores explícito (timeouts, errores auth, rate limits, respuestas inválidas) al llamar a APIs de _fuentes_ de datos (SerpApi).
    -   Al _delegar_ la evaluación LLM al paquete OpenRouter: implementar manejo robusto de **excepciones o errores devueltos por ese paquete**.
    -   Usar reintentos en Jobs de cola si los errores son transitorios.
    -   Registrar errores de forma clara para diagnóstico.

4.  **Errores explícitos:**
    -   Lanzar excepciones específicas si falta configuración esencial propia (ej: API Key de SerpApi).
    -   Reportar errores claros en comandos Artisan.
    -   Manejar fallos en Jobs de cola adecuadamente (marcar como fallido, log).
    -   Reportar errores originados en la interacción con el paquete OpenRouter delegado.
