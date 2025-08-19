<?php

namespace Mgcodeur\LaravelTranslationLoader\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Mgcodeur\LaravelTranslationLoader\Traits\FlushesCache;

class Language extends Model
{
    use FlushesCache;

    protected $fillable = [
        'name',
        'code',
        'flag',
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(Translation::class);
    }
}
