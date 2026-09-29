<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\Worker;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * فريق زيارات جاهز: قائد + عضوان (كلهم cleaner). مرّر leader_id لتجاوز الإنشاء التلقائي للأعضاء.
 *
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'فريق '.fake()->unique()->numberBetween(1, 9999),
            'is_active' => true,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Team $team) {
            if ($team->leader_id === null) {
                $members = Worker::factory()->cleaner()->count(3)->create();
                $team->members()->sync($members->modelKeys());
                $team->update(['leader_id' => $members->first()->id]);
            }
        });
    }
}
