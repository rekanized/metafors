<?php

namespace App\Livewire;

use App\Models\Metafor;
use App\Models\MetaforRating;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class MetaforBoard extends Component
{
    use WithPagination;

    public const MAX_METAFOR_LENGTH = 2000;

    private const SUBMISSION_LIMIT = 6;

    private const SUBMISSION_DECAY_SECONDS = 600;

    public int $perPage = 12;

    public string $search = '';

    public string $explainedForFilter = '';

    public string $metafor = '';

    public string $explained_for = '';

    public ?int $editingId = null;

    public ?int $confirmingDeleteId = null;

    public string $editMetafor = '';

    public string $editExplainedFor = '';

    public string $moderationKey = '';

    public bool $moderationUnlocked = false;

    public function mount(): void
    {
        $this->moderationUnlocked = (bool) session('metafors.moderation_unlocked', false);
    }

    public static function limiterKey(?string $ip = null): string
    {
        return 'metafor-submission:'.($ip ?: request()->ip() ?: 'unknown');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingExplainedForFilter(): void
    {
        $this->resetPage();
    }

    public function unlockModeration(): void
    {
        $validated = $this->validate([
            'moderationKey' => ['required', 'string'],
        ]);

        $configuredKey = (string) config('services.metafors.moderation_key', '');

        if ($configuredKey === '' || ! hash_equals($configuredKey, $validated['moderationKey'])) {
            $this->addError('moderationKey', 'Incorrect moderation key.');

            return;
        }

        $this->moderationUnlocked = true;
        session(['metafors.moderation_unlocked' => true]);
        $this->reset('moderationKey');
        $this->resetValidation('moderationKey');

        session()->flash('status', 'Moderation unlocked.');
    }

    public function lockModeration(): void
    {
        $this->moderationUnlocked = false;
        $this->reset('moderationKey');
        $this->cancelEditing();
        $this->confirmingDeleteId = null;
        session()->forget('metafors.moderation_unlocked');

        session()->flash('status', 'Moderation locked.');
    }

    public function save(): void
    {
        $validated = $this->validate([
            'metafor' => ['required', 'string', 'min:6', 'max:'.self::MAX_METAFOR_LENGTH],
            'explained_for' => ['required', 'string', 'min:2', 'max:120'],
        ]);

        if (! $this->ensureCanSubmit()) {
            return;
        }

        $validated = $this->normalizePayload($validated);

        if ($this->hasDuplicate($validated['metafor'], $validated['explained_for'])) {
            $this->addError('metafor', 'This metaphor already exists for that software solution.');

            return;
        }

        try {
            Metafor::query()->create($validated);
        } catch (QueryException $exception) {
            if (! $this->isDuplicateConstraintViolation($exception)) {
                throw $exception;
            }

            $this->addError('metafor', 'This metaphor already exists for that software solution.');

            return;
        }

        RateLimiter::hit(static::limiterKey(), self::SUBMISSION_DECAY_SECONDS);

        $this->reset('metafor', 'explained_for');
        $this->resetValidation();

        session()->flash('status', 'Metafor saved.');
    }

    public function startEditing(int $metaforId): void
    {
        if (! $this->ensureCanModerate()) {
            return;
        }

        $entry = Metafor::query()->findOrFail($metaforId);

        $this->editingId = $entry->id;
        $this->confirmingDeleteId = null;
        $this->editMetafor = $entry->metafor;
        $this->editExplainedFor = $entry->explained_for;
        $this->resetValidation();
    }

    public function cancelEditing(): void
    {
        $this->reset('editingId', 'editMetafor', 'editExplainedFor');
        $this->resetValidation();
    }

    public function saveEdit(): void
    {
        if (! $this->ensureCanModerate()) {
            return;
        }

        if ($this->editingId === null) {
            return;
        }

        $validated = $this->validate([
            'editMetafor' => ['required', 'string', 'min:6', 'max:'.self::MAX_METAFOR_LENGTH],
            'editExplainedFor' => ['required', 'string', 'min:2', 'max:120'],
        ]);

        $payload = $this->normalizePayload([
            'metafor' => $validated['editMetafor'],
            'explained_for' => $validated['editExplainedFor'],
        ]);

        if ($this->hasDuplicate($payload['metafor'], $payload['explained_for'], $this->editingId)) {
            $this->addError('editMetafor', 'This metaphor already exists for that software solution.');

            return;
        }

        try {
            Metafor::query()->findOrFail($this->editingId)->update($payload);
        } catch (QueryException $exception) {
            if (! $this->isDuplicateConstraintViolation($exception)) {
                throw $exception;
            }

            $this->addError('editMetafor', 'This metaphor already exists for that software solution.');

            return;
        }

        if ($this->explainedForFilter !== '' && $this->explainedForFilter !== $payload['explained_for']) {
            $this->explainedForFilter = '';
        }

        $this->cancelEditing();

        session()->flash('status', 'Metafor updated.');
    }

    public function confirmDelete(int $metaforId): void
    {
        if (! $this->ensureCanModerate()) {
            return;
        }

        $this->confirmingDeleteId = $this->confirmingDeleteId === $metaforId ? null : $metaforId;
    }

    public function deleteMetafor(int $metaforId): void
    {
        if (! $this->ensureCanModerate()) {
            return;
        }

        Metafor::query()->findOrFail($metaforId)->delete();

        if ($this->editingId === $metaforId) {
            $this->cancelEditing();
        }

        if ($this->confirmingDeleteId === $metaforId) {
            $this->confirmingDeleteId = null;
        }

        session()->flash('status', 'Metafor deleted.');
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'explainedForFilter');
        $this->resetPage();
    }

    public function filterByExplainedFor(string $group): void
    {
        if ($this->explainedForFilter === $group) {
            return;
        }

        $this->explainedForFilter = $group;
        $this->resetPage();
    }

    public function filterByEncodedExplainedFor(string $encodedGroup): void
    {
        $group = base64_decode($encodedGroup, true);

        if (! is_string($group)) {
            return;
        }

        $this->filterByExplainedFor($group);
    }

    public function rateMetafor(int $metaforId, int $rating): void
    {
        if ($rating < 1 || $rating > 5) {
            return;
        }

        $entry = Metafor::query()->findOrFail($metaforId);

        MetaforRating::query()->updateOrCreate(
            [
                'metafor_id' => $entry->id,
                'rater_token' => $this->raterToken(),
            ],
            [
                'rating' => $rating,
            ],
        );

        session()->flash('status', 'Rating saved.');
    }

    protected function ensureCanSubmit(): bool
    {
        $key = static::limiterKey();

        if (! RateLimiter::tooManyAttempts($key, self::SUBMISSION_LIMIT)) {
            return true;
        }

        $seconds = RateLimiter::availableIn($key);

        $this->addError(
            'metafor',
            "Slow down a little. Please wait {$seconds} seconds before adding another metaphor."
        );

        return false;
    }

    protected function ensureCanModerate(): bool
    {
        if ($this->moderationUnlocked) {
            return true;
        }

        $this->addError('moderationKey', 'Enter the moderation key to edit or delete metaphors.');

        return false;
    }

    protected function hasDuplicate(string $metafor, string $explainedFor, ?int $ignoreId = null): bool
    {
        $signature = Metafor::signature($metafor, $explainedFor);

        return Metafor::query()
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->where('signature', $signature)
            ->exists();
    }

    /**
     * @param  array{metafor: string, explained_for: string}  $payload
     * @return array{metafor: string, explained_for: string}
     */
    protected function normalizePayload(array $payload): array
    {
        return [
            'metafor' => Metafor::normalizeMetafor($payload['metafor']),
            'explained_for' => Metafor::normalizeExplainedFor($payload['explained_for']),
        ];
    }

    protected function raterToken(): string
    {
        $token = session('metafors.rater_token');

        if (is_string($token) && $token !== '') {
            return $token;
        }

        $token = Str::uuid()->toString();
        session(['metafors.rater_token' => $token]);

        return $token;
    }

    protected function emptyPaginator(): LengthAwarePaginator
    {
        return new LengthAwarePaginator(collect(), 0, $this->perPage, 1, [
            'path' => request()->url(),
            'pageName' => 'page',
        ]);
    }

    protected function metaforsQuery(): Builder
    {
        return Metafor::query()
            ->withAvg('ratings', 'rating')
            ->withCount('ratings')
            ->when(
                $this->explainedForFilter !== '',
                fn ($query) => $query->where('explained_for', $this->explainedForFilter),
            )
            ->when(
                trim($this->search) !== '',
                fn ($query) => $query->search($this->search),
            )
            ->when(
                $this->explainedForFilter === '',
                fn ($query) => $query->orderBy('explained_for'),
            )
            ->orderByDesc('ratings_avg_rating')
            ->orderByDesc('ratings_count')
            ->latest()
            ->orderByDesc('id');
    }

    protected function isDuplicateConstraintViolation(QueryException $exception): bool
    {
        return in_array((string) $exception->getCode(), ['19', '23000', '23505'], true);
    }

    public function render(): View
    {
        if (! Schema::hasTable('metafors') || ! Schema::hasTable('metafor_ratings')) {
            $metafors = $this->emptyPaginator();

            return view('livewire.metafor-board', [
                'groups' => new Collection(),
                'metafors' => $metafors,
                'groupedMetafors' => new Collection(),
                'currentRatings' => collect(),
                'filteredCount' => 0,
                'totalGroups' => 0,
                'totalMetafors' => 0,
                'moderationKeyConfigured' => (string) config('services.metafors.moderation_key', '') !== '',
            ]);
        }

        $groups = Metafor::query()
            ->select('explained_for')
            ->distinct()
            ->orderBy('explained_for')
            ->pluck('explained_for');

        $metafors = $this->metaforsQuery()->paginate($this->perPage);

        if ($metafors->total() > 0 && $metafors->currentPage() > $metafors->lastPage()) {
            $this->setPage($metafors->lastPage());

            $metafors = $this->metaforsQuery()->paginate($this->perPage);
        }

        $currentRatings = MetaforRating::query()
            ->where('rater_token', $this->raterToken())
            ->whereIn('metafor_id', $metafors->pluck('id'))
            ->pluck('rating', 'metafor_id');

        return view('livewire.metafor-board', [
            'groups' => $groups,
            'metafors' => $metafors,
            'groupedMetafors' => $metafors->getCollection()->groupBy('explained_for'),
            'currentRatings' => $currentRatings,
            'filteredCount' => $metafors->total(),
            'totalGroups' => $groups->count(),
            'totalMetafors' => Metafor::query()->count(),
            'moderationKeyConfigured' => (string) config('services.metafors.moderation_key', '') !== '',
        ]);
    }
}