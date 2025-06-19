<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AssessmentController extends Controller
{
    /**
     * Handle sending assessment request to external API
     */
    public function sendAssessment(Request $request)
    {
        // Get API URL and Secret Key from .env
        $apiUrl = env('API_URL'); // URL endpoint API eksternal
        $secretKey = env('SECRET_KEY'); // Secret key
        $nonce = Str::random(32);
        $timestamp = $request->input('timestamp');
        
        $signature = $this->generateSignature($nonce, $timestamp, $secretKey);
        $headers = [
            'X-Nonce' => $nonce,
            'X-API-Signature' => $signature,
        ];
        $body = [
            'timestamp' => $timestamp,
        ];
        
        try {
            $response = Http::withHeaders($headers)->post($apiUrl, $body);

            Log::info('API Response: ', ['response' => $response->body()]);

            return response()->json([
                'message' => 'Request sent successfully!',
                'data' => $response->json()
            ], $response->status());
        } catch (\Exception $e) {
            Log::error('Error sending request to API: ', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to send request'], 500);
        }
    }

    private function generateSignature($nonce, $timestamp, $secretKey)
    {        
        $stringToHash = $nonce . $timestamp . $secretKey;        
        return hash('sha256', $stringToHash);
    }
}
