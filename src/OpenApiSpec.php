<?php

namespace App;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    title: "SSJ Mukdahan API",
    description: "API documentation for SSJ Mukdahan system.",
    contact: new OA\Contact(
        email: "admin@ssjmukdahan.go.th"
    )
)]
#[OA\Server(
    url: "http://localhost/api/v1",
    description: "Local Development Server"
)]
class OpenApiSpec
{
}
