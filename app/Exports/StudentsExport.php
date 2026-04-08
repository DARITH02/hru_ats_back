<?php

namespace App\Exports;

use App\Models\Student;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StudentsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Student::with(['user', 'group', 'major'])->get();
    }

    public function headings(): array
    {
        return ['ID', 'Student Code', 'Name', 'Email', 'Group', 'Major', 'Year Level'];
    }

    public function map($student): array
    {
        return [
            $student->id,
            $student->student_code,
            $student->user->name,
            $student->user->email,
            $student->group->name ?? 'N/A',
            $student->major->name ?? 'N/A',
            $student->year_level
        ];
    }
}
