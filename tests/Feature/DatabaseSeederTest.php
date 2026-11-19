<?php

use App\Models\Article;
use App\Models\User;
use Database\Seeders\ArticleSamples;
use Database\Seeders\AuthorSamples;

beforeEach(function () {
    config(['scout.driver' => 'collection']);
    Article::disableSearchSyncing();
});

it('gives every author a different number of sample articles', function () {
    $this->seed();

    $users = User::query()->orderBy('name')->get();
    $samples = collect(ArticleSamples::all());
    $authors = collect(AuthorSamples::all());
    $counts = Article::query()
        ->selectRaw('count(*) as articles_count')
        ->groupBy('user_id')
        ->pluck('articles_count')
        ->map(fn (mixed $count): int => (int) $count);

    expect($users->pluck('name')->all())->toBe($authors->pluck('name')->sort()->values()->all())
        ->and($counts)->toHaveCount($users->count())
        ->and($counts->min())->toBeGreaterThanOrEqual(1)
        ->and($counts->unique())->not->toHaveCount(1)
        ->and($counts->sum())->toBe($samples->count())
        ->and(Article::query()->where('published', false)->exists())->toBeTrue()
        ->and(Article::query()->pluck('title')->diff($samples->pluck('title'))->all())->toBe([]);
});
