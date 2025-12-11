<?php

namespace App\Http\Controllers\QA;

use App\Http\Controllers\Controller;
use App\Exports\QAResponsesExport;
use App\Models\Form;
use App\Models\Response;
use App\Models\ResponseItem;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelWriter;

class QAResponsesReportController extends Controller
{
    public function index(Request $request)
    {
        $termCode = $request->query('term', '202510'); // Default to Spring 2025
        $formId = $request->query('form_id');
        $courseRegNo = $request->query('course_reg_no');

        // Load available forms for filter
        $forms = Form::where('is_active', true)
            ->whereIn('code', ['COURSE_EVAL_DEFAULT', 'SERVICES_EVAL_DEFAULT'])
            ->get();

        // Get distinct courses from responses
        $courses = Response::where('term_code', $termCode)
            ->whereNotNull('course_reg_no')
            ->where('status', 'submitted')
            ->select('course_reg_no')
            ->distinct()
            ->orderBy('course_reg_no')
            ->pluck('course_reg_no');

        // Query detailed responses if filters are applied
        $detailedResponses = $formId 
            ? $this->loadDetailedResponses($termCode, $formId, $courseRegNo)
            : collect();

        $exportFormat = $request->query('export');

        if ($exportFormat && $formId) {
            if ($detailedResponses->isEmpty()) {
                return redirect()
                    ->route('qa.reports.responses', $request->except('export'))
                    ->with('error', 'No responses available to export for the selected filters.');
            }

            return $this->exportResponses($exportFormat, $detailedResponses, $termCode, $courseRegNo);
        }

        return view('qa.reports.responses', [
            'termCode' => $termCode,
            'forms' => $forms,
            'courses' => $courses,
            'selectedFormId' => $formId,
            'selectedCourse' => $courseRegNo,
            'detailedResponses' => $detailedResponses,
        ]);
    }

    private function loadDetailedResponses(string $termCode, string $formId, ?string $courseRegNo): Collection
    {
        $query = ResponseItem::query()
            ->with(['response.form', 'question.section'])
            ->whereHas('response', function ($q) use ($termCode, $courseRegNo, $formId) {
                $q->where('term_code', $termCode)
                  ->where('form_id', $formId)
                  ->where('status', 'submitted');

                if ($courseRegNo) {
                    $q->where('course_reg_no', $courseRegNo);
                }
            })
            ->join('responses', 'response_items.response_id', '=', 'responses.id')
            ->join('questions', 'response_items.question_id', '=', 'questions.id')
            ->select('response_items.*')
            ->orderBy('responses.sis_student_id')
            ->orderBy('responses.course_reg_no')
            ->orderBy('questions.section_id')
            ->orderBy('questions.order');

        return $query->get()->map(function ($item) {
            return (object) [
                'student_id' => $item->response->sis_student_id,
                'course_reg_no' => $item->response->course_reg_no ?? 'General',
                'course_label' => $item->response->course_reg_no ?? 'General',
                'form_code' => $item->response->form->code ?? 'N/A',
                'section_label' => $item->question->section->title ?? 'N/A',
                'question_text' => $item->question->prompt,
                'answer_value' => $item->value,
                'submitted_at' => $item->response->updated_at,
            ];
        });
    }

    private function exportResponses(string $format, Collection $rows, string $termCode, ?string $courseRegNo)
    {
        $courseSuffix = $courseRegNo ? '_'.str_replace([' ', '/'], '_', $courseRegNo) : '';
        $filename = "afm_responses_{$termCode}{$courseSuffix}_" . now()->format('Ymd_His');

        if ($format === 'excel') {
            return Excel::download(new QAResponsesExport($rows), "{$filename}.xlsx");
        }

        if ($format === 'csv') {
            return Excel::download(new QAResponsesExport($rows), "{$filename}.csv", ExcelWriter::CSV);
        }

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('qa.reports.responses_export', [
                'rows' => $rows,
                'termCode' => $termCode,
                'courseRegNo' => $courseRegNo,
            ])->setPaper('a4', 'landscape');

            return $pdf->download("{$filename}.pdf");
        }

        return redirect()
            ->route('qa.reports.responses')
            ->with('error', 'Unsupported export format requested.');
    }
}
