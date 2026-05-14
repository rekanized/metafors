<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['metafor', 'explained_for'])]
class Metafor extends Model
{
    public function ratings(): HasMany
    {
        return $this->hasMany(MetaforRating::class);
    }

    public static function normalizeMetafor(string $value): string
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", trim($value));

        return implode("\n", array_map(
            static fn (string $line): string => (string) Str::of($line)->squish(),
            explode("\n", $normalized),
        ));
    }

    public static function normalizeExplainedFor(string $value): string
    {
        return (string) Str::of($value)->squish()->trim();
    }

    public static function signature(string $metafor, string $explainedFor): string
    {
        return Str::lower(
            static::normalizeExplainedFor($explainedFor).'|'.static::normalizeMetafor($metafor)
        );
    }

    public function scopeSearch(Builder $query, string $term): void
    {
        $pattern = '%'.str_replace(' ', '%', trim($term)).'%';

        $query->where(function (Builder $query) use ($pattern): void {
            $query
                ->where('metafor', 'like', $pattern)
                ->orWhere('explained_for', 'like', $pattern);
        });
    }
}