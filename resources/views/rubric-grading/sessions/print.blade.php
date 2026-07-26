<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rubric Assessment - {{ $session->name }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #000; margin: 0; background: #fff; }
        .page { page-break-after: always; padding: 18px; }
        .page:last-child { page-break-after: auto; }
        h1 { margin: 0; text-align: center; font-size: 15px; letter-spacing: .04em; }
        h2 { margin: 4px 0 12px; text-align: center; font-size: 13px; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid #000; padding: 5px 7px; font-size: 10px; vertical-align: top; }
        th { background: #e8e8e8; text-align: left; }
        .selected { background: #f2dedc; font-weight: 700; }
        .total td { background: #e8e8e8; font-weight: 700; }
        .sign td { height: 62px; vertical-align: bottom; }
        @media print {
            .toolbar { display: none; }
            .page { padding: 8px; }
        }
    </style>
</head>
<body>
    <div class="toolbar" style="padding: 12px; border-bottom: 1px solid #ddd;">
        <button onclick="window.print()">Print</button>
        <button onclick="window.close()">Close</button>
        @if ($tsrOnly)
            <span style="margin-left: 12px; font-size: 12px;">TSR only: printing graded students included in the T/S/R groups.</span>
        @endif
    </div>

    @if ($students->isEmpty())
        <section class="page">
            <h1>RUBRIC FOR ASSESSMENT</h1>
            <h2>{{ $session->rubric->title }}</h2>
            <p style="text-align: center; font-size: 12px;">No graded students are available for TSR-only printing.</p>
        </section>
    @endif

    @foreach ($students as $student)
        @php
            $rubric = $session->rubric;
            $scores = $student->scores->keyBy('criterion_id');
            $summary = $service->studentTotal($rubric, $student);
        @endphp
        <section class="page">
            <h1>RUBRIC FOR ASSESSMENT</h1>
            <h2>{{ $rubric->title }}</h2>

            <table>
                <tr>
                    <td style="width: 22%;"><strong>COURSE CODE</strong></td>
                    <td style="width: 28%;">{{ $rubric->course_code }}</td>
                    <td style="width: 22%;"><strong>COURSE NAME</strong></td>
                    <td>{{ $rubric->course_name }}</td>
                </tr>
                <tr>
                    <td><strong>STUDENT NAME</strong></td>
                    <td>{{ $student->name }}</td>
                    <td><strong>REGISTRATION NO</strong></td>
                    <td>{{ $student->registration_no }}</td>
                </tr>
                <tr>
                    <td><strong>CLASS</strong></td>
                    <td>{{ $session->class_group }}</td>
                    <td><strong>DATE</strong></td>
                    <td>{{ $session->assessment_date?->format('d/m/Y') }}</td>
                </tr>
            </table>

            <table>
                <thead>
                    <tr>
                        <th style="width: 22%;">CRITERIA</th>
                        @foreach ($rubric->levels as $level)
                            <th>{{ $level->label }}<br>({{ number_format((float) $level->value, 0) }})</th>
                        @endforeach
                        <th style="width: 9%; text-align: center;">WEIGHT</th>
                        <th style="width: 9%; text-align: center;">SCORE</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rubric->criteria as $criterion)
                        @php
                            $descriptors = $criterion->descriptors->keyBy('level_id');
                            $score = $scores->get($criterion->id);
                        @endphp
                        <tr>
                            <td><strong>{{ $criterion->name }}</strong></td>
                            @foreach ($rubric->levels as $level)
                                @php($selected = $score && (float) $score->level_value === (float) $level->value)
                                <td class="{{ $selected ? 'selected' : '' }}">
                                    {{ $descriptors->get($level->id)?->description }}
                                    @if ($selected)
                                        <br><strong>Selected: {{ number_format((float) $level->value, 0) }}</strong>
                                    @endif
                                </td>
                            @endforeach
                            <td style="text-align: center;">{{ number_format((float) $criterion->weight, 2) }}</td>
                            <td style="text-align: center;"><strong>{{ $score ? number_format($service->criterionScore($rubric, $criterion, $score->level_value) ?? 0, 2) : '' }}</strong></td>
                        </tr>
                    @endforeach
                    <tr class="total">
                        <td colspan="{{ $rubric->levels->count() + 2 }}" style="text-align: right;">TOTAL MARKS</td>
                        <td style="text-align: center;">{{ $summary['graded'] ? number_format($summary['total'], 2) : '' }} / {{ number_format($totalWeight, 2) }}</td>
                    </tr>
                </tbody>
            </table>

            @if ($student->remarks)
                <p style="font-size: 11px;"><strong>Remarks:</strong> {{ $student->remarks }}</p>
            @endif

            @if ($signatures['prepared'] || $signatures['verified'] || $signatures['approved'])
                <table class="sign">
                    <tr>
                        @if ($signatures['prepared'])
                            <td>Prepared By:<br><br>____________________________</td>
                        @endif
                        @if ($signatures['verified'])
                            <td>Verified By:<br><br>____________________________</td>
                        @endif
                        @if ($signatures['approved'])
                            <td>Approved By:<br><br>____________________________</td>
                        @endif
                    </tr>
                </table>
            @endif
        </section>
    @endforeach
</body>
</html>
