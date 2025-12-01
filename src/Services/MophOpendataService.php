<?php

namespace App\Services;

use Exception;

class MophOpendataService
{
    private const BASE_URL = 'https://opendata.moph.go.th/api/report_data';

    /**
     * Fetch data from MOPH Open Data API (POST method)
     * 
     * @param string $tableName
     * @param array $params
     * @return array
     * @throws Exception
     */
    public function fetchData(string $tableName, array $params): array
    {
        $body = [
            'tableName' => $tableName,
            'year' => $params['year'] ?? date('Y') + 543,
            'province' => $params['province'] ?? null,
            'type' => 'json'
        ];

        if (empty($body['province'])) {
            throw new Exception('province parameter is required', 400);
        }

        $ch = curl_init(self::BASE_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        $response = curl_exec($ch);
        $err = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($err) {
            throw new Exception('Curl error: ' . $err, 500);
        }

        if ($httpCode !== 200) {
             throw new Exception('Upstream API returned error code: ' . $httpCode, 502);
        }

        $data = json_decode($response, true);
        if ($data === null) {
            throw new Exception('Invalid JSON response from upstream', 502);
        }

        return $data;
    }

    /**
     * Fetch data from MOPH Open Data API (GET method)
     * 
     * @param string $tableName
     * @return array
     * @throws Exception
     */
    public function fetchDataGet(string $tableName): array
    {
        $url = self::BASE_URL . "/{$tableName}/";

        // Suppress warnings for file_get_contents and handle errors manually
        $response = @file_get_contents($url);

        if ($response === false) {
            $error = error_get_last();
            throw new Exception('Failed to fetch data: ' . ($error['message'] ?? 'Unknown error'), 500);
        }

        $data = json_decode($response, true);

        if ($data === null) {
            throw new Exception('Invalid JSON response from upstream', 502);
        }

        return $data;
    }
}
