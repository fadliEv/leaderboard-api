<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Utils\ApiResponse;

class LeaderboardController extends Controller
{
    public function getLeaderboard(Request $request)
    {
        // Variabel untuk pagination
        $page = (int) $request->query('page', 1);
        $size = (int) $request->query('size', 10);
        $username = $request->query('username', null); // Jika ada username untuk filter

        try {
            // Mendapatkan leaderboard query tanpa pagination
            $leaderboardQuery = $this->buildLeaderboardQuery($username);

            // Total Records (count seluruh data)
            $totalRecords = $this->getTotalRecords();

            // Ambil seluruh data leaderboard tanpa pagination terlebih dahulu
            $leaderboardData = $leaderboardQuery->get();

            // Menambahkan ranking berdasarkan total_score (ranking dihitung berdasarkan semua data)
            $rankedData = $this->addRanking($leaderboardData);

            // Pagination - ambil data sesuai dengan page dan size setelah ranking
            $totalPages = (int) ceil($totalRecords / $size); // Total halaman berdasarkan total records dan size

            // Gunakan `values()` untuk memastikan data tetap array numerik
            $pagedData = $this->applyPagination($rankedData->values(), $page, $size);

            // Return the successful response with leaderboard data and pagination
            return ApiResponse::pagedResponse($pagedData, $page, $size, $totalRecords, $totalPages);
        } catch (\Exception $e) {
            // Handle exception and return error response
            return $this->errorResponse($e);
        }
    }
    
    protected function buildLeaderboardQuery($username = null)
    {
        // Subquery untuk mendapatkan skor tertinggi tiap user dan level
        $subQuery = DB::table('history_submit_score')
            ->select('user_id', 'level', DB::raw('MAX(score) as total_score'))
            ->groupBy('user_id', 'level'); // Group by user_id dan level

        // Main leaderboard query
        $leaderboard = DB::table(DB::raw("({$subQuery->toSql()}) as t"))
            ->mergeBindings($subQuery) // Merge bindings from the subquery
            ->join('users', 'users.id', '=', 't.user_id') // Join dengan tabel users
            ->select('users.id', 'users.username', DB::raw('SUM(t.total_score) as total_score'), DB::raw('MAX(t.level) as last_level'))
            ->groupBy('users.id', 'users.username')
            ->orderByDesc(DB::raw('SUM(t.total_score)')); // Urutkan berdasarkan total_score

        // Filter berdasarkan username jika ada
        if ($username) {
            $leaderboard->where('users.username', $username);
        }

        return $leaderboard;
    }

    protected function applyPagination($data, $page, $size){
        // Apply pagination and return a slice of the collection
        $pagedData = $data->forPage($page, $size);

        // Convert to numeric array to avoid associative keys in the response
        return $pagedData->values();
    }

    protected function getTotalRecords($username = null)
    {
        // Mengambil total records dari subquery leaderboard tanpa pagination
        $subQuery = DB::table('history_submit_score')
            ->select('user_id', 'level', DB::raw('MAX(score) as total_score'))
            ->groupBy('user_id', 'level');

        // Query utama untuk leaderboard
        $leaderboard = DB::table(DB::raw("({$subQuery->toSql()}) as t"))
            ->mergeBindings($subQuery) // Merge bindings from the subquery
            ->join('users', 'users.id', '=', 't.user_id')
            ->select('users.id');

        // Jika ada filter username, tambahkan kondisi WHERE
        if ($username) {
            $leaderboard->where('users.username', $username);
        }

        return $leaderboard->count(); // Total jumlah users di leaderboard
    }


    protected function addRanking($data)
    {
        // Menambahkan ranking berdasarkan total_score (menggunakan collection)
        // Urutkan data berdasarkan total_score
        $ranked = $data->sortByDesc('total_score');

        // Menambahkan ranking secara manual
        $rank = 1;
        return $ranked->map(function ($item) use (&$rank) {
            $item->ranking = $rank++;
            return $item;
        });
    }

    protected function errorResponse(\Exception $e)
    {
        return ApiResponse::singleResponse([
            'error' => $e->getMessage()
        ], 500, 'Terjadi kesalahan saat mengambil data leaderboard. Silakan coba lagi.');
    }
}
