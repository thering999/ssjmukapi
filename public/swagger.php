<?php

require __DIR__ . '/../vendor/autoload.php';

use OpenApi\Generator;

header('Content-Type: application/x-yaml');

$openapi = Generator::scan([__DIR__ . '/../src']);
echo $openapi->toYaml();
