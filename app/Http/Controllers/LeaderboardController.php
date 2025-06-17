<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HistorySubmitScore;

class LeaderboardController extends Controller
{
 
    public function getLeaderboard(Request $request){
        // Default per_page adalah 10
        $perPage = $request->get('per_page', 10); 

        // Ambil leaderboard berdasarkan skor tertinggi di setiap level
        $leaderboard = HistorySubmitScore::select('user_id', 'level', \DB::raw('MAX(score) as total_score'))
            ->groupBy('user_id', 'level') // Group berdasarkan user_id dan level
            ->orderByDesc('total_score')  // Urutkan berdasarkan total score
            ->paginate($perPage);  // Pagination

        return response()->json($leaderboard);
    }
}
