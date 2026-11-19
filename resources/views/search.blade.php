<x-app-layout>
    <div class="py-10">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-gray-800">
                <div class="p-6 text-gray-900 dark:text-gray-100 sm:p-8">
                    <div
                        x-data="{
                            loading: false,
                            requestId: 0,
                            suggestions: [],
                            openSuggestions: false,
                            recent: [],
                            filtersActive: false,
                            init() {
                                this.recent = this.savedSearches();
                                this.readSuggestions(this.$refs.results);
                                this.syncFilters();
                            },
                            savedSearches() {
                                try {
                                    const saved = JSON.parse(localStorage.getItem('article-searches') || '[]');

                                    return Array.isArray(saved) ? saved.filter((item) => typeof item === 'string' && item !== '') : [];
                                } catch (error) {
                                    return [];
                                }
                            },
                            remember(query) {
                                const value = query.trim();

                                if (value.length < 2) {
                                    return;
                                }

                                this.recent = [value, ...this.recent.filter((item) => item !== value && ! value.startsWith(item))].slice(0, 5);
                                localStorage.setItem('article-searches', JSON.stringify(this.recent));
                            },
                            clearRecent() {
                                this.recent = [];
                                localStorage.removeItem('article-searches');
                            },
                            syncFilters() {
                                this.filtersActive = [this.$refs.author, this.$refs.period, this.$refs.sort]
                                    .some((select) => select.value !== '');
                            },
                            clearFilters() {
                                this.$refs.author.value = '';
                                this.$refs.period.value = '';
                                this.$refs.sort.value = '';
                                this.filtersActive = false;
                                this.submit();
                            },
                            readSuggestions(container) {
                                const data = container.querySelector('#suggestions-data');
                                this.suggestions = data ? JSON.parse(data.textContent) : [];
                                this.openSuggestions = this.suggestions.length > 0 && document.activeElement === this.$refs.query;
                            },
                            applySuggestion(title) {
                                this.$refs.query.value = title;
                                this.openSuggestions = false;
                                this.submit();
                            },
                            applyRecent(query) {
                                this.$refs.query.value = query;
                                this.openSuggestions = false;
                                this.submit();
                            },
                            applyCorrection(event) {
                                const button = event.target.closest('[data-correction]');

                                if (! button) {
                                    return;
                                }

                                this.$refs.query.value = button.dataset.correction;
                                this.openSuggestions = false;
                                this.submit();
                            },
                            async submit() {
                                const requestId = ++this.requestId;
                                this.loading = true;
                                this.syncFilters();

                                const url = new URL(this.$refs.form.action);
                                url.search = new URLSearchParams(new FormData(this.$refs.form)).toString();
                                window.history.replaceState({}, '', url);

                                try {
                                    const response = await fetch(url, {
                                        headers: {
                                            'X-Search-Fragment': 'results',
                                            'Accept': 'text/html',
                                        },
                                    });

                                    if (requestId !== this.requestId) {
                                        return;
                                    }

                                    if (response.ok) {
                                        const html = await response.text();
                                        const fragment = new DOMParser().parseFromString(html, 'text/html');
                                        const options = fragment.querySelector('#author-options');

                                        if (options) {
                                            const selected = this.$refs.author.value;
                                            this.$refs.author.innerHTML = options.innerHTML;
                                            this.$refs.author.value = selected;
                                        }

                                        this.$refs.results.innerHTML = html;
                                        this.readSuggestions(this.$refs.results);
                                        this.remember(this.$refs.query.value);
                                    }
                                } finally {
                                    if (requestId === this.requestId) {
                                        this.loading = false;
                                    }
                                }
                            },
                        }"
                    >
                        <div class="flex flex-col items-center gap-1 text-center">
                            <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                                {{ __('Search') }}
                            </h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Find published articles by title or teaser.
                            </p>
                        </div>

                        <form
                            x-ref="form"
                            action="{{ route('search') }}"
                            method="get"
                            class="mt-6 flex flex-col gap-4"
                            @submit.prevent="submit"
                        >
                            <div class="grid grid-cols-1 items-center gap-3 sm:grid-cols-[minmax(0,1fr)_auto] sm:gap-x-3 sm:gap-y-1">
                                <x-input-label for="q" value="Query" class="sm:col-start-1" />

                                <div class="relative sm:col-start-1 sm:row-start-2" @click.outside="openSuggestions = false">
                                    <span class="pointer-events-none absolute inset-y-0 start-3 flex items-center text-gray-400">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="11" cy="11" r="7" />
                                            <path d="M20 20l-3.5-3.5" stroke-linecap="round" />
                                        </svg>
                                    </span>

                                    <x-text-input
                                        id="q"
                                        name="q"
                                        type="search"
                                        placeholder="Search articles..."
                                        class="block w-full ps-10"
                                        value="{{ request('q') }}"
                                        x-ref="query"
                                        autocomplete="off"
                                        @input.debounce.300ms="submit"
                                        @focus="openSuggestions = suggestions.length > 0"
                                        @click="openSuggestions = suggestions.length > 0"
                                        @keydown.escape="openSuggestions = false"
                                    />

                                    <ul
                                        x-show="openSuggestions && suggestions.length"
                                        x-cloak
                                        class="absolute z-20 mt-1 w-full overflow-hidden rounded-md border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-900"
                                    >
                                        <template x-for="suggestion in suggestions" :key="suggestion">
                                            <li>
                                                <button
                                                    type="button"
                                                    class="block w-full px-3 py-2 text-start text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800"
                                                    @click="applySuggestion(suggestion)"
                                                    x-text="suggestion"
                                                ></button>
                                            </li>
                                        </template>
                                    </ul>
                                </div>

                                <x-primary-button class="justify-self-center px-8 sm:col-start-2 sm:row-start-2">Search</x-primary-button>
                            </div>

                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                <div class="flex flex-col gap-1">
                                    <x-input-label for="user_id" value="Author" />

                                    <x-select id="user_id" name="user_id" class="block w-full" x-ref="author" @change="submit">
                                        @include('search.author-options')
                                    </x-select>
                                </div>

                                <div class="flex flex-col gap-1">
                                    <x-input-label for="period" value="Date" />

                                    <x-select id="period" name="period" class="block w-full" x-ref="period" @change="submit">
                                        <option value="">Any time</option>
                                        <option value="week" @selected(request('period') === 'week')>Past week</option>
                                        <option value="month" @selected(request('period') === 'month')>Past month</option>
                                        <option value="year" @selected(request('period') === 'year')>Past year</option>
                                    </x-select>
                                </div>

                                <div class="flex flex-col gap-1">
                                    <x-input-label for="sort" value="Sort" />

                                    <x-select id="sort" name="sort" class="block w-full" x-ref="sort" @change="submit">
                                        <option value="">Relevance</option>
                                        <option value="newest" @selected(request('sort') === 'newest')>Newest</option>
                                        <option value="oldest" @selected(request('sort') === 'oldest')>Oldest</option>
                                    </x-select>
                                </div>
                            </div>

                            <div class="flex justify-end" x-show="filtersActive" x-cloak>
                                <button
                                    type="button"
                                    class="text-sm text-gray-500 underline hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                                    @click="clearFilters"
                                >
                                    Clear all filters
                                </button>
                            </div>
                        </form>

                        <div x-show="recent.length" x-cloak class="mt-4 flex flex-wrap items-center gap-2">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Recent</span>

                            <template x-for="query in recent" :key="query">
                                <button
                                    type="button"
                                    class="rounded-full border border-gray-300 px-3 py-1 text-sm text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
                                    @click="applyRecent(query)"
                                    x-text="query"
                                ></button>
                            </template>

                            <button
                                type="button"
                                class="text-sm text-gray-500 underline hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                                @click="clearRecent"
                            >
                                Clear
                            </button>
                        </div>

                        <p x-show="loading" x-cloak class="mt-6 text-center text-sm text-gray-500 dark:text-gray-400">
                            Searching…
                        </p>

                        <div x-ref="results" class="mt-8 border-t border-gray-100 pt-6 dark:border-gray-700" @click="applyCorrection($event)">
                            @include('search.fragment')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
