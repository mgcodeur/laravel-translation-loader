<?php

namespace Mgcodeur\LaravelTranslationLoader\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Mgcodeur\LaravelTranslationLoader\Traits\FlushesCache;

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
