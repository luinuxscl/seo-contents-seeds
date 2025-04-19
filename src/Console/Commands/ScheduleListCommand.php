<?php

namespace Luinuxscl\SeoContentSeeds\Console\Commands;

use Illuminate\Console\Command;

class ScheduleListCommand extends Command
{
    /**
     * El nombre y la firma del comando.
     *
     * @var string
     */
    protected $signature = 'seo:schedule-list';

    /**
     * La descripción del comando.
     *
     * @var string
     */
    protected $description = 'Muestra la programación de tareas recomendada para SEO Content Seeds';

    /**
     * Ejecuta el comando.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('SEO Content Seeds - Configuración recomendada de programación');
        $this->line('==========================================');
        $this->newLine();
        
        $this->info('Para programar la obtención automática de seeds, añada lo siguiente a su App\Console\Kernel:');
        $this->newLine();
        
        $this->line('
    /**
     * Define the application\'s command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Obtener semillas de Google Trends diariamente a las 8am
        $schedule->command(\'seo:fetch-seeds --source=google_trends\')
                ->dailyAt(\'08:00\')
                ->appendOutputTo(storage_path(\'logs/seo-seeds-fetching.log\'));
                
        // Obtener semillas de todas las fuentes cada lunes a las 10am
        $schedule->command(\'seo:fetch-seeds\')
                ->weeklyOn(1, \'10:00\')
                ->appendOutputTo(storage_path(\'logs/seo-seeds-fetching.log\'));
                
        // Enviar informe semanal por correo (opcional)
        $schedule->command(\'seo:list-seeds --stats --format=json\')
                ->weeklyOn(5, \'16:00\')
                ->sendOutputTo(storage_path(\'app/seo-seeds-report.json\'));
    }
');
        
        $this->newLine();
        $this->line('No olvide que para que el scheduler de Laravel funcione, debe configurar un cron job:');
        $this->line('* * * * * cd /ruta/de/su/proyecto && php artisan schedule:run >> /dev/null 2>&1');
        $this->newLine();
        
        $this->info('Configuración para evaluación con LLM (si está habilitada):');
        $this->line('
    protected function schedule(Schedule $schedule): void
    {
        // Evaluar semillas pendientes diariamente a medianoche
        $schedule->command(\'queue:work --queue=seo-seeds-evaluation --stop-when-empty\')
                ->dailyAt(\'00:00\')
                ->appendOutputTo(storage_path(\'logs/seo-seeds-evaluation.log\'));
    }
');
        
        $this->newLine();
        $this->info('Para más información sobre cómo personalizar la programación, consulte:');
        $this->line('https://laravel.com/docs/scheduling');
        
        return 0;
    }
}
