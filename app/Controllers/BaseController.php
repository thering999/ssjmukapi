<?php

namespace App\Controllers;

class BaseController
{
    protected function jsonResponse($data, $status = 200)
    {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data);
        exit();
    }

    protected function errorResponse($message, $status = 400)
    {
        $this->jsonResponse(['error' => true, 'message' => $message], $status);
    }

    protected function successResponse($data = [], $message = 'Success', $status = 200)
    {
        $this->jsonResponse(['error' => false, 'message' => $message, 'data' => $data], $status);
    }
    
    protected function getInput()
    {
        return json_decode(file_get_contents('php://input'), true) ?? [];
    }
}
