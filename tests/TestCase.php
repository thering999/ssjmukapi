<?php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use GuzzleHttp\Client;

class TestCase extends BaseTestCase
{
    protected $client;
    protected $baseUrl = 'http://localhost/api/v1';

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'http_errors' => false, // Don't throw exceptions on 4xx/5xx
            'headers' => [
                'Accept' => 'application/json',
            ]
        ]);
    }

    protected function get($uri, $query = [])
    {
        return $this->client->get($uri, ['query' => $query]);
    }

    protected function post($uri, $data = [])
    {
        return $this->client->post($uri, ['json' => $data]);
    }

    protected function put($uri, $data = [])
    {
        return $this->client->put($uri, ['json' => $data]);
    }

    protected function delete($uri)
    {
        return $this->client->delete($uri);
    }
}
