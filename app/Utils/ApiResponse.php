<?php

namespace App\Utils;

class ApiResponse{    
    public static function singleResponse($data, $statusCode = 200, $message = 'success'){
        return response()->json([
            'status' => [
                'code' => $statusCode,
                'description' => $message,
            ],
            'data' => $data
        ], $statusCode);
    }

    public static function pagedResponse($data, $page, $size, $total){
        return response()->json([
            'status' => [
                'code' => 200,
                'description' => 'success get data',
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
}
