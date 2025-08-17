<?php

namespace Mgcodeur\LaravelTranslationLoader\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Translation extends Model
{
    protected $fillable = [
        'key',
        'value',
        'language_id'
    ];

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
