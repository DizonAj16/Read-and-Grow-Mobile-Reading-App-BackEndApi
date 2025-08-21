<?php

namespace App\Traits;

trait ApiResponse
{
    protected function success($data = null, string $message = 'Success', int $status = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $status);
    }

    protected function fail(string $message = 'Error', int $status = 400, $errors = null)
    {
        $payload = ['success' => false, 'message' => $message];
        if (!is_null($errors)) {
            $payload['errors'] = $errors;
        }
        return response()->json($payload, $status);
    }
}
