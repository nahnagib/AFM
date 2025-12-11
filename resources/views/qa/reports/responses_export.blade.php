<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تقرير الردود التفصيلي</title>
    <style>
        body {
            direction: rtl;
            unicode-bidi: bidi-override;
            font-family: 'Tajawal', 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #0f172a;
            margin: 20px;
        }
        h1 {
            font-size: 20px;
            margin-bottom: 10px;
        }
        p.meta {
            font-size: 12px;
            color: #475569;
            margin-bottom: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 6px 8px;
            border: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        thead th {
            background-color: #e2e8f0;
            font-weight: bold;
            font-size: 11px;
        }
        .col-ar {
            text-align: right;
            white-space: normal;
        }
        .col-id,
        .col-course,
        .col-form,
        .col-answer,
        .col-submitted {
            direction: ltr;
            text-align: center;
        }
    </style>
</head>
<body>
    <h1>تصدير الردود التفصيلية</h1>
    <p class="meta">
        الفصل الدراسي: <strong>{{ $termCode }}</strong>
        @if($courseRegNo)
            &nbsp;|&nbsp; المقرر: <strong>{{ $courseRegNo }}</strong>
        @endif
    </p>

    <table>
        <thead>
            <tr>
                <th class="col-id">Student ID</th>
                <th class="col-course">Course</th>
                <th class="col-form">Form</th>
                <th class="col-ar">Section</th>
                <th class="col-ar">Question</th>
                <th class="col-answer">Answer</th>
                <th class="col-submitted">Submitted At</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                <tr>
                    <td class="col-id">{{ $row->student_id }}</td>
                    <td class="col-course">{{ $row->course_label }}</td>
                    <td class="col-form">{{ $row->form_code }}</td>
                    <td class="col-ar">{{ $row->section_label }}</td>
                    <td class="col-ar">{{ $row->question_text }}</td>
                    <td class="col-answer">
                        @if(is_numeric($row->answer_value))
                            {{ number_format($row->answer_value, 2) }}
                        @else
                            {{ $row->answer_value }}
                        @endif
                    </td>
                    <td class="col-submitted">{{ optional($row->submitted_at)->format('Y-m-d H:i') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
