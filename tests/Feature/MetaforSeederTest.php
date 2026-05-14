<?php

namespace Tests\Feature;

use App\Models\Metafor;
use Database\Seeders\MetaforSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MetaforSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_metafor_seeder_creates_starting_examples_idempotently(): void
    {
        $this->seed(MetaforSeeder::class);
        $this->seed(MetaforSeeder::class);

        $this->assertSame(10, Metafor::query()->count());

        $this->assertDatabaseHas('metafors', [
            'explained_for' => 'Legacy system changes',
        ]);

        $this->assertDatabaseHas('metafors', [
            'explained_for' => 'Caching',
        ]);

        $this->assertDatabaseHas('metafors', [
            'explained_for' => 'Observability',
        ]);
    }
}