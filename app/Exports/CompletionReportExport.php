<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class CompletionReportExport implements FromArray, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'Course Code',
            'Course Name',
            'Department',
            'Enrolled Students',
            'Completed Evaluations',
            'Completion Rate (%)',
        ];
    }

    public function map($row): array
    {
        return [
            $row['course_code'],
            $row['course_name'],
            $row['dept_name'],
            $row['enrolled'],
            $row['completed'],
            $row['rate'] . '%',
        ];
    }
}
