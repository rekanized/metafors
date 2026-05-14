<?php

namespace Database\Seeders;

use App\Models\Metafor;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MetaforSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $metafors = [
            [
                'explained_for' => 'Legacy system changes',
                'metafor' => 'Changing a load-bearing rule in a legacy system is like rebuilding the floor while people are still walking on it.',
            ],
            [
                'explained_for' => 'Caching',
                'metafor' => 'Caching is a pantry stocked with the ingredients your application grabs all day.',
            ],
            [
                'explained_for' => 'Queues',
                'metafor' => 'A job queue is an air traffic tower that spaces out background work so nothing collides.',
            ],
            [
                'explained_for' => 'Feature flags',
                'metafor' => 'Feature flags are light switches behind the walls, so you can open a room without rebuilding the house.',
            ],
            [
                'explained_for' => 'CI/CD',
                'metafor' => 'CI/CD is a conveyor belt with checkpoints, where every change rides the same line before it reaches customers.',
            ],
            [
                'explained_for' => 'Event sourcing',
                'metafor' => 'Event sourcing is a flight recorder for business state: you can rewind every change instead of guessing what happened.',
            ],
            [
                'explained_for' => 'API gateway',
                'metafor' => 'An API gateway is a front desk that routes visitors, checks badges, and keeps the hallways orderly.',
            ],
            [
                'explained_for' => 'Database indexes',
                'metafor' => 'A database index is the table of contents for your data; without it, every query starts at page one.',
            ],
            [
                'explained_for' => 'Observability',
                'metafor' => 'Observability is a glass cockpit for a distributed system: you fly by gauges, traces, and alerts instead of instinct.',
            ],
            [
                'explained_for' => 'Zero-downtime deployments',
                'metafor' => 'A zero-downtime deployment is swapping train lines while passengers are still boarding, so the route changes without stopping the station.',
            ],
        ];

        foreach ($metafors as $entry) {
            Metafor::query()->firstOrCreate($entry);
        }
    }
}