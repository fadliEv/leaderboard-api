<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\HistorySubmitScore;
use App\Models\User;

class HistorySubmitScoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Ambil semua user yang telah di-seed
        $users = User::all();

        // Pastikan setiap user memiliki 3 submit history
        foreach ($users as $user) {
            // Membuat 3 submit history untuk setiap user
            HistorySubmitScore::factory(3)->create([
                'user_id' => $user->id,
            ]);
        }
    }
}
