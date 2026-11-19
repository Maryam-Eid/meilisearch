<div class="space-y-4">
    @if ($results)
        @if ($results->count())
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Found {{ $results->total() }} results
            </p>

            <div class="flex flex-col gap-3">
                @foreach ($results as $result)
                    <article class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{!! $result->highlighted('title') !!}</h3>
                        <p class="mt-2 text-gray-700 dark:text-gray-300">{!! $result->highlighted('teaser') !!}</p>
                        <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                            {{ $result->user->name }} · {{ $result->created_at->toFormattedDateString() }}
                        </p>
                    </article>
                @endforeach
            </div>

            <div class="pt-2">
                {{ $results->links() }}
            </div>
        @else
            @if (! empty($correction))
                <p class="text-center text-sm text-gray-500 dark:text-gray-400">
                    Did you mean
                    <button
                        type="button"
                        class="font-medium text-gray-900 underline hover:text-gray-700 dark:text-gray-100 dark:hover:text-gray-300"
                        data-correction="{{ $correction }}"
                    >{{ $correction }}</button>?
                </p>
            @else
                <p class="text-center text-sm text-gray-500 dark:text-gray-400">No results found</p>
            @endif
        @endif
    @else
        <p class="text-center text-sm text-gray-500 dark:text-gray-400">
            Type a word to search published articles.
        </p>
    @endif
</div>
