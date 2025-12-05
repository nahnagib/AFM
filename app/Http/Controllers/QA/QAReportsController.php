<?php

namespace App\Http\Controllers\QA;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Services\QaReportingService;
use Illuminate\Http\Request;

class QAReportsController extends Controller
{
    protected $qaReporting;

    public function __construct(QaReportingService $qaReporting)
    {
        $this->qaReporting = $qaReporting;
    }

    public function completionReport(Request $request)
    {
        $termCode = $request->query('term', $this->qaReporting->getCurrentTerm());
        $courseRegNo = $request->query('course');
        $formType = $request->query('form_type');
        $status = $request->query('status');

        $report = $this->qaReporting->getCompletionReport($termCode, $courseRegNo, $formType, $status);

        if ($request->has('export')) {
            $format = $request->query('export');
            $filename = 'completion_report_' . $termCode . '.' . $format;
            
            if ($format === 'pdf') {
                // For PDF, we might want to use DomPDF directly or via Excel if configured.
                // Maatwebsite Excel supports PDF via DomPDF.
                return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\CompletionReportExport($report), $filename, \Maatwebsite\Excel\Excel::DOMPDF);
            }
            
            return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\CompletionReportExport($report), $filename);
        }

        return view('qa.reports.completion', [
            'report' => $report,
            'termCode' => $termCode,
            'courseRegNo' => $courseRegNo,
            'formType' => $formType,
            'status' => $status,
        ]);
    }

    public function studentReport(Request $request)
    {
        $termCode = $request->query('term', $this->qaReporting->getCurrentTerm());
        $courseRegNo = $request->query('course');
        $studentId = $request->query('student_id');
        $status = $request->query('status');
        $formType = $request->query('form_type');

        $report = $this->qaReporting->getStudentReport($termCode, $courseRegNo, $studentId, $status, $formType);

        if ($request->has('export')) {
            $format = $request->query('export');
            $filename = 'student_report_' . $termCode . '.' . $format;
            
            if ($format === 'pdf') {
                return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\StudentReportExport($report), $filename, \Maatwebsite\Excel\Excel::DOMPDF);
            }
            
            return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\StudentReportExport($report), $filename);
        }

        return view('qa.reports.student_report', [
            'report' => $report,
            'termCode' => $termCode,
            'courseRegNo' => $courseRegNo,
            'studentId' => $studentId,
            'status' => $status,
            'formType' => $formType,
        ]);
    }

    public function nonCompleters(Request $request)
    {
        $termCode = $request->query('term', $this->qaReporting->getCurrentTerm());
        $courseRegNo = $request->query('course');

        if (!$courseRegNo) {
            return back()->with('error', 'Please select a course.');
        }

        $nonCompleters = $this->qaReporting->getNonCompleters($termCode, $courseRegNo);

        return view('qa.reports.non_completers', [
            'nonCompleters' => $nonCompleters,
            'termCode' => $termCode,
            'courseRegNo' => $courseRegNo,
        ]);
    }

    public function responseAnalysis(Request $request, $formId)
    {
        $form = Form::with('questions')->findOrFail($formId);
        $courseRegNo = $request->query('course');

        $summary = $this->qaReporting->getResponseSummary($form, $courseRegNo);

        return view('qa.reports.response_analysis', [
            'form' => $form,
            'summary' => $summary,
            'courseRegNo' => $courseRegNo,
        ]);
    }
}
