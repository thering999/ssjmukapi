<?php

namespace App\Controllers;

use App\Support\Response;
use Exception;

class OpendataController
{
    private static function _fetchData(string $tableName, array $params)
    {
        $year = $params['year'] ?? date('Y') + 543;
        $province = $params['province'] ?? null;

        if (empty($province)) {
            Response::error('BAD_REQUEST', 'province parameter is required', 400);
    private static function getService(): \App\Services\MophOpendataService
    {
        return new \App\Services\MophOpendataService();
    }

    private static function handleRequest(callable $callback)
    {
        try {
            $data = $callback();
            Response::success($data);
        } catch (Exception $e) {
            $code = $e->getCode();
            // Map standard HTTP codes or default to 500
            $httpCode = ($code >= 400 && $code < 600) ? $code : 500;
            Response::error('API_ERROR', $e->getMessage(), $httpCode);
        }
    }

    public static function getIpd10SexSummary($params)
    {
        self::handleRequest(fn() => self::getService()->fetchData('s_ipd10_sex', $params));
    }

    public static function getOpd10SexSummary($params)
    {
        self::handleRequest(fn() => self::getService()->fetchData('s_opd10_sex', $params));
    }

    public static function getOpdAllSummary($params)
    {
        self::handleRequest(fn() => self::getService()->fetchData('s_opd_all', $params));
    }

    public static function getSchoolSummaryByType($params)
    {
        self::handleRequest(fn() => self::getService()->fetchData('s_school2', $params));
    }

    public static function getSchoolStudentSummary($params)
    {
        self::handleRequest(fn() => self::getService()->fetchData('s_school3', $params));
    }

    public static function getHealthCoverageCardSummary($params)
    {
        self::handleRequest(fn() => self::getService()->fetchData('s_card', $params));
    }

    public static function getVolunteerHealthSummary($params)
    {
        self::handleRequest(fn() => self::getService()->fetchData('s_volunteer_health', $params));
    }

    public static function getProviderSummaryByType($params)
    {
        self::handleRequest(fn() => self::getService()->fetchData('s_provider', $params));
    }

    public static function getHospitalServicePlanSummary($params)
    {
        self::handleRequest(fn() => self::getService()->fetchData('s_hospital1', $params));
    }

    public static function getHospitalSummaryByLevel($params)
    {
        self::handleRequest(fn() => self::getService()->fetchData('s_hospital', $params));
    }

    public static function getPcc1ByService($params)
    {
        self::handleRequest(fn() => self::getService()->fetchData('s_pcc1', $params));
    }

    public static function getPersonTypeByService($params)
    {
        self::handleRequest(fn() => self::getService()->fetchData('s_persontype', $params));
    }

    public static function getPopulationSexAgeDirect()
    {
        self::handleRequest(fn() => self::getService()->fetchDataGet('s_pop_sex_age'));
    }

    public static function getPopulationSexAgeMophDirect()
    {
        self::handleRequest(fn() => self::getService()->fetchDataGet('s_pop_sex_age_moph'));
    }

    public static function getLaborSexAge($params)
    {
        self::handleRequest(fn() => self::getService()->fetchData('s_labor_sex_age', $params));
    }

    public static function getPopulationSexAgeMoph($params)
    {
        self::handleRequest(fn() => self::getService()->fetchData('s_pop_sex_age_moph', $params));
    }

    public static function getPopulationSexAge($params)
    {
        self::handleRequest(fn() => self::getService()->fetchData('s_pop_sex_age', $params));
    }
}