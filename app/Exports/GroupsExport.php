<?php

namespace App\Exports;

use App\Models\ClassGroup;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class GroupsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return ClassGroup::with(['major.department'])->withCount('students')->get();
    }

    public function headings(): array
    {
        return ['ID', 'Group/Class Name', 'Major', 'Department', 'Year Level', 'Students Count'];
    }

    public function map($group): array
    {
        return [
            $group->id,
            $group->name,
            $group->major->name ?? 'N/A',
            $group->major->department->name ?? 'N/A',
            $group->year_level,
            $group->students_count
        ];
    }
}
