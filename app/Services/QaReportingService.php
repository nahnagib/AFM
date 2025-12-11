<?php

namespace App\Services;

use App\Models\CompletionFlag;
use App\Models\Form;
use App\Models\Response;
use App\Models\FormCourseScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class QaReportingService extends BaseService
{
    public function getCurrentTerm(): string
    {
        // Return internal term code (matches what we store in responses table)
        return '202510'; // Spring 2025
    }

    /**
     * Helper to get course/student assignments from AFM feedback data
     * Returns array: [ 'course_reg_no' => [ 'code' => ..., 'name' => ..., 'students' => [id1 => name1, ...] ] ]
     */
    private function getEnrollmentsFromAFMFeedback(string $termCode): array
    {
        // Strategy: Build enrollment from actual AFM data (completion_flags + responses)
        // This works for both SSO and dev login scenarios
        $courses = [];

        // Get all completion flags for this term
        $completions = CompletionFlag::where('term_code', $termCode)
            ->with('form')
            ->get();

        foreach ($completions as $completion) {
            $regNo = $completion->course_reg_no;
            $studentId = $completion->sis_student_id;

            // Service / system forms may have NULL course_reg_no → skip for course-level logic
            if ($regNo === null) {
                continue;
            }

            if (!isset($courses[$regNo])) {
                // Try to get course details from form_course_scope or responses table
                $scope = FormCourseScope::where('course_reg_no', $regNo)
                    ->where('term_code', $termCode)
                    ->first();

                $courses[$regNo] = [
                    'code'     => $this->extractCourseCode($regNo),
                    'name'     => $this->extractCourseName($regNo),
                    'students' => [],
                ];
            }

            // Add student (use ID for both key and name for now, as we don't have name in AFM)
            if (!isset($courses[$regNo]['students'][$studentId])) {
                $courses[$regNo]['students'][$studentId] = "Student {$studentId}";
            }
        }

        // Also get students from responses who may not have completed yet
        $responses = Response::where('term_code', $termCode)
            ->where('status', 'submitted')
            ->get();

        foreach ($responses as $response) {
            $regNo = $response->course_reg_no;
            $studentId = $response->sis_student_id;

            // Again, ignore service-level / non-course forms
            if ($regNo === null) {
                continue;
            }

            if (!isset($courses[$regNo])) {
                $scope = FormCourseScope::where('course_reg_no', $regNo)
                    ->where('term_code', $termCode)
                    ->first();

                $courses[$regNo] = [
                    'code'     => $this->extractCourseCode($regNo),
                    'name'     => $this->extractCourseName($regNo),
                    'students' => [],
                ];
            }

            if (!isset($courses[$regNo]['students'][$studentId])) {
                $courses[$regNo]['students'][$studentId] = "Student {$studentId}";
            }
        }

        return $courses;
    }

    /**
     * Extract course code from course_reg_no
     * Examples:
     *  - "CS301_001_202410" → "CS301"
     *  - "SE401-202410"     → "SE401"
     *  - "SE401-Spring2025" → "SE401"
     */
    private function extractCourseCode(?string $courseRegNo): ?string
    {
        if ($courseRegNo === null || $courseRegNo === '') {
            return null;
        }

        // Split on underscore or dash, take first token as code
        $parts = preg_split('/[_-]/', $courseRegNo);

        return $parts[0] ?? null;
    }

    /**
     * Extract course name (placeholder for now, could be enhanced with lookup table)
     */
    private function extractCourseName(?string $courseRegNo): string
    {
        $code = $this->extractCourseCode($courseRegNo);

        if ($code === null || $code === '') {
            // Service / global forms or unknown code
            return 'General Survey';
        }

        // Basic mapping for known courses (could be moved to config or database)
        $knownCourses = [
            'CS301'  => 'Database Systems',
            'CS302'  => 'Database Systems',
            'SE401'  => 'Software Engineering Project',
            'SE402'  => 'Quality Assurance',
            'IT201'  => 'Web Development',
            'IT202'  => 'Web Development',
            'IT210'  => 'Computer Networks',
            'MA201'  => 'Discrete Mathematics',
            'MATH101'=> 'Calculus I',
        ];

        return $knownCourses[$code] ?? "{$code} Course";
    }

    public function getOverviewMetrics(string $termCode): array
    {
        // 1. Eligible Students: All who reached the dashboard (from registry)
        $eligibleStudents = \App\Models\AfmStudentRegistry::where('term_code', $termCode)
            ->distinct('sis_student_id')
            ->count('sis_student_id');

        // 2. Completed Students: Count distinct students who completed at least one course evaluation
        $completedStudents = Response::where('term_code', $termCode)
            ->whereHas('form', fn ($q) => $q->where('code', 'COURSE_EVAL_DEFAULT'))
            ->where('status', 'submitted')
            ->distinct('sis_student_id')
            ->count('sis_student_id');

        // 3. Participation Rate
        $participationRate = $eligibleStudents > 0
            ? round(($completedStudents / $eligibleStudents) * 100, 1)
            : 0;

        // 4. Pending Evaluations
        $pendingEvaluations = max($eligibleStudents - $completedStudents, 0);

        // 5. High Risk Courses (placeholder for now)
        $highRiskCount = 0; // TODO: Implement based on average scores

        return [
            'total_students'      => $eligibleStudents,
            'participation_rate'  => $participationRate,
            'pending_evaluations' => $pendingEvaluations,
            'high_risk_courses'   => $highRiskCount,
        ];
    }

    public function getParticipationByCourse(string $termCode): array
    {
        $enrollments = $this->getEnrollmentsFromAFMFeedback($termCode);
        $stats = [];

        foreach ($enrollments as $regNo => $data) {
            $target = count($data['students']);

            $completed = CompletionFlag::where('course_reg_no', $regNo)
                ->where('term_code', $termCode)
                ->distinct('sis_student_id')
                ->count();

            $rate = $target > 0 ? round(($completed / $target) * 100, 1) : 0;

            $stats[] = [
                'course_reg_no' => $regNo,
                'course_name'   => $data['name'],
                'participation' => $rate,
                'total'         => $target,
                'completed'     => $completed,
            ];
        }

        // Sort by participation ascending (worst first)
        usort($stats, fn($a, $b) => $a['participation'] <=> $b['participation']);

        return array_slice($stats, 0, 10); // Top 10 worst
    }

    public function getCompletionReport(string $termCode, ?string $courseRegNo = null, ?string $formType = null, ?string $status = null): array
    {
        $enrollments = $this->getEnrollmentsFromAFMFeedback($termCode);
        $report = [];

        // Filter by course if requested
        if ($courseRegNo) {
            $enrollments = array_intersect_key($enrollments, [$courseRegNo => true]);
        }

        foreach ($enrollments as $regNo => $data) {
            $target = count($data['students']);

            $query = CompletionFlag::where('course_reg_no', $regNo)
                ->where('term_code', $termCode);

            if ($formType && $formType !== 'all') {
                $query->whereHas('form', function ($q) use ($formType) {
                    $q->where('form_type', $formType);
                });
            }

            $completed = $query->distinct('sis_student_id')->count();
            $rate = $target > 0 ? round(($completed / $target) * 100, 1) : 0;

            // Filter by Status
            if ($status === 'Completed' && $completed == 0) {
                continue;
            }
            if ($status === 'Not Completed' && $completed > 0) {
                continue;
            }

            $report[] = [
                'course_code' => $data['code'],
                'course_name' => $data['name'],
                'dept_name'   => 'N/A',
                'enrolled'    => $target,
                'completed'   => $completed,
                'rate'        => $rate,
            ];
        }

        return $report;
    }

    public function getNonCompleters(string $termCode, ?string $courseRegNo): Collection
    {
        $enrollments = $this->getEnrollmentsFromAFMFeedback($termCode);

        if ($courseRegNo) {
            $enrollments = array_intersect_key($enrollments, [$courseRegNo => true]);
        }

        $nonCompleters = new Collection();

        foreach ($enrollments as $regNo => $data) {
            foreach ($data['students'] as $studentId => $studentName) {
                $hasCompleted = CompletionFlag::where('sis_student_id', $studentId)
                    ->where('course_reg_no', $regNo)
                    ->where('term_code', $termCode)
                    ->exists();

                if (!$hasCompleted) {
                    $student = new \stdClass();
                    $student->sis_student_id = $studentId;
                    $student->full_name      = $studentName;
                    $student->email          = "{$studentId}@student.university.edu";
                    $student->department     = 'N/A';
                    $student->course_reg_no  = $regNo;

                    $nonCompleters->push($student);
                }
            }
        }

        return $nonCompleters;
    }

    public function getResponseSummary(Form $form, ?string $courseRegNo): array
    {
        $summary = [];

        foreach ($form->questions as $question) {
            $query = DB::table('response_items')
                ->join('responses', 'response_items.response_id', '=', 'responses.id')
                ->where('responses.form_id', $form->id)
                ->where('responses.status', 'submitted')
                ->where('response_items.question_id', $question->id);

            if ($courseRegNo) {
                $query->where('responses.course_reg_no', $courseRegNo);
            }

            if ($question->qtype === 'likert' || $question->qtype === 'rating') {
                $avg = $query->avg('numeric_value');
                $dist = $query->select('numeric_value', DB::raw('count(*) as count'))
                    ->groupBy('numeric_value')
                    ->pluck('count', 'numeric_value')
                    ->toArray();

                $summary[$question->id] = [
                    'type'         => $question->qtype,
                    'prompt'       => $question->prompt,
                    'average'      => round($avg, 2),
                    'distribution' => $dist,
                ];
            } else {
                if (in_array($question->qtype, ['mcq_single', 'mcq_multi', 'yes_no'])) {
                    $counts = $query->select('option_value', DB::raw('count(*) as count'))
                        ->groupBy('option_value')
                        ->pluck('count', 'option_value')
                        ->toArray();

                    $summary[$question->id] = [
                        'type'   => $question->qtype,
                        'prompt' => $question->prompt,
                        'counts' => $counts,
                    ];
                }
            }
        }

        return $summary;
    }

    public function getStudentReport(string $termCode, ?string $courseRegNo = null, ?string $studentId = null, ?string $status = null, ?string $formType = null): array
    {
        $enrollments = $this->getEnrollmentsFromAFMFeedback($termCode);
        $report = [];

        // Pre-fetch completion flags for this term
        $query = CompletionFlag::where('term_code', $termCode);

        if ($formType && $formType !== 'all') {
            $query->whereHas('form', function ($q) use ($formType) {
                $q->where('form_type', $formType);
            });
        }

        $completions = $query->get()
            ->groupBy(function ($item) {
                return $item->sis_student_id . '-' . $item->course_reg_no;
            });

        foreach ($enrollments as $regNo => $data) {
            // Filter by Course (flexible matching: code, partial string, or full course_reg_no)
            if ($courseRegNo) {
                $needle = strtolower(trim($courseRegNo));

                $codeMatch = isset($data['code'])
                    ? str_contains(strtolower($data['code']), $needle)
                    : false;

                $regNoMatch = str_contains(strtolower((string) $regNo), $needle);

                if (!$codeMatch && !$regNoMatch) {
                    continue;
                }
            }

            foreach ($data['students'] as $sId => $sName) {
                // Filter by Student ID
                if ($studentId && stripos($sId, $studentId) === false) {
                    continue;
                }

                $key = $sId . '-' . $regNo;
                $isCompleted = isset($completions[$key]);
                $recordStatus = $isCompleted ? 'Completed' : 'Not Completed';

                // Filter by Status
                if ($status && $status !== 'all' && $recordStatus !== $status) {
                    continue;
                }

                $report[] = [
                    'student_id'   => $sId,
                    'student_name' => $sName,
                    'course_code'  => $data['code'],
                    'course_name'  => $data['name'],
                    'status'       => $recordStatus,
                ];
            }
        }

        return $report;
    }
}
