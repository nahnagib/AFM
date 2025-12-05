<?php

namespace App\Http\Controllers\QA;

use App\Http\Controllers\Controller;
use App\Services\QaReportingService;
use Illuminate\Http\Request;

class QAOverviewController extends Controller
{
    protected $qaReporting;

    public function __construct(QaReportingService $qaReporting)
    {
        $this->qaReporting = $qaReporting;
    }

    public function index(Request $request)
    {
        $termCode = $request->query('term', $this->qaReporting->getCurrentTerm());

        // Get overview metrics
        $metrics = $this->qaReporting->getOverviewMetrics($termCode);

        // Get participation by course (high-risk courses)
        $participationByCourse = $this->qaReporting->getParticipationByCourse($termCode);

        return view('qa.overview', [
            'metrics' => $metrics,
            'participationByCourse' => $participationByCourse,
            'currentTerm' => $termCode,
        ]);
    }
}
