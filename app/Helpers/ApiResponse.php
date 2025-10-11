<?php

namespace App\Helpers;

class ApiResponse
{
    public static function success($data = [], $message = 'Operación Exitosa', $statusCode = 200)
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
            'errors' => null
        ], $statusCode);
    }

    public static function error($message = 'Ocurrió un error', $statusCode = 500, $errors = [])
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'errors' => $errors,
            'data' => null
        ], $statusCode);
    }
}
