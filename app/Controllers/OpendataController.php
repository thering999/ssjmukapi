<?php

namespace App\Controllers;

class OpendataController extends BaseController
{
    private $cacheDir;
    private $cacheTime = 3600; // 1 hour

    public function __construct()
    {
        $this->cacheDir = __DIR__ . '/../../storage/cache/';
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }
    }

    public function proxy($endpoint)
    {
        // Sanitize endpoint
        $endpoint = preg_replace('/[^a-zA-Z0-9_\-\/]/', '', $endpoint);
        $url = $_ENV['MOPH_API_URL'] . '/' . $endpoint;
        
        $cacheFile = $this->cacheDir . md5($url) . '.json';

        if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $this->cacheTime)) {
            $data = json_decode(file_get_contents($cacheFile), true);
            $this->successResponse($data, 'Data retrieved from cache');
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For development
        $output = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $output) {
            file_put_contents($cacheFile, $output);
            $data = json_decode($output, true);
            $this->successResponse($data, 'Data retrieved from MOPH API');
        } else {
            $this->errorResponse('Failed to fetch data from MOPH API', 502);
        }
    }
}
