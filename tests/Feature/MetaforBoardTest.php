<?php

namespace Tests\Feature;

use App\Livewire\MetaforBoard;
use App\Models\Metafor;
use App\Models\MetaforRating;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class MetaforBoardTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_renders_the_metafor_board(): void
    {
        config()->set('services.metafors.moderation_key', 'test-moderation-key');

        Metafor::query()->create([
            'metafor' => 'Caching is a pantry for data you reach for all day.',
            'explained_for' => 'Caching',
        ]);

        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('Collect metaphors that make systems easier to explain.')
            ->assertSee('Caching is a pantry for data you reach for all day.');
    }

    public function test_home_page_preserves_metafor_line_breaks(): void
    {
        config()->set('services.metafors.moderation_key', 'test-moderation-key');

        Metafor::query()->create([
            'metafor' => "First line\nSecond line",
            'explained_for' => 'Queues',
        ]);

        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('First line<br />', false)
            ->assertSee('Second line');
    }

    public function test_anyone_can_add_a_metafor(): void
    {
        config()->set('services.metafors.moderation_key', 'test-moderation-key');

        $longMetafor = str_repeat('A job queue is an airport departure board for background work. ', 10);

        Livewire::test(MetaforBoard::class)
            ->set('metafor', $longMetafor)
            ->set('explained_for', 'Queues')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('metafors', [
            'metafor' => trim($longMetafor),
            'explained_for' => 'Queues',
        ]);
    }

    public function test_multiline_metafors_keep_line_breaks_when_saved(): void
    {
        config()->set('services.metafors.moderation_key', 'test-moderation-key');

        Livewire::test(MetaforBoard::class)
            ->set('metafor', "Identity checks happen once.\nThe rest of the club trusts the stamp.")
            ->set('explained_for', 'Identity providers')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('metafors', [
            'metafor' => "Identity checks happen once.\nThe rest of the club trusts the stamp.",
            'explained_for' => 'Identity providers',
        ]);
    }

    public function test_livewire_filters_entries_by_search_and_group(): void
    {
        config()->set('services.metafors.moderation_key', 'test-moderation-key');

        Metafor::query()->create([
            'metafor' => 'Event sourcing is a flight data recorder for state changes.',
            'explained_for' => 'Event sourcing',
        ]);

        Metafor::query()->create([
            'metafor' => 'A queue is a ticket desk that spaces out work.',
            'explained_for' => 'Queues',
        ]);

        Livewire::test(MetaforBoard::class)
            ->set('search', 'flight')
            ->assertSee('Event sourcing is a flight data recorder for state changes.')
            ->assertDontSee('A queue is a ticket desk that spaces out work.')
            ->set('search', '')
            ->set('explainedForFilter', 'Queues')
            ->assertSee('A queue is a ticket desk that spaces out work.')
            ->assertDontSee('Event sourcing is a flight data recorder for state changes.');
    }

    public function test_anyone_can_edit_and_delete_a_metafor(): void
    {
        config()->set('services.metafors.moderation_key', 'test-moderation-key');

        $longMetafor = str_repeat('A cache is a pantry stocked for the requests you reach for most. ', 10);

        $entry = Metafor::query()->create([
            'metafor' => 'A cache is a pantry for quick ingredients.',
            'explained_for' => 'Caching',
        ]);

        Livewire::test(MetaforBoard::class)
            ->set('moderationKey', 'test-moderation-key')
            ->call('unlockModeration')
            ->call('startEditing', $entry->id)
            ->set('editMetafor', $longMetafor)
            ->set('editExplainedFor', 'Caching')
            ->call('saveEdit')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('metafors', [
            'id' => $entry->id,
            'metafor' => trim($longMetafor),
        ]);

        Livewire::test(MetaforBoard::class)
            ->set('moderationKey', 'test-moderation-key')
            ->call('unlockModeration')
            ->call('confirmDelete', $entry->id)
            ->call('deleteMetafor', $entry->id);

        $this->assertDatabaseMissing('metafors', [
            'id' => $entry->id,
        ]);
    }

    public function test_exact_duplicates_are_rejected(): void
    {
        config()->set('services.metafors.moderation_key', 'test-moderation-key');

        Metafor::query()->create([
            'metafor' => 'A queue is an airport departure board for work.',
            'explained_for' => 'Queues',
        ]);

        Livewire::test(MetaforBoard::class)
            ->set('metafor', '  A queue is an airport departure board for work.  ')
            ->set('explained_for', 'Queues')
            ->call('save')
            ->assertHasErrors(['metafor']);

        $this->assertDatabaseCount('metafors', 1);
    }

    public function test_database_enforces_normalized_duplicate_signatures(): void
    {
        config()->set('services.metafors.moderation_key', 'test-moderation-key');

        Metafor::query()->create([
            'metafor' => 'A queue is an airport departure board for work.',
            'explained_for' => 'Queues',
        ]);

        $this->expectException(QueryException::class);

        Metafor::query()->create([
            'metafor' => '  A queue is an airport departure board for work.  ',
            'explained_for' => '  queues ',
        ]);
    }

    public function test_public_submissions_are_rate_limited(): void
    {
        config()->set('services.metafors.moderation_key', 'test-moderation-key');

        $key = MetaforBoard::limiterKey('127.0.0.1');

        RateLimiter::clear($key);

        for ($attempt = 0; $attempt < 6; $attempt++) {
            RateLimiter::hit($key, 600);
        }

        Livewire::test(MetaforBoard::class)
            ->set('metafor', 'A queue is a train platform that spaces out work safely.')
            ->set('explained_for', 'Queues')
            ->call('save')
            ->assertHasErrors(['metafor']);

        $this->assertDatabaseCount('metafors', 0);
    }

    public function test_moderation_actions_require_a_valid_key(): void
    {
        config()->set('services.metafors.moderation_key', 'test-moderation-key');

        $entry = Metafor::query()->create([
            'metafor' => 'A queue is a staged platform for background work.',
            'explained_for' => 'Queues',
        ]);

        Livewire::test(MetaforBoard::class)
            ->call('startEditing', $entry->id)
            ->assertHasErrors(['moderationKey']);

        $this->assertDatabaseHas('metafors', [
            'id' => $entry->id,
        ]);
    }

    public function test_metafors_can_be_rated_and_sorted_within_a_solution_group(): void
    {
        config()->set('services.metafors.moderation_key', 'test-moderation-key');

        $lowerRated = Metafor::query()->create([
            'metafor' => 'A cache is a notebook for nearby data.',
            'explained_for' => 'Caching',
        ]);

        $higherRated = Metafor::query()->create([
            'metafor' => 'A cache is a pantry for the ingredients you grab all day.',
            'explained_for' => 'Caching',
        ]);

        Livewire::test(MetaforBoard::class)
            ->call('rateMetafor', $lowerRated->id, 2)
            ->call('rateMetafor', $higherRated->id, 5)
            ->set('explainedForFilter', 'Caching')
            ->assertSeeInOrder([
                'A cache is a pantry for the ingredients you grab all day.',
                'A cache is a notebook for nearby data.',
            ]);

        $this->assertDatabaseHas('metafor_ratings', [
            'metafor_id' => $higherRated->id,
            'rating' => 5,
        ]);
    }

    public function test_board_paginates_large_lists(): void
    {
        config()->set('services.metafors.moderation_key', 'test-moderation-key');

        foreach (range(1, 13) as $number) {
            Metafor::query()->create([
                'metafor' => "A queue metaphor [{$number}]",
                'explained_for' => 'Queues',
            ]);
        }

        Livewire::test(MetaforBoard::class)
            ->assertSee('Page 1 of 2')
            ->assertSee('A queue metaphor [13]')
            ->assertDontSee('A queue metaphor [1]')
            ->call('nextPage')
            ->assertSee('Page 2 of 2')
            ->assertSee('A queue metaphor [1]');
    }

    public function test_filtering_from_a_later_page_resets_back_to_the_first_page(): void
    {
        config()->set('services.metafors.moderation_key', 'test-moderation-key');

        foreach (range(1, 13) as $number) {
            Metafor::query()->create([
                'metafor' => "A queue metaphor [{$number}]",
                'explained_for' => 'Queues',
            ]);
        }

        Metafor::query()->create([
            'metafor' => 'A cache is a pantry for nearby requests.',
            'explained_for' => 'Caching',
        ]);

        Livewire::test(MetaforBoard::class)
            ->call('nextPage')
            ->assertSee('Page 2 of 2')
            ->call('filterByExplainedFor', 'Caching')
            ->assertSee('A cache is a pantry for nearby requests.')
            ->assertDontSee('Page 2 of 2');
    }

    public function test_out_of_range_pages_are_clamped_to_the_last_available_page(): void
    {
        config()->set('services.metafors.moderation_key', 'test-moderation-key');

        foreach (range(1, 13) as $number) {
            Metafor::query()->create([
                'metafor' => "A queue metaphor [{$number}]",
                'explained_for' => 'Queues',
            ]);
        }

        Livewire::withQueryParams(['page' => 99])
            ->test(MetaforBoard::class)
            ->assertSee('Page 2 of 2')
            ->assertSee('A queue metaphor [1]');
    }

    public function test_filter_chips_render_with_safe_encoded_actions(): void
    {
        config()->set('services.metafors.moderation_key', 'test-moderation-key');

        Metafor::query()->create([
            'metafor' => 'Identity checks happen once, then the whole club trusts the stamp.',
            'explained_for' => 'IDP; Identity and Service providers.',
        ]);

        Livewire::test(MetaforBoard::class)
            ->assertSeeHtml("wire:click=\"filterByEncodedExplainedFor('SURQOyBJZGVudGl0eSBhbmQgU2VydmljZSBwcm92aWRlcnMu')\"")
            ->assertDontSeeHtml("wire:click=\"filterByExplainedFor(\"");
    }
}