<?php

namespace App\Controllers;

use App\Services\AlertService;
use App\Support\Response;
use PDO;
use Rakit\Validation\Validator;
use Throwable;

class AlertsController
{
    public static function send(PDO $pdo, array $data, array $alertConfig): void
    {
        $validator = new Validator();
        $validation = $validator->validate($data, [
            'cid' => 'required', // Can be string or array, handled below
            'messages' => 'array',
            'message_text' => 'required_without:messages',
            'message_type' => 'alpha_num'
        ]);

        if ($validation->fails()) {
            Response::error('VALIDATION_ERROR', 'Invalid data', 400, $validation->errors()->toArray());
            return;
        }

        try {
            $service = new AlertService($alertConfig);
            
            // Normalize CID
            $cid = $data['cid'];
            if (!is_array($cid)) {
                $cid = [$cid];
            }

            // Normalize Messages
            $messages = $data['messages'] ?? [];
            if (empty($messages) && !empty($data['message_text'])) {
                $messages = [['type' => 'text', 'text' => $data['message_text']]];
            }

            $payload = [
                'cid' => $cid,
                'messages' => $messages,
                'message_title' => $data['message_title'] ?? '',
                'message_html' => $data['message_html'] ?? '',
                'message_text' => $data['message_text'] ?? '',
                'message_type' => $data['message_type'] ?? ($alertConfig['default_message_type'] ?? 'HPT'),
            ];

            // Remove empty optional fields
            foreach (['message_title', 'message_html', 'message_text'] as $key) {
                if (empty($payload[$key])) {
                    unset($payload[$key]);
                }
            }

            $result = $service->send($payload);
            Response::success($result['data'], [], $result['status']);

        } catch (Throwable $e) {
            Response::error('ALERT_ERROR', $e->getMessage(), 500);
        }
    }

    public static function sendTemplate(PDO $pdo, array $data, array $alertConfig): void
    {
        $validator = new Validator();
        $validation = $validator->validate($data, [
            'cid' => 'required',
            'template' => 'required',
            // Add other required fields for template if known
        ]);

        if ($validation->fails()) {
            Response::error('VALIDATION_ERROR', 'Invalid data', 400, $validation->errors()->toArray());
            return;
        }

        try {
            $service = new AlertService($alertConfig);

            // Normalize CID
            $cid = $data['cid'];
            if (!is_array($cid)) {
                $cid = [$cid];
            }

            $payload = $data;
            $payload['cid'] = $cid;
            $payload['message_type'] = $data['message_type'] ?? 'HPT';

            // Remove empty optional fields
            foreach ($payload as $key => $value) {
                if ($value === '' && $key !== 'cid' && $key !== 'message_type') {
                    unset($payload[$key]);
                }
            }

            $result = $service->sendTemplate($payload);
            Response::success($result['data'], [], $result['status']);

        } catch (Throwable $e) {
            Response::error('ALERT_ERROR', $e->getMessage(), 500);
        }
    }
}

