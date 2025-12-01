<?php

namespace App\Controllers;

use App\Support\Response;
use Exception;
use PDO;
use Throwable;

use Rakit\Validation\Validator;

class AnnouncementsController
{
    public static function index(PDO $pdo, array $query): void
    {
        $page = max(1, (int)($query['page'] ?? 1));
        $perPage = min(200, max(1, (int)($query['per_page'] ?? 50)));
        $offset = ($page - 1) * $perPage;

        $where = [];
        $params = [];

        if (isset($query['published']) && $query['published'] !== '') {
            $where[] = 'published = :published';
            $params[':published'] = (int) (bool) $query['published'];
        }
        if (!empty($query['q'])) {
            $where[] = '(title LIKE :q OR body LIKE :q)';
            $params[':q'] = '%' . $query['q'] . '%';
        }

        $sqlBase = 'FROM announcements';
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

            $sql = 'SELECT id, title, body, published, published_at, updated_at, created_at ' . $sqlBase . ' ORDER BY COALESCE(published_at, created_at) DESC, id DESC LIMIT :limit OFFSET :offset';
            $stmt = $pdo->prepare($sql);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v);
            }
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll();

            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? 80) == 443 ? 'https://' : 'http://';
            $baseUrl = $protocol . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/api/v1/announcements';
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
                'warning' => 'announcements table not found or DB error (dev mode)',
                'exception' => ($e instanceof Exception ? $e->getMessage() : 'error'),
            ]);
        }
    }

    public static function show(PDO $pdo, int $id): void
    {
        try {
            $stmt = $pdo->prepare('SELECT id, title, body, published, published_at, updated_at, created_at FROM announcements WHERE id = :id LIMIT 1');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch();
            if (!$row) {
                Response::error('NOT_FOUND', 'Announcement not found', 404);
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
        $validator = new Validator();
        $validation = $validator->validate($data, [
            'title' => 'required',
            'body'  => 'required',
            'published' => 'boolean',
            'published_at' => 'date:Y-m-d H:i:s'
        ]);

        if ($validation->fails()) {
            Response::error('VALIDATION_ERROR', 'Invalid data', 400, $validation->errors()->toArray());
            return;
        }

        try {
            $published = !empty($data['published']) ? 1 : 0;
            $publishedAt = null;
            if ($published) {
                $ts = !empty($data['published_at']) ? strtotime($data['published_at']) : false;
                if ($ts === false) {
                    $ts = time();
                }
                $publishedAt = date('Y-m-d H:i:s', $ts);
            }

            $stmt = $pdo->prepare('INSERT INTO announcements (title, body, published, published_at) VALUES (:title, :body, :published, :published_at)');
            $stmt->bindValue(':title', $data['title']);
            $stmt->bindValue(':body', $data['body']);
            $stmt->bindValue(':published', $published, PDO::PARAM_INT);
            $stmt->bindValue(':published_at', $publishedAt);
            $stmt->execute();

            $id = (int) $pdo->lastInsertId();
            Response::success(['id' => $id, 'message' => 'Announcement created'], [], 201);
        } catch (Throwable $e) {
            Response::error('DB_ERROR', 'Failed to create announcement', 500, [
                'exception' => ($e instanceof Exception ? $e->getMessage() : 'error'),
            ]);
        }
    }

    public static function update(PDO $pdo, int $id, array $data): void
    {
        $validator = new Validator();
        $validation = $validator->validate($data, [
            'title' => 'min:1',
            'body'  => 'min:1',
            'published' => 'boolean',
            'published_at' => 'date:Y-m-d H:i:s'
        ]);

        if ($validation->fails()) {
            Response::error('VALIDATION_ERROR', 'Invalid data', 400, $validation->errors()->toArray());
            return;
        }

        try {
            $stmt = $pdo->prepare('SELECT id FROM announcements WHERE id = :id LIMIT 1');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            if (!$stmt->fetch()) {
                Response::error('NOT_FOUND', 'Announcement not found', 404);
                return;
            }

            $fields = [];
            $params = [':id' => $id];
            foreach (['title', 'body', 'published', 'published_at'] as $field) {
                if (isset($data[$field])) {
                    if ($field === 'published_at' && !empty($data[$field])) {
                        $ts = strtotime($data[$field]);
                        $data[$field] = date('Y-m-d H:i:s', $ts === false ? time() : $ts);
                    }
                    $fields[] = "$field = :$field";
                    $params[":$field"] = $data[$field];
                }
            }

            if (empty($fields)) {
                Response::error('VALIDATION_ERROR', 'No fields to update', 400);
                return;
            }

            $sql = 'UPDATE announcements SET ' . implode(', ', $fields) . ' WHERE id = :id';
            $stmt = $pdo->prepare($sql);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v);
            }
            $stmt->execute();

            Response::success(['message' => 'Announcement updated']);
        } catch (Throwable $e) {
            Response::error('DB_ERROR', 'Failed to update announcement', 500, [
                'exception' => ($e instanceof Exception ? $e->getMessage() : 'error'),
            ]);
        }
    }

    public static function delete(PDO $pdo, int $id): void
    {
        try {
            $stmt = $pdo->prepare('DELETE FROM announcements WHERE id = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                Response::error('NOT_FOUND', 'Announcement not found', 404);
                return;
            }

            Response::success(['message' => 'Announcement deleted']);
        } catch (Throwable $e) {
            Response::error('DB_ERROR', 'Failed to delete announcement', 500, [
                'exception' => ($e instanceof Exception ? $e->getMessage() : 'error'),
            ]);
        }
    }
}
