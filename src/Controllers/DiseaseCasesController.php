<?php

namespace App\Controllers;

use App\Support\Response;
use Exception;
use PDO;
use Throwable;

class DiseaseCasesController
{
    public static function index(PDO $pdo, array $query): void
    {
        $page = max(1, (int)($query['page'] ?? 1));
        $perPage = min(200, max(1, (int)($query['per_page'] ?? 50)));
        $offset = ($page - 1) * $perPage;

        $where = [];
        $params = [];

        if (!empty($query['date_from'])) {
            $where[] = 'report_date >= :date_from';
            $params[':date_from'] = $query['date_from'];
        }
        if (!empty($query['date_to'])) {
            $where[] = 'report_date <= :date_to';
            $params[':date_to'] = $query['date_to'];
        }
        if (!empty($query['icd10'])) {
            $where[] = 'icd10 = :icd10';
            $params[':icd10'] = $query['icd10'];
        }
        if (!empty($query['province_code'])) {
            $where[] = 'province_code = :province_code';
            $params[':province_code'] = $query['province_code'];
        }
        if (!empty($query['district_code'])) {
            $where[] = 'district_code = :district_code';
            $params[':district_code'] = $query['district_code'];
        }

        $sqlBase = 'FROM disease_cases';
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

            $sql = 'SELECT id, report_date, icd10, province_code, district_code, cases ' . $sqlBase . ' ORDER BY report_date DESC, id DESC LIMIT :limit OFFSET :offset';
            $stmt = $pdo->prepare($sql);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v);
            }
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll();

            $baseUrl = 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/api/v1/cases';
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
                'warning' => 'disease_cases table not found or DB error (dev mode)',
                'exception' => ($e instanceof Exception ? $e->getMessage() : 'error'),
            ]);
        }
    }
}
