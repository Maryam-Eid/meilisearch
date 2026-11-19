<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\User;
use App\Search\QueryCorrection;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Laravel\Scout\Builder;

class SearchController extends Controller
{
    public function __invoke(Request $request, QueryCorrection $corrections): View|string
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'period' => ['nullable', Rule::in(['week', 'month', 'year'])],
            'sort' => ['nullable', Rule::in(['newest', 'oldest'])],
        ]);

        $results = null;
        $suggestions = [];
        $correction = null;

        if (filled($filters['q'] ?? null)) {
            $results = $this->articles($filters)
                ->paginate(5)
                ->withQueryString();

            $suggestions = $results->getCollection()
                ->pluck('title')
                ->unique()
                ->values()
                ->all();

            if ($results->total() === 0) {
                $correction = $corrections->suggest(
                    $filters['q'],
                    $this->vocabulary(
                        isset($filters['user_id']) ? (int) $filters['user_id'] : null,
                        $filters['period'] ?? null,
                    ),
                );
            }
        }

        $viewData = [
            'results' => $results,
            'suggestions' => $suggestions,
            'correction' => $correction,
            'users' => User::query()->orderBy('name')->get(),
            'authorCounts' => $this->authorCounts($filters['q'] ?? null, $filters['period'] ?? null),
        ];

        if ($request->header('X-Search-Fragment') === 'results') {
            return view('search.fragment', $viewData);
        }

        return view('search', $viewData);
    }

    /**
     * @param  array{q?: string, user_id?: int, period?: string, sort?: string}  $filters
     * @return Builder<Article>
     */
    private function articles(array $filters): Builder
    {
        $search = Article::search($filters['q'])
            ->options([
                'attributesToHighlight' => ['title', 'teaser'],
                'highlightPreTag' => '<mark class="rounded-sm bg-emerald-200 px-0.5 text-inherit dark:bg-emerald-600">',
                'highlightPostTag' => '</mark>',
            ])
            ->query(fn ($query) => $query->with('user'));

        if (isset($filters['user_id'])) {
            $search->where('user_id', $filters['user_id']);
        }

        if (isset($filters['period'])) {
            $search->where('created_at', '>=', $this->periodStart($filters['period'])->toDateTimeString());
        }

        if (isset($filters['sort'])) {
            $search->orderBy(
                'created_at',
                $filters['sort'] === 'newest' ? 'desc' : 'asc',
            );
        }

        return $search;
    }

    /**
     * @return array<int, int>
     */
    private function authorCounts(?string $query, ?string $period): array
    {
        $search = Article::search($query ?? '');

        if ($period !== null) {
            $search->where('created_at', '>=', $this->periodStart($period)->toDateTimeString());
        }

        $raw = $search->options(['facets' => ['user_id']])->take(1)->raw();

        if (is_array($raw) && array_key_exists('facetDistribution', $raw)) {
            return collect($raw['facetDistribution']['user_id'] ?? [])
                ->mapWithKeys(fn (int|string $count, int|string $id): array => [(int) $id => (int) $count])
                ->all();
        }

        return $this->authorCountsFromDatabase($query, $period);
    }

    /**
     * @return array<int, int>
     */
    private function authorCountsFromDatabase(?string $query, ?string $period): array
    {
        return Article::query()
            ->where('published', true)
            ->when($period !== null, function (EloquentBuilder $articles) use ($period): void {
                $articles->where('created_at', '>=', $this->periodStart($period));
            })
            ->when(filled($query), function (EloquentBuilder $articles) use ($query): void {
                $like = '%'.addcslashes($query, '%_\\').'%';

                $articles->where(function (EloquentBuilder $articles) use ($like): void {
                    $articles->where('title', 'like', $like)
                        ->orWhere('teaser', 'like', $like);
                });
            })
            ->pluck('user_id')
            ->countBy()
            ->map(fn (int $count): int => $count)
            ->all();
    }

    /**
     * @return list<string>
     */
    private function vocabulary(?int $userId, ?string $period): array
    {
        return Article::query()
            ->where('published', true)
            ->when($userId !== null, function (EloquentBuilder $articles) use ($userId): void {
                $articles->where('user_id', $userId);
            })
            ->when($period !== null, function (EloquentBuilder $articles) use ($period): void {
                $articles->where('created_at', '>=', $this->periodStart($period));
            })
            ->get(['title', 'teaser'])
            ->flatMap(fn (Article $article): array => [$article->title, $article->teaser])
            ->all();
    }

    private function periodStart(string $period): Carbon
    {
        return match ($period) {
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'year' => now()->subYear(),
        };
    }
}
