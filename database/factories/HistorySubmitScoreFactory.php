<?php

namespace Database\Factories;
use App\Models\HistorySubmitScore;
use App\Models\User;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HistorySubmitScore>
 */
class HistorySubmitScoreFactory extends Factory
{   

    protected $model = HistorySubmitScore::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'level' => $this->faker->numberBetween(1, 10),
            'score' => $this->faker->numberBetween(100, 5000),
        ];
    }
}
