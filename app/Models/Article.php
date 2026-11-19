<?php

namespace App\Models;

use Database\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Scout\Searchable;

class Article extends Model
{
    /** @use HasFactory<ArticleFactory> */
    use HasFactory, Searchable;

    public function shouldBeSearchable(): bool
    {
        return $this->published == true;
    }

    /**
     * @return array{id: int, title: string, teaser: string, user_id: int, created_at: string}
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => (int) $this->id,
            'title' => $this->title,
            'teaser' => $this->teaser,
            'user_id' => (int) $this->user_id,
            'created_at' => $this->created_at?->toDateTimeString() ?? '',
        ];
    }

    public function highlighted(string $attribute): string
    {
        $formatted = $this->scoutMetadata()['_formatted'][$attribute] ?? null;

        if (is_string($formatted) && $formatted !== '') {
            return $formatted;
        }

        return e((string) $this->getAttribute($attribute));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function makeAllSearchableUsing(Builder $query): Builder
    {
        return $query->where('published', true);
    }
}
