<?php

namespace Database\Seeders;

class ArticleSamples
{
    /**
     * Readable article copy. The seeder assigns these to authors.
     *
     * @return array<int, array{title: string, teaser: string, published: bool}>
     */
    public static function all(): array
    {
        return [
            [
                'title' => 'How to plan a weekend walk through a city market',
                'teaser' => 'Start with one neighborhood, eat before the stalls get crowded, and leave time to sit down with what you bought.',
                'published' => true,
            ],
            [
                'title' => 'A small-kitchen guide to cooking dried beans',
                'teaser' => 'Soak them overnight, salt the water, and keep the simmer low so the skins stay intact.',
                'published' => true,
            ],
            [
                'title' => 'What to check before you rent your first apartment',
                'teaser' => 'Look at water pressure, phone signal, and how the street sounds after ten at night. The photos will not tell you any of that.',
                'published' => true,
            ],
            [
                'title' => 'Notes from the Saturday market',
                'teaser' => 'An unfinished list of vendors, prices, and the stall that sold out of peaches before noon.',
                'published' => false,
            ],
            [
                'title' => 'A beginner route along the coastal trail',
                'teaser' => 'The first two miles are flat, the view opens after the wooden stairs, and the turnaround point is the bench above the cove.',
                'published' => true,
            ],
            [
                'title' => 'How to pack for a one-night hike',
                'teaser' => 'Bring a warm layer, a way to make breakfast, and less food than you think. The extra sweater matters more than a second pair of shoes.',
                'published' => true,
            ],
            [
                'title' => 'How to read the weather before you leave camp',
                'teaser' => 'A high thin cloud in the morning can turn into rain by lunch. If the wind changes and the birds go quiet, start walking back.',
                'published' => true,
            ],
            [
                'title' => 'Trail repairs after the spring rain',
                'teaser' => 'A draft about washed-out steps and the tools the volunteer crew still needs.',
                'published' => false,
            ],
            [
                'title' => 'Three novels worth reading this month',
                'teaser' => 'One is about a family bakery, one follows a mapmaker at sea, and the third is a short story collection you can finish on the train.',
                'published' => true,
            ],
            [
                'title' => 'How to take notes you will actually reread',
                'teaser' => 'Write the question the page answered, not a copy of the paragraph. A week later, the question is what helps you find the idea again.',
                'published' => true,
            ],
            [
                'title' => 'Building a reading habit after work',
                'teaser' => 'Leave the book in the chair you already sit in. Ten pages on a weeknight is enough to keep the story moving.',
                'published' => true,
            ],
            [
                'title' => 'Questions for next month’s book club',
                'teaser' => 'A private draft of discussion prompts, including the ending we agreed not to spoil.',
                'published' => false,
            ],
            [
                'title' => 'What happens when you search for a word',
                'teaser' => 'The index looks for the word, then for close spellings, and then ranks the pages that use it in the title above pages that only mention it once.',
                'published' => true,
            ],
            [
                'title' => 'How to name files so you can find them later',
                'teaser' => 'Start with the date, then the project, then a plain description. “Notes final final” is not a description.',
                'published' => true,
            ],
            [
                'title' => 'A plain explanation of database backups',
                'teaser' => 'A backup is a copy you can restore after a mistake. If you have never tried restoring it, you do not yet know that the copy works.',
                'published' => true,
            ],
            [
                'title' => 'Outline for the search workshop',
                'teaser' => 'An unpublished running order for the afternoon session, from indexing to filters.',
                'published' => false,
            ],
        ];
    }
}
