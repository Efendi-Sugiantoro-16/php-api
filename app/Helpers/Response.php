<?php
// app/helpers/response.php

namespace App\Helpers;

class Response
{
    public static function send($success, $message, $data = null, $statusCode = 200)
    {
        http_response_code($statusCode);

        $response = [
            'success' => $success,
            'message' => $message
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        echo json_encode($response);
        exit;
    }

    public static function success($message, $data = null, $statusCode = 200)
    {
        self::send(true, $message, $data, $statusCode);
    }

    public static function error($message, $statusCode = 400)
    {
        self::send(false, $message, null, $statusCode);
    }

    public static function getJsonInput()
    {
        $input = file_get_contents('php://input');
        return json_decode($input, true);
    }

    public static function validateRequiredFields($data, $requiredFields)
    {
        $missingFields = [];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || (is_string($data[$field]) && empty(trim($data[$field])))) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            self::error('Missing required fields: ' . implode(', ', $missingFields), 400);
        }

        return true;
    }
}
?>