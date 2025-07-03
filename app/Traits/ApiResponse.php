<?php


namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

trait ApiResponse
{
    protected function successResponse($data = null, string $message = 'Success', int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    protected function errorResponse(string $message, int $statusCode = 400, $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    protected function resourceResponse(JsonResource $resource, string $message = 'Success'): JsonResponse
    {
        return $this->successResponse($resource, $message);
    }

    protected function collectionResponse($collection, string $message = 'Success'): JsonResponse
    {
        return $this->successResponse($collection, $message);
    }
}