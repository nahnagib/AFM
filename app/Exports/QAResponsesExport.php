<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class QAResponsesExport implements FromCollection, WithHeadings
{
    protected Collection $rows;

    public function __construct(Collection $rows)
    {
        $this->rows = $rows;
    }

    public function collection(): Collection
    {
        return $this->rows->map(function ($row) {
            return [
                'Student ID' => $row->student_id,
                'Course' => $row->course_label,
                'Form Code' => $row->form_code,
                'Section' => $row->section_label,
                'Question' => $row->question_text,
                'Answer' => $row->answer_value,
                'Submitted At' => $row->submitted_at 
                    ? $row->submitted_at->format('Y-m-d H:i') 
                    : null,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Student ID',
            'Course',
            'Form Code',
            'Section',
            'Question',
            'Answer',
            'Submitted At',
        ];
    }
}
