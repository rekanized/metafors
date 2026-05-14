<section class="page-shell">
    <div class="page-glow page-glow-one"></div>
    <div class="page-glow page-glow-two"></div>

    <div class="app-frame">
        <header class="hero-card panel">
            <div class="hero-content">
                <img class="hero-logo" src="{{ asset('images/metafors-logo.svg') }}" alt="Metafors logo">
                <p class="eyebrow">Shared language for hard software ideas</p>
                <h1>Collect metaphors that make systems easier to explain.</h1>
                <p class="hero-copy">
                    Anyone can add a metaphor and tag the software concept it explains. Search updates live, and the
                    front page doubles as the public archive.
                </p>
            </div>

            <div class="hero-stats">
                <article>
                    <span>Total metaphors</span>
                    <strong>{{ $totalMetafors }}</strong>
                </article>
                <article>
                    <span>Solution groups</span>
                    <strong>{{ $totalGroups }}</strong>
                </article>
                <article>
                    <span>Live results</span>
                    <strong>{{ $filteredCount }}</strong>
                </article>
            </div>
        </header>

        <div class="workspace-grid">
            <aside class="panel composer-panel">
                <div class="panel-heading composer-heading">
                    <div>
                        <p class="eyebrow">Add a new entry</p>
                        <h2>Make the glossary smarter</h2>
                    </div>
                    <p class="panel-copy composer-copy">The form is open to everyone. Exact duplicates are blocked and public posting is rate-limited.</p>
                </div>

                @if (session('status'))
                    <div class="notice notice-success">{{ session('status') }}</div>
                @endif

                <p class="composer-guidelines">
                    Keep each metaphor specific. Everyone can submit and rate entries. Editing and deleting are locked behind a moderation key.
                </p>

                <form wire:submit="save" class="entry-form">
                    <label class="field">
                        <span>Metafor</span>
                        <textarea
                            wire:model.blur="metafor"
                            rows="5"
                            maxlength="{{ \App\Livewire\MetaforBoard::MAX_METAFOR_LENGTH }}"
                            placeholder="Example: Event sourcing is a flight data recorder for business state."
                        ></textarea>
                        @error('metafor')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </label>

                    <label class="field">
                        <span>Explained for</span>
                        <input
                            wire:model.blur="explained_for"
                            type="text"
                            maxlength="120"
                            placeholder="Example: Event sourcing"
                        >
                        @error('explained_for')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </label>

                    <button type="submit" class="primary-button">Save metafor</button>
                </form>

                <section class="moderation-panel">
                    <div class="panel-heading compact moderation-heading">
                        <div>
                            <p class="eyebrow">Moderation tools</p>
                            <h2>{{ $moderationUnlocked ? 'Moderation is unlocked' : 'Unlock edit and delete' }}</h2>
                        </div>

                        @if ($moderationUnlocked)
                            <button type="button" wire:click="lockModeration" class="ghost-button">Lock moderation</button>
                        @endif
                    </div>

                    @if ($moderationKeyConfigured)
                        @if (! $moderationUnlocked)
                            <form wire:submit="unlockModeration" class="entry-form compact-form">
                                <label class="field">
                                    <span>Moderation key</span>
                                    <input wire:model.blur="moderationKey" type="password" placeholder="Enter the admin-only moderation key">
                                    @error('moderationKey')
                                        <small class="field-error">{{ $message }}</small>
                                    @enderror
                                </label>

                                <button type="submit" class="ghost-button">Unlock moderation</button>
                            </form>
                        @else
                            <p class="composer-guidelines">Card-level edit and delete controls are now active for this browser session.</p>
                        @endif
                    @else
                        <p class="composer-guidelines">Set <strong>METAFORS_ADMIN_KEY</strong> on the server to enable moderation unlock.</p>
                    @endif
                </section>
            </aside>

            <div class="content-column">
                <section class="panel filters-panel">
                    <div class="panel-heading compact">
                        <div>
                            <p class="eyebrow">Browse the archive</p>
                            <h2>Filter by keyword or software solution</h2>
                        </div>
                        <button type="button" wire:click="clearFilters" class="ghost-button">Clear filters</button>
                    </div>

                    <label class="search-field">
                        <span>Live search</span>
                        <input
                            wire:model.live.debounce.250ms="search"
                            type="search"
                            placeholder="Search metaphors or the software topic they explain"
                        >
                    </label>

                    <div class="filter-group">
                        <button
                            type="button"
                            wire:click="$set('explainedForFilter', '')"
                            class="filter-chip {{ $explainedForFilter === '' ? 'is-active' : '' }}"
                        >
                            All solutions
                        </button>

                        @foreach ($groups as $group)
                            <button
                                type="button"
                                wire:click="$set('explainedForFilter', '{{ addslashes($group) }}')"
                                class="filter-chip {{ $explainedForFilter === $group ? 'is-active' : '' }}"
                            >
                                {{ $group }}
                            </button>
                        @endforeach
                    </div>
                </section>

                @if ($groupedMetafors->isNotEmpty())
                    <div class="results-stack">
                        @foreach ($groupedMetafors as $explainedFor => $entries)
                            <section class="solution-group" wire:key="group-{{ md5($explainedFor) }}">
                                <header class="solution-group-head">
                                    <div>
                                        <p class="eyebrow">Explained for</p>
                                        <h3>{{ $explainedFor }}</h3>
                                    </div>
                                    <p class="solution-group-meta">Sorted by rating first, then by recent additions.</p>
                                </header>

                                <div class="results-grid">
                                    @foreach ($entries as $entry)
                                        <article class="metafor-card panel {{ $moderationUnlocked ? 'is-moderation' : 'has-floating-rating' }}" wire:key="metafor-{{ $entry->id }}">
                                            <header class="card-head">
                                                <div class="card-meta">
                                                    <p class="card-kicker">{{ $entry->explained_for }}</p>

                                                    @if ($moderationUnlocked)
                                                        <div class="rating-summary" aria-label="Average rating">
                                                            <span class="rating-summary-label">Rating</span>
                                                            <p class="rating-summary-value">
                                                                <strong>{{ number_format((float) ($entry->ratings_avg_rating ?? 0), 1) }}</strong>
                                                                <span>/ 5</span>
                                                            </p>
                                                            <p class="rating-summary-count">
                                                                {{ $entry->ratings_count }} {{ \Illuminate\Support\Str::plural('rating', $entry->ratings_count) }}
                                                            </p>
                                                        </div>
                                                    @endif
                                                </div>

                                                @if (! $moderationUnlocked)
                                                    <div class="rating-summary rating-summary-floating" aria-label="Average rating">
                                                        <span class="rating-summary-label">Rating</span>
                                                        <p class="rating-summary-value">
                                                            <strong>{{ number_format((float) ($entry->ratings_avg_rating ?? 0), 1) }}</strong>
                                                            <span>/ 5</span>
                                                        </p>
                                                        <p class="rating-summary-count">
                                                            {{ $entry->ratings_count }} {{ \Illuminate\Support\Str::plural('rating', $entry->ratings_count) }}
                                                        </p>
                                                    </div>
                                                @endif

                                                @if ($moderationUnlocked)
                                                    <div class="card-actions">
                                                        @if ($editingId === $entry->id)
                                                            <button type="button" wire:click="cancelEditing" class="chip-button">Cancel</button>
                                                        @else
                                                            <button type="button" wire:click="startEditing({{ $entry->id }})" class="chip-button">Edit</button>
                                                        @endif

                                                        @if ($confirmingDeleteId === $entry->id)
                                                            <button type="button" wire:click="deleteMetafor({{ $entry->id }})" class="danger-button">Confirm delete</button>
                                                        @else
                                                            <button type="button" wire:click="confirmDelete({{ $entry->id }})" class="chip-button danger-soft">Delete</button>
                                                        @endif
                                                    </div>
                                                @endif
                                            </header>

                                            @if ($editingId === $entry->id)
                                                <form wire:submit="saveEdit" class="entry-form compact-form">
                                                    <label class="field">
                                                        <span>Metafor</span>
                                                        <textarea wire:model.blur="editMetafor" rows="4" maxlength="{{ \App\Livewire\MetaforBoard::MAX_METAFOR_LENGTH }}"></textarea>
                                                        @error('editMetafor')
                                                            <small class="field-error">{{ $message }}</small>
                                                        @enderror
                                                    </label>

                                                    <label class="field">
                                                        <span>Explained for</span>
                                                        <input wire:model.blur="editExplainedFor" type="text" maxlength="120">
                                                        @error('editExplainedFor')
                                                            <small class="field-error">{{ $message }}</small>
                                                        @enderror
                                                    </label>

                                                    <div class="inline-actions">
                                                        <button type="submit" class="primary-button">Save changes</button>
                                                        <button type="button" wire:click="cancelEditing" class="ghost-button">Cancel</button>
                                                    </div>
                                                </form>
                                            @else
                                                <blockquote>{!! nl2br(e($entry->metafor)) !!}</blockquote>

                                                <div class="rating-strip">
                                                    <div class="rating-strip-copy">
                                                        <span class="rating-strip-label">Rate this metaphor</span>
                                                        <p class="rating-strip-hint">Pick a score from 1 to 5.</p>
                                                    </div>

                                                    <div class="rating-actions">
                                                        @for ($rating = 1; $rating <= 5; $rating++)
                                                            <button
                                                                type="button"
                                                                wire:click="rateMetafor({{ $entry->id }}, {{ $rating }})"
                                                                class="rating-button {{ ($currentRatings[$entry->id] ?? null) === $rating ? 'is-active' : '' }}"
                                                            >
                                                                {{ $rating }}
                                                            </button>
                                                        @endfor
                                                    </div>
                                                </div>

                                                <footer>
                                                    <span>Added {{ $entry->created_at->diffForHumans() }}</span>
                                                    @if ($confirmingDeleteId === $entry->id)
                                                        <span>Delete is armed. Click again to remove it.</span>
                                                    @elseif ($moderationUnlocked)
                                                        <span>Moderation is unlocked for this session.</span>
                                                    @endif
                                                </footer>
                                            @endif
                                        </article>
                                    @endforeach
                                </div>
                            </section>
                        @endforeach
                    </div>

                    @if ($metafors->hasPages())
                        <nav class="pager" aria-label="Metafor pagination">
                            <button type="button" wire:click="previousPage" class="ghost-button" @disabled($metafors->onFirstPage())>
                                Previous
                            </button>

                            <p class="pager-copy">Page {{ $metafors->currentPage() }} of {{ $metafors->lastPage() }}</p>

                            <button type="button" wire:click="nextPage" class="ghost-button" @disabled(! $metafors->hasMorePages())>
                                Next
                            </button>
                        </nav>
                    @endif
                @else
                    <section class="results-grid">
                        <article class="empty-state panel">
                            <p class="eyebrow">No matches yet</p>
                            <h3>Try a broader search or add the first metaphor for this topic.</h3>
                            <p>Filtering is instant, so a blank state usually means the current keyword or group is too narrow.</p>
                        </article>
                    </section>
                @endif
            </div>
        </div>
    </div>
</section>