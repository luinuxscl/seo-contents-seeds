<?php

namespace Luinuxscl\SeoContentSeeds\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;

class SeoContentSeed extends Model
{
    /**
     * La tabla asociada con el modelo.
     *
     * @var string
     */
    protected $table = 'seo_content_seeds';

    /**
     * Los atributos que son asignables en masa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'source_type',
        'source_identifier',
        'topic_keyword',
        'title_suggestion',
        'details',
        'relevance_score',
        'status',
        'region',
        'language',
        'fetched_at',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'details' => 'array',
        'relevance_score' => 'decimal:2',
        'fetched_at' => 'datetime',
    ];

    /**
     * Los estados posibles de una seed.
     */
    const STATUS_PENDING_EVALUATION = 'pending_evaluation';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_EVALUATION_FAILED = 'evaluation_failed';
    const STATUS_PROCESSED = 'processed';

    /**
     * Los tipos de fuentes soportados.
     */
    const SOURCE_GOOGLE_TRENDS = 'google_trends';
    const SOURCE_RSS_FEED = 'rss_feed';
    
    /**
     * Boot the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();
        
        // Establecer fetched_at automáticamente al crear
        static::creating(function ($seed) {
            if (empty($seed->fetched_at)) {
                $seed->fetched_at = now();
            }
        });
    }

    /**
     * Scope para filtrar por tipo de fuente.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $sourceType
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfSourceType(Builder $query, string $sourceType): Builder
    {
        return $query->where('source_type', $sourceType);
    }

    /**
     * Scope para filtrar por estado.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string|array  $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithStatus(Builder $query, $status): Builder
    {
        return is_array($status) 
            ? $query->whereIn('status', $status)
            : $query->where('status', $status);
    }

    /**
     * Scope para filtrar por región.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $region
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInRegion(Builder $query, string $region): Builder
    {
        return $query->where('region', $region);
    }

    /**
     * Scope para filtrar por idioma.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $language
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInLanguage(Builder $query, string $language): Builder
    {
        return $query->where('language', $language);
    }

    /**
     * Scope para filtrar por pendientes de evaluación.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePendingEvaluation(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING_EVALUATION);
    }

    /**
     * Scope para filtrar por aprobadas.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope para filtrar por rechazadas.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    /**
     * Scope para filtrar por fecha de obtención.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Support\Carbon|string  $date
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFetchedOn(Builder $query, $date): Builder
    {
        if (is_string($date)) {
            $date = Carbon::parse($date);
        }

        return $query->whereDate('fetched_at', $date);
    }

    /**
     * Scope para obtener semillas recientes.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $days
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecentlyFetched(Builder $query, int $days = 7): Builder
    {
        return $query->where('fetched_at', '>=', now()->subDays($days));
    }

    /**
     * Scope para filtrar por puntuación de relevancia mínima.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  float  $minScore
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithMinRelevanceScore(Builder $query, float $minScore): Builder
    {
        return $query->where('relevance_score', '>=', $minScore);
    }

    /**
     * Actualiza el estado a aprobado y opcionalmente establece una puntuación de relevancia.
     *
     * @param  float|null  $score
     * @return bool
     */
    public function markAsApproved(?float $score = null): bool
    {
        $this->status = self::STATUS_APPROVED;
        
        if ($score !== null) {
            $this->relevance_score = $score;
        }
        
        return $this->save();
    }

    /**
     * Actualiza el estado a rechazado y opcionalmente establece una puntuación de relevancia.
     *
     * @param  float|null  $score
     * @return bool
     */
    public function markAsRejected(?float $score = null): bool
    {
        $this->status = self::STATUS_REJECTED;
        
        if ($score !== null) {
            $this->relevance_score = $score;
        }
        
        return $this->save();
    }

    /**
     * Actualiza el estado a procesado.
     *
     * @return bool
     */
    public function markAsProcessed(): bool
    {
        $this->status = self::STATUS_PROCESSED;
        return $this->save();
    }

    /**
     * Actualiza el estado a fallo en la evaluación.
     *
     * @return bool
     */
    public function markAsEvaluationFailed(): bool
    {
        $this->status = self::STATUS_EVALUATION_FAILED;
        return $this->save();
    }

    /**
     * Obtiene un valor específico de los detalles JSON.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function getDetailValue(string $key, $default = null)
    {
        return data_get($this->details, $key, $default);
    }

    /**
     * Establece un valor específico en los detalles JSON.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return $this
     */
    public function setDetailValue(string $key, $value)
    {
        $details = $this->details ?: [];
        data_set($details, $key, $value);
        $this->details = $details;
        
        return $this;
    }

    /**
     * Determina si la seed está pendiente de evaluación.
     *
     * @return bool
     */
    public function isPendingEvaluation(): bool
    {
        return $this->status === self::STATUS_PENDING_EVALUATION;
    }

    /**
     * Determina si la seed ha sido aprobada.
     *
     * @return bool
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Determina si la seed ha sido rechazada.
     *
     * @return bool
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Determina si la seed ha sido procesada.
     *
     * @return bool
     */
    public function isProcessed(): bool
    {
        return $this->status === self::STATUS_PROCESSED;
    }

    /**
     * Determina si la evaluación de la seed ha fallado.
     *
     * @return bool
     */
    public function hasEvaluationFailed(): bool
    {
        return $this->status === self::STATUS_EVALUATION_FAILED;
    }
}
