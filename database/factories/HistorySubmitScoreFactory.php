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
            'level' => $this->faker->numberBetween(1, 10),  // Level antara 1 dan 10
            'score' => $this->faker->numberBetween(1000, 5000),  // Skor antara 1000 dan 5000
        ];
    }
}
