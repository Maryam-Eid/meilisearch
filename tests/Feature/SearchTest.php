<?php

use App\Models\Article;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

beforeEach(function () {
    config(['scout.driver' => 'collection']);
    Article::disableSearchSyncing();
});

it('shows a published article that matches the query', function () {
    $user = User::factory()->create();

    Article::factory()->published()->for($user)->create([
        'title' => 'Glacier field notes',
        'teaser' => 'Notes from the ice.',
    ]);

    Article::factory()->for($user)->create([
        'title' => 'Glacier draft',
        'published' => false,
    ]);

    $this->actingAs($user)
        ->get('/search?q=Glacier')
        ->assertOk()
        ->assertSee('Glacier field notes')
        ->assertDontSee('Glacier draft');
});

it('limits results to the selected author', function () {
    $author = User::factory()->create();
    $other = User::factory()->create();

    Article::factory()->published()->for($author)->create([
        'title' => 'Cedar trail guide',
    ]);

    Article::factory()->published()->for($other)->create([
        'title' => 'Cedar town map',
    ]);

    $this->actingAs($author)
        ->get('/search?q=Cedar&user_id='.$author->id)
        ->assertOk()
        ->assertSee('Cedar trail guide')
        ->assertDontSee('Cedar town map');
});

it('limits results to the selected period', function () {
    $this->freezeTime();

    $user = User::factory()->create();

    Article::factory()->published()->for($user)->create([
        'title' => 'Recent glacier report',
        'created_at' => now()->subDays(2),
    ]);

    Article::factory()->published()->for($user)->create([
        'title' => 'Old glacier report',
        'created_at' => now()->subMonths(2),
    ]);

    $this->actingAs($user)
        ->get('/search?q=glacier&period=week')
        ->assertOk()
        ->assertSee('Recent glacier report')
        ->assertDontSee('Old glacier report');
});

it('orders results from newest to oldest', function () {
    $this->freezeTime();

    $user = User::factory()->create();

    Article::factory()->published()->for($user)->create([
        'title' => 'Older cedar note',
        'created_at' => now()->subDays(3),
    ]);

    Article::factory()->published()->for($user)->create([
        'title' => 'Newer cedar note',
        'created_at' => now()->subDay(),
    ]);

    $this->actingAs($user)
        ->get('/search?q=cedar&sort=newest')
        ->assertOk()
        ->assertSeeInOrder(['Newer cedar note', 'Older cedar note']);
});

it('returns only the results markup for an instant search request', function () {
    $user = User::factory()->create();

    Article::factory()->published()->for($user)->create([
        'title' => 'Instant glacier result',
    ]);

    $this->get('/search?q=glacier', ['X-Search-Fragment' => 'results'])
        ->assertOk()
        ->assertSee('Instant glacier result')
        ->assertDontSee('name="q"', false);
});

it('escapes article text when the search engine does not highlight it', function () {
    $user = User::factory()->create();

    Article::factory()->published()->for($user)->create([
        'title' => 'Visible <script>alert(1)</script> marker',
        'teaser' => 'Plain teaser',
    ]);

    $this->actingAs($user)
        ->get('/search?q=Visible')
        ->assertOk()
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
});

it('renders highlighted terms from the search engine', function () {
    $article = Article::factory()->published()->for(User::factory())->create([
        'title' => 'Hello glacier',
        'teaser' => 'A short teaser',
    ]);

    $article->withScoutMetadata('_formatted', [
        'title' => 'Hello <mark>glacier</mark>',
        'teaser' => 'A short teaser',
    ]);

    $results = new LengthAwarePaginator(
        collect([$article->load('user')]),
        1,
        5,
        1,
        ['path' => url('/search')],
    );

    $this->view('search.results', ['results' => $results])
        ->assertSee('<mark>glacier</mark>', false);
});

it('shows how many matching articles each author has', function () {
    $author = User::factory()->create(['name' => 'Ada Lovelace']);
    $other = User::factory()->create(['name' => 'Grace Hopper']);

    Article::factory()->published()->count(2)->for($author)->create([
        'title' => 'Glacier field notes',
    ]);

    Article::factory()->published()->for($other)->create([
        'title' => 'Desert map',
    ]);

    $this->actingAs($author)
        ->get('/search?q=Glacier')
        ->assertOk()
        ->assertSee('Ada Lovelace (2)')
        ->assertSee('Grace Hopper (0)')
        ->assertSee('Glacier field notes')
        ->assertDontSee('Desert map');
});

