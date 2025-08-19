<?php

namespace Mgcodeur\LaravelTranslationLoader\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Mgcodeur\LaravelTranslationLoader\Traits\FlushesCache;

/**
 * @property int $id
 * @property int $language_id
 * @property string $key
 * @property string|null $value
 * @property-read Language|null  $language
 */
class Translation extends Model
{
    use FlushesCache;

    protected $fillable = [
        'key',
        'value',
        'language_id',
    ];

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
