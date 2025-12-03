<?php

namespace App\Controllers;

use App\Services\LineService;
use App\Support\Response;
use Rakit\Validation\Validator;
use Throwable;

class LineController
{
    public static function send(array $data, array $lineConfig): void
    {
        $validator = new Validator();
        $validation = $validator->validate($data, [
            'message' => 'required',
            'user_ids' => 'array',
            'user_id' => 'required_without:user_ids',
        ]);

        if ($validation->fails()) {
            Response::error('VALIDATION_ERROR', 'Invalid data', 400, $validation->errors()->toArray());
            return;
        }

        try {
            $service = new LineService($lineConfig);

            // Normalize User IDs
            $userIds = [];
            if (!empty($data['user_ids']) && is_array($data['user_ids'])) {
                $userIds = $data['user_ids'];
            } elseif (!empty($data['user_id'])) {
                $userIds = [$data['user_id']];
            }

            $results = [];
            $errors = [];

            foreach ($userIds as $userId) {
                try {
                    $service->send($userId, $data['message'], $data['image_url'] ?? '');
                    $results[] = ['user_id' => $userId, 'status' => 'sent'];
                } catch (Throwable $e) {
                    $errors[] = ['user_id' => $userId, 'error' => $e->getMessage()];
                }
            }

            if (empty($errors)) {
                Response::success([
                    'sent_count' => count($results),
                    'results' => $results,
                ], [], 200);
            } else {
                Response::success([
                    'sent_count' => count($results),
                    'error_count' => count($errors),
                    'results' => $results,
                    'errors' => $errors,
                ], [], 207);
            }

        } catch (Throwable $e) {
            Response::error('LINE_ERROR', $e->getMessage(), 500);
        }
    }
}