it('counts only articles inside the selected period', function () {
    $this->freezeTime();

    $author = User::factory()->create(['name' => 'Ada Lovelace']);

    Article::factory()->published()->for($author)->create([
        'title' => 'Recent glacier report',
        'created_at' => now()->subDays(2),
    ]);

    Article::factory()->published()->for($author)->create([
        'title' => 'Old glacier report',
        'created_at' => now()->subMonths(2),
    ]);

    $this->actingAs($author)
        ->get('/search?q=glacier&period=week')
        ->assertOk()
        ->assertSee('Ada Lovelace (1)');
});

it('sends updated author counts with an instant search', function () {
    $author = User::factory()->create(['name' => 'Ada Lovelace']);

    Article::factory()->published()->for($author)->create([
        'title' => 'Instant glacier result',
    ]);

    $this->get('/search?q=glacier', ['X-Search-Fragment' => 'results'])
        ->assertOk()
        ->assertSee('Ada Lovelace (1)')
        ->assertSee('Instant glacier result')
        ->assertDontSee('name="q"', false);
});

it('shows a control for clearing the author, date, and sort filters', function () {
    $this->get('/search?q=glacier&period=week&sort=newest')
        ->assertOk()
        ->assertSee('Clear all filters');
});

it('rejects an unknown date period', function () {
    $this->from('/search')
        ->get('/search?q=glacier&period=decade')
        ->assertRedirect('/search')
        ->assertInvalid(['period' => 'The selected period is invalid.']);
});

it('rejects an unknown sort', function () {
    $this->from('/search')
        ->get('/search?q=glacier&sort=popular')
        ->assertRedirect('/search')
        ->assertInvalid(['sort' => 'The selected sort is invalid.']);
});

it('suggests a close word when nothing matches', function () {
    $user = User::factory()->create();

    Article::factory()->published()->for($user)->create([
        'title' => 'Glacier field notes',
        'teaser' => 'Notes from the ice.',
    ]);

    $this->actingAs($user)
        ->get('/search?q=Glaciar')
        ->assertOk()
        ->assertSee('Did you mean')
        ->assertSee('Glacier')
        ->assertDontSee('field notes');
});

it('suggests the corrected phrase for a mistyped search', function () {
    $user = User::factory()->create();

    Article::factory()->published()->for($user)->create([
        'title' => 'A small-kitchen guide to cooking dried beans',
    ]);

    $this->actingAs($user)
        ->get('/search?q=dried+beens', ['X-Search-Fragment' => 'results'])
        ->assertOk()
        ->assertSee('dried beans')
        ->assertDontSee('small-kitchen')
        ->assertDontSee('name="q"', false);
});

it('does not suggest a correction when the query matches', function () {
    $user = User::factory()->create();

    Article::factory()->published()->for($user)->create([
        'title' => 'Glacier field notes',
    ]);

    $this->actingAs($user)
        ->get('/search?q=Glacier')
        ->assertOk()
        ->assertSee('Glacier field notes')
        ->assertDontSee('Did you mean');
});

it('does not suggest a word that only appears in a draft', function () {
    $user = User::factory()->create();

    Article::factory()->for($user)->create([
        'title' => 'Market draft',
        'published' => false,
    ]);

    $this->actingAs($user)
        ->get('/search?q=markit')
        ->assertOk()
        ->assertSee('No results found')
        ->assertDontSee('Did you mean');
});

it('does not suggest a word outside the selected period', function () {
    $this->freezeTime();

    $user = User::factory()->create();

    Article::factory()->published()->for($user)->create([
        'title' => 'Cooking dried beans',
        'created_at' => now()->subMonths(2),
    ]);

    $this->actingAs($user)
        ->get('/search?q=beens&period=week')
        ->assertOk()
        ->assertSee('No results found')
        ->assertDontSee('Did you mean');
});

it('does not suggest another author word when an author is selected', function () {
    $author = User::factory()->create();
    $other = User::factory()->create();

    Article::factory()->published()->for($other)->create([
        'title' => 'Cooking dried beans',
    ]);

    $this->actingAs($author)
        ->get('/search?q=beens&user_id='.$author->id)
        ->assertOk()
        ->assertSee('No results found')
        ->assertDontSee('Did you mean');
});

it('rejects an unknown author', function () {
    $this->from('/search')
        ->get('/search?q=glacier&user_id=999999')
        ->assertRedirect('/search')
        ->assertInvalid(['user_id' => 'The selected user id is invalid.']);
});
