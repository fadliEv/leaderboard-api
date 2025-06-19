<?php

namespace App\Http\Controllers;


use App\Models\HistorySubmitScore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Utils\ApiResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Collection;
use Illuminate\Validation\Validator as ValidationValidator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class LeaderboardController extends Controller {

    public function submitHistoryScore(Request $request): JsonResponse {
        $validationResponse = $this->validateScoreRequest($request);
        if ($validationResponse) {
            return $validationResponse;
        }

        try {
            $history = $this->createHistoryScore($request);
            
            // Clear leaderboard cache when new score is submitted
            $this->clearLeaderboardCache();
            
            return $this->successResponse($history);
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e);
        }
    }

    public function getLeaderboard(Request $request): JsonResponse {
        $page = (int) $request->query('page', 1);
        $size = (int) $request->query('size', 10);
        $username = $request->query('username');

        try {
            // Generate cache key berdasarkan halaman, ukuran, dan username
            $cacheKey = $this->generateCacheKey($page, $size, $username);

            // Cek apakah leaderboard ada di Redis
            $cached = Redis::get($cacheKey);
            if ($cached !== null) {
                // Cache HIT: Data sudah ada di Redis
                Log::debug('Cache HIT for key: ' . $cacheKey);
                return JsonResponse::fromJsonString($cached);
            }

            // Cache MISS: Data tidak ada di Redis, lakukan query untuk leaderboard
            Log::debug('Cache MISS for key: ' . $cacheKey);

            // Query leaderboard
            $leaderboardQuery = $this->buildLeaderboardQuery($username);
            $totalRecords = $this->getTotalRecords($username);
            $leaderboardData = $leaderboardQuery->get();
            $rankedData = $this->addRanking($leaderboardData);
            $totalPages = (int) ceil($totalRecords / $size);
            $pagedData = $this->applyPagination($rankedData->values(), $page, $size);

            // Buat response API
            $response = ApiResponse::pagedResponse($pagedData, $page, $size, $totalRecords, $totalPages);

            // Simpan data leaderboard ke Redis
            $duration = max(60, min(86400, (int)config('leaderboard.cache_duration', 3600))); // Durasi cache default 1 jam jika tidak ada di config
            Redis::setex($cacheKey, $duration, $response->content());

            return $response;
        } catch (\Exception $e) {
            Log::error('Leaderboard error', ['error' => $e->getMessage()]);
            return $this->errorResponse($e);
        }
    }

    protected function generateCacheKey(int $page, int $size, ?string $username): string {
        // Membuat key cache yang unik berdasarkan page, size dan username
        $key = "leaderboard_page_{$page}_size_{$size}";
        if ($username) {
            $key .= "_user_" . md5($username);
        }
        return $key;
    }

    protected function clearLeaderboardCache(): void {
        try {
            $redis = Redis::connection();

            // Gunakan EVAL untuk delete cache leaderboard berdasarkan prefix
            $redis->eval(
                "local keys = redis.call('keys', ARGV[1]) 
                if #keys > 0 then 
                    return redis.call('del', unpack(keys)) 
                else 
                    return 0 
                end",
                0,
                'leaderboard_*' // Hapus semua cache dengan prefix 'leaderboard_'
            );

            Log::info('Leaderboard cache cleared');
        } catch (\Exception $e) {
            Log::error('Cache clearance failed', [
                'error' => $e->getMessage(),
                'exception' => $e
            ]);
        }
    }

    protected function validateScoreRequest(Request $request): ?JsonResponse {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'level' => ['required'],
            'score' => ['required'],
        ]);

        $this->addCustomValidationRules($validator, $request);

        if ($validator->fails()) {
            return ApiResponse::singleResponse(
                ['errors' => $validator->errors()],
                422,
                'Validation failed'
            );
        }

        return null;
    }

    protected function addCustomValidationRules(ValidationValidator $validator, Request $request): void{
        $validator->after(function ($validator) use ($request) {
            $this->validateLevel($validator, $request->level);
            $this->validateScore($validator, $request->score);
        });
    }


    protected function validateLevel(ValidationValidator $validator, $level): void {
        if (!is_int($level)) {
            $validator->errors()->add('level', 'The level must be an integer');
            return;
        }

        if ($level < 1 || $level > 10) {
            $validator->errors()->add('level', 'The level must be between 1 and 10');
        }
    }

    /**
     * Validate score requirements
     */
    protected function validateScore(ValidationValidator $validator, $score): void {
        if (!is_int($score)) {
            $validator->errors()->add('score', 'The score must be an integer');
            return;
        }

        if ($score < 1 || $score > 50000) {
            $validator->errors()->add('score', 'The score must be between 1 and 15000');
        }
    }

    protected function createHistoryScore(Request $request): HistorySubmitScore {
        return HistorySubmitScore::create([
            'user_id' => $request->user_id,
            'level' => $request->level,
            'score' => $request->score,
        ]);
    }

    protected function successResponse(HistorySubmitScore $history): JsonResponse {
        return ApiResponse::singleResponse([
            'message' => 'Score submitted successfully',
            'data' => $history
        ], 201, 'Success');
    }

    protected function serverErrorResponse(\Exception $e): JsonResponse {
        logger()->error('Score submission failed: ' . $e->getMessage());
        
        return ApiResponse::singleResponse([
            'error' => 'Internal server error'
        ], 500, 'Server Error');
    }

    protected function buildLeaderboardQuery(?string $username = null) {
        // First get the max score for each user at each level
        $maxScoresPerLevel = DB::table('history_submit_score')
            ->select('user_id', 'level', DB::raw('MAX(score) as max_score'))
            ->groupBy('user_id', 'level');

        // Then sum these max scores per user
        $leaderboard = DB::table(DB::raw("({$maxScoresPerLevel->toSql()}) as level_scores"))
            ->mergeBindings($maxScoresPerLevel)
            ->join('users', 'users.id', '=', 'level_scores.user_id')
            ->select(
                'users.id',
                'users.username',
                DB::raw('SUM(level_scores.max_score) as total_score'),
                DB::raw('MAX(level_scores.level) as last_level')
            )
            ->groupBy('users.id', 'users.username')
            ->orderByDesc('total_score');

        if ($username) {
            $leaderboard->where('users.username', $username);
        }

        return $leaderboard;
    }

    protected function applyPagination(Collection $data, int $page, int $size): Collection {
        return $data->forPage($page, $size)->values();
    }

    protected function getTotalRecords(?string $username = null): int {
        $subQuery = DB::table('history_submit_score')
            ->select('user_id', 'level', DB::raw('MAX(score) as total_score'))
            ->groupBy('user_id', 'level');

        $leaderboard = DB::table(DB::raw("({$subQuery->toSql()}) as t"))
            ->mergeBindings($subQuery)
            ->join('users', 'users.id', '=', 't.user_id')
            ->select('users.id');

        if ($username) {
            $leaderboard->where('users.username', $username);
        }

        return $leaderboard->count();
    }

    protected function addRanking(Collection $data): Collection {
        $rank = 1;
        return $data->sortByDesc('total_score')
            ->map(function ($item) use (&$rank) {
                $item->ranking = $rank++;
                return $item;
            });
    }

    protected function errorResponse(\Exception $e): JsonResponse {
        return ApiResponse::singleResponse([
            'error' => $e->getMessage()
        ], 500, 'Terjadi kesalahan saat mengambil data leaderboard. Silakan coba lagi.');
    }
}


