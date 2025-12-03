<?php

namespace App\Services;

use PDO;

class FacilityService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function list(array $query): array
    {
        $page = max(1, (int)($query['page'] ?? 1));
        $perPage = min(200, max(1, (int)($query['per_page'] ?? 50)));
        $offset = ($page - 1) * $perPage;

        $where = [];
        $params = [];

        if (!empty($query['province_code'])) {
            $where[] = 'province_code = :province_code';
            $params[':province_code'] = $query['province_code'];
        }
        if (!empty($query['district_code'])) {
            $where[] = 'district_code = :district_code';
            $params[':district_code'] = $query['district_code'];
        }
        if (!empty($query['type'])) {
            $where[] = 'type = :type';
            $params[':type'] = $query['type'];
        }
        if (!empty($query['q'])) {
            $where[] = '(code LIKE :q OR name_th LIKE :q)';
            $params[':q'] = '%' . $query['q'] . '%';
        }

        $sqlBase = 'FROM facilities';
        if ($where) {
            $sqlBase .= ' WHERE ' . implode(' AND ', $where);
        }

        // total
        $stmt = $this->pdo->prepare('SELECT COUNT(*) AS cnt ' . $sqlBase);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        $total = (int) $stmt->fetchColumn();

        // data
        $sql = 'SELECT id, code, name_th, type, province_code, district_code, lat, lng, phone, updated_at ' . $sqlBase . ' ORDER BY id DESC LIMIT :limit OFFSET :offset';
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        return [
            'data' => $rows,
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
            ]
        ];
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, code, name_th, type, province_code, district_code, lat, lng, phone, updated_at FROM facilities WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO facilities (code, name_th, type, province_code, district_code, lat, lng, phone) VALUES (:code, :name_th, :type, :province_code, :district_code, :lat, :lng, :phone)');
        $stmt->bindValue(':code', $data['code']);
        $stmt->bindValue(':name_th', $data['name_th']);
        $stmt->bindValue(':type', $data['type']);
        $stmt->bindValue(':province_code', $data['province_code']);
        $stmt->bindValue(':district_code', $data['district_code']);
        $stmt->bindValue(':lat', $data['lat'] ?? null);
        $stmt->bindValue(':lng', $data['lng'] ?? null);
        $stmt->bindValue(':phone', $data['phone'] ?? null);
        $stmt->execute();

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        // Check if exists
        if (!$this->find($id)) {
            return false;
        }

        $fields = [];
        $params = [':id' => $id];
        foreach (['code', 'name_th', 'type', 'province_code', 'district_code', 'lat', 'lng', 'phone'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }

        if (empty($fields)) {
            return true; // Nothing to update
        }

        $sql = 'UPDATE facilities SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();

        return true;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM facilities WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
}
