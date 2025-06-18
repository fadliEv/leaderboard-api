<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\HistorySubmitScore;

class LeaderboardController extends Controller
{
    /**
     * Get leaderboard with pagination and optional username filter.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLeaderboard(Request $request)
    {
        try {
            // Get pagination and username filter from request
            $page = (int) $request->query('page', 1);
            $size = (int) $request->query('size', 10);
            $username = $request->query('username', null);

            // Get the leaderboard query
            $leaderboardQuery = $this->buildLeaderboardQuery($username);

            // Pagination
            $total = $leaderboardQuery->count();
            $leaderboardData = $this->applyPagination($leaderboardQuery, $page, $size)->get();

            // Return the successful response with leaderboard data and pagination
            return $this->successResponse($leaderboardData, $page, $size, $total);
        } catch (\Exception $e) {
            // Handle exception and return error response
            return $this->errorResponse($e);
        }
    }

    /**
     * Build the base leaderboard query with optional username filter.
     *
     * @param string|null $username
     * @return \Illuminate\Database\Query\Builder
     */
    protected function buildLeaderboardQuery($username = null)
    {
        // Subquery to get max score each user and level
        $subQuery = DB::table('history_submit_score')
            ->select('user_id', 'level', DB::raw('MAX(score) as total_score'))
            ->groupBy('user_id', 'level');

        // Main leaderboard query
        $leaderboard = DB::table(DB::raw("({$subQuery->toSql()}) as t"))
            ->mergeBindings($subQuery) // Merge bindings from the subquery
            ->join('users', 'users.id', '=', 't.user_id')
            ->select('users.id', 'users.username', DB::raw('SUM(t.total_score) as total_score'), DB::raw('MAX(t.level) as last_level'))
            ->groupBy('users.id', 'users.username')
            ->orderByDesc(DB::raw('SUM(t.total_score)'));

        // Apply username filter if provided
        if ($username) {
            $leaderboard->where('users.username', $username);
        }

        return $leaderboard;
    }

    /**
     * Apply pagination to the query.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param int $page
     * @param int $size
     * @return \Illuminate\Database\Query\Builder
     */
    protected function applyPagination($query, $page, $size)
    {
        return $query->offset(($page - 1) * $size)
            ->limit($size);
    }

    /**
     * Format success response.
     *
     * @param \Illuminate\Support\Collection $data
     * @param int $page
     * @param int $size
     * @param int $total
     * @return \Illuminate\Http\JsonResponse
     */
    protected function successResponse($data, $page, $size, $total)
    {
        return response()->json([
            'status' => [
                'code' => 200,
                'description' => 'success get leaderboard'
            ],
            'data' => $data,
            'pagination' => [
                'page' => $page,
                'rows_per_page' => $size,
                'total_rows' => $total,
                'total_pages' => ceil($total / $size),
            ]
        ]);
    }

    /**
     * Format error response.
     *
     * @param \Exception $e
     * @return \Illuminate\Http\JsonResponse
     */
    protected function errorResponse(\Exception $e)
    {
        return response()->json([
            'status' => [
                'code' => 500,
                'description' => 'Terjadi kesalahan saat mengambil data leaderboard. Silakan coba lagi.'
            ],
            'error' => $e->getMessage() // Optional: Include error message for debugging
        ], 500);
    }
}
