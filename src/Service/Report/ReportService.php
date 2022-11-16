<?php

namespace App\Service\Report;

class ReportService
{

    public function __construct(private ReportEpcService $reportEpc,
    private ReportsMonthlyService $reportsMonthly,
    private AssetManagementService $assetManagement,
    private ReportEpcPRNewService $reportEpcNew){}
}