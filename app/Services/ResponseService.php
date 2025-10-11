<?php
namespace App\Services;

class ResponseService
{
    public static function success($message, $data = [], $statusCode = 200)
    {
        return response()->json([
            'isRequest' => true,
            'isSuccess' => true,
            'isMessageError' => false,
            'message' => $message,
            'messageError' => [],
            'data' => $data,
            'statusCode' => $statusCode
        ], $statusCode);
    }

    public static function error($message, $messageError = [], $statusCode = 500)
    {
        return response()->json([
            'isRequest' => true,
            'isSuccess' => false,
            'isMessageError' => true,
            'message' => $message,
            'messageError' => $messageError,
            'data' => [],
            'statusCode' => $statusCode
        ], $statusCode);
    }
}
