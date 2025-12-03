<?php

namespace App\Controllers;

use App\Support\Response;
use Exception;
use Throwable;
use OpenApi\Attributes as OA;
use Rakit\Validation\Validator;

#[OA\Tag(name: "Facilities", description: "API for managing health facilities")]
class FacilitiesController
{
    private $service;

    public function __construct($service)
    {
        $this->service = $service;
    }

    #[OA\Get(
        path: "/facilities",
        tags: ["Facilities"],
        summary: "List all facilities",
        parameters: [
            new OA\Parameter(name: "page", in: "query", description: "Page number", schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "limit", in: "query", description: "Items per page", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Successful operation")
        ]
    )]
    public function index(array $query): void
    {
        try {
            $result = $this->service->list($query);

            $baseUrl = 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/api/v1/facilities';
            $headers = array_merge(
                Response::paginationLinks($baseUrl, $result['meta']['page'], $result['meta']['per_page'], $result['meta']['total']),
                Response::cacheHeaders(300, true)
            );

            Response::success($result['data'], $result['meta'], 200, $headers);
        } catch (Throwable $e) {
            Response::success([], [
                'warning' => 'facilities table not found or DB error (dev mode)',
                'exception' => ($e instanceof Exception ? $e->getMessage() : 'error'),
            ]);
        }
    }

    #[OA\Get(
        path: "/facilities/{id}",
        tags: ["Facilities"],
        summary: "Get a facility by ID",
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Facility found"),
            new OA\Response(response: 404, description: "Facility not found")
        ]
    )]
    public function show(int $id): void
    {
        try {
            $facility = $this->service->find($id);
            if (!$facility) {
                Response::error('NOT_FOUND', 'Facility not found', 404);
                return;
            }
            Response::success($facility);
        } catch (Throwable $e) {
            Response::error('DB_ERROR', 'Database error', 500, [
                'exception' => ($e instanceof Exception ? $e->getMessage() : 'error'),
            ]);
        }
    }

    #[OA\Post(
        path: "/admin/facilities",
        tags: ["Facilities"],
        summary: "Create a new facility",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["code", "name_th", "type", "province_code", "district_code"],
                properties: [
                    new OA\Property(property: "code", type: "string"),
                    new OA\Property(property: "name_th", type: "string"),
                    new OA\Property(property: "type", type: "string"),
                    new OA\Property(property: "province_code", type: "string"),
                    new OA\Property(property: "district_code", type: "string")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Facility created"),
            new OA\Response(response: 400, description: "Validation error")
        ]
    )]
    public function create(array $data): void
    {
        $validator = new Validator();
        $validation = $validator->validate($data, [
            'code'          => 'required',
            'name_th'       => 'required',
            'type'          => 'required',
            'province_code' => 'required',
            'district_code' => 'required'
        ]);

        if ($validation->fails()) {
            Response::error('VALIDATION_ERROR', 'Invalid data', 400, $validation->errors()->toArray());
            return;
        }

        try {
            $id = $this->service->create($data);
            Response::success(['id' => $id, 'message' => 'Facility created'], [], 201);
        } catch (Throwable $e) {
            Response::error('DB_ERROR', 'Failed to create facility', 500, [
                'exception' => ($e instanceof Exception ? $e->getMessage() : 'error'),
            ]);
        }
    }

    #[OA\Put(
        path: "/admin/facilities/{id}",
        tags: ["Facilities"],
        summary: "Update an existing facility",
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "name_th", type: "string"),
                    new OA\Property(property: "type", type: "string")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Facility updated"),
            new OA\Response(response: 404, description: "Facility not found")
        ]
    )]
    public function update(int $id, array $data): void
    {
        $validator = new Validator();
        $validation = $validator->validate($data, [
            'code'          => 'min:1',
            'name_th'       => 'min:1',
            'type'          => 'in:HOSPITAL,CLINIC,PHC,OTHER',
            'province_code' => 'numeric',
            'district_code' => 'numeric',
            'lat'           => 'numeric',
            'lng'           => 'numeric'
        ]);

        if ($validation->fails()) {
            Response::error('VALIDATION_ERROR', 'Invalid data', 400, $validation->errors()->toArray());
            return;
        }

        try {
            if (!$this->service->update($id, $data)) {
                Response::error('NOT_FOUND', 'Facility not found or nothing to update', 404);
                return;
            }
            Response::success(['message' => 'Facility updated']);
        } catch (Throwable $e) {
            Response::error('DB_ERROR', 'Failed to update facility', 500, [
                'exception' => ($e instanceof Exception ? $e->getMessage() : 'error'),
            ]);
        }
    }

    #[OA\Delete(
        path: "/admin/facilities/{id}",
        tags: ["Facilities"],
        summary: "Delete a facility",
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Facility deleted"),
            new OA\Response(response: 404, description: "Facility not found")
        ]
    )]
    public function delete(int $id): void
    {
        try {
            if (!$this->service->delete($id)) {
                Response::error('NOT_FOUND', 'Facility not found', 404);
                return;
            }
            Response::success(['message' => 'Facility deleted']);
        } catch (Throwable $e) {
            Response::error('DB_ERROR', 'Failed to delete facility', 500, [
                'exception' => ($e instanceof Exception ? $e->getMessage() : 'error'),
            ]);
        }
    }
}
