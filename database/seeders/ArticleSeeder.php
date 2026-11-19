<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class ArticleSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed authors and a random number of articles for each one.
     */
    public function run(): void
    {
        $articles = collect(ArticleSamples::all())->shuffle();
        $authors = collect(AuthorSamples::all())
            ->map(fn (array $author): User => User::factory()->create($author));

        $this->counts($authors->count(), $articles->count())
            ->each(function (int $count) use ($authors, $articles): void {
                $author = $authors->shift();

                $articles->splice(0, $count)->each(function (array $article) use ($author): void {
                    $writtenAt = now()->subDays(random_int(1, 120));

                    Article::factory()->create([
                        'user_id' => $author->id,
                        'title' => $article['title'],
                        'teaser' => $article['teaser'],
                        'published' => $article['published'],
                        'created_at' => $writtenAt,
                        'updated_at' => $writtenAt,
                    ]);
                });
            });
    }

    /**
     * Give every author at least one article, then hand out the rest at random.
     *
     * @return Collection<int, int>
     */
    private function counts(int $authors, int $articles): Collection
    {
        $counts = array_fill(0, $authors, 1);

        for ($extra = $articles - $authors; $extra > 0; $extra--) {
            $counts[random_int(0, $authors - 1)]++;
        }

        return collect($counts);
    }
}
