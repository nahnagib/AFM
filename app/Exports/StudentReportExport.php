<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class StudentReportExport implements FromArray, WithHeadings, WithMapping, ShouldAutoSize
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
            'Student ID',
            'Student Name',
            'Course Code',
            'Course Name',
            'Status',
        ];
    }

    public function map($row): array
    {
        return [
            $row['student_id'],
            $row['student_name'],
            $row['course_code'],
            $row['course_name'],
            $row['status'],
        ];
    }
}
