<?php

namespace App\Controllers;

use App\Support\Response;
use Exception;
use PDO;
use Throwable;

class ContactsController
{
    public static function index(PDO $pdo, array $query): void
    {
        $page = max(1, (int)($query['page'] ?? 1));
        $perPage = min(200, max(1, (int)($query['per_page'] ?? 50)));
        $offset = ($page - 1) * $perPage;

        $where = [];
        $params = [];

        if (!empty($query['q'])) {
            $where[] = '(name_th LIKE :q1 OR email LIKE :q2 OR phone LIKE :q3)';
            $params[':q1'] = '%' . $query['q'] . '%';
            $params[':q2'] = '%' . $query['q'] . '%';
            $params[':q3'] = '%' . $query['q'] . '%';
        }

        $sqlBase = 'FROM contacts';
        if ($where) {
            $sqlBase .= ' WHERE ' . implode(' AND ', $where);
        }

        try {
            $stmt = $pdo->prepare('SELECT COUNT(*) ' . $sqlBase);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v);
            }
            $stmt->execute();
            $total = (int) $stmt->fetchColumn();

            $sql = 'SELECT id, name_th, phone, email, url, updated_at, created_at ' . $sqlBase . ' ORDER BY id DESC LIMIT :limit OFFSET :offset';
            $stmt = $pdo->prepare($sql);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v);
            }
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll();

            $baseUrl = 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/api/v1/contacts';
            $headers = array_merge(
                Response::paginationLinks($baseUrl, $page, $perPage, $total),
                Response::cacheHeaders(300, true)
            );

            Response::success($rows, [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
            ], 200, $headers);
        } catch (Throwable $e) {
            Response::success([], [
                'page' => $page,
                'per_page' => $perPage,
                'total' => 0,
                'warning' => 'contacts table not found or DB error (dev mode)',
                'exception' => ($e instanceof Exception ? $e->getMessage() : 'error'),
            ]);
        }
    }

    public static function show(PDO $pdo, int $id): void
    {
        try {
            $stmt = $pdo->prepare('SELECT id, name_th, phone, email, url, updated_at, created_at FROM contacts WHERE id = :id LIMIT 1');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch();
            if (!$row) {
                Response::error('NOT_FOUND', 'Contact not found', 404);
                return;
            }
            Response::success($row);
        } catch (Throwable $e) {
            Response::error('DB_ERROR', 'Database error', 500, [
                'exception' => ($e instanceof Exception ? $e->getMessage() : 'error'),
            ]);
        }
    }

    public static function create(PDO $pdo, array $data): void
    {
        if (empty($data['name_th'])) {
            Response::error('VALIDATION_ERROR', "Field 'name_th' is required", 400);
            return;
        }

        try {
            $stmt = $pdo->prepare('INSERT INTO contacts (name_th, phone, email, url) VALUES (:name_th, :phone, :email, :url)');
            $stmt->bindValue(':name_th', $data['name_th']);
            $stmt->bindValue(':phone', $data['phone'] ?? null);
            $stmt->bindValue(':email', $data['email'] ?? null);
            $stmt->bindValue(':url', $data['url'] ?? null);
            $stmt->execute();

            $id = (int) $pdo->lastInsertId();
            Response::success(['id' => $id, 'message' => 'Contact created'], [], 201);
        } catch (Throwable $e) {
            Response::error('DB_ERROR', 'Failed to create contact', 500, [
                'exception' => ($e instanceof Exception ? $e->getMessage() : 'error'),
            ]);
        }
    }

    public static function update(PDO $pdo, int $id, array $data): void
    {
        try {
            $stmt = $pdo->prepare('SELECT id FROM contacts WHERE id = :id LIMIT 1');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            if (!$stmt->fetch()) {
                Response::error('NOT_FOUND', 'Contact not found', 404);
                return;
            }

            $fields = [];
            $params = [':id' => $id];
            foreach (['name_th', 'phone', 'email', 'url'] as $field) {
                if (isset($data[$field])) {
                    $fields[] = "$field = :$field";
                    $params[":$field"] = $data[$field];
                }
            }

            if (empty($fields)) {
                Response::error('VALIDATION_ERROR', 'No fields to update', 400);
                return;
            }

            $sql = 'UPDATE contacts SET ' . implode(', ', $fields) . ' WHERE id = :id';
            $stmt = $pdo->prepare($sql);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v);
            }
            $stmt->execute();

            Response::success(['message' => 'Contact updated']);
        } catch (Throwable $e) {
            Response::error('DB_ERROR', 'Failed to update contact', 500, [
                'exception' => ($e instanceof Exception ? $e->getMessage() : 'error'),
            ]);
        }
    }

    public static function delete(PDO $pdo, int $id): void
    {
        try {
            $stmt = $pdo->prepare('DELETE FROM contacts WHERE id = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                Response::error('NOT_FOUND', 'Contact not found', 404);
                return;
            }

            Response::success(['message' => 'Contact deleted']);
        } catch (Throwable $e) {
            Response::error('DB_ERROR', 'Failed to delete contact', 500, [
                'exception' => ($e instanceof Exception ? $e->getMessage() : 'error'),
            ]);
        }
    }
}
