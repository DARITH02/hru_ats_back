<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassRoom extends Model
{
    use HasFactory;

    protected $table = 'classes';

    protected $fillable = ['name', 'subject_id', 'teacher_id', 'room_number', 'schedule', 'status', 'academic_year', 'semester', 'group_id'];

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function groups()
    {
        return $this->belongsToMany(ClassGroup::class, 'class_class_group', 'class_room_id', 'class_group_id');
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'group_id', 'group_id');
    }

    public function sessions()
    {
        return $this->hasMany(AttendanceSession::class, 'class_id');
    }

    public function semesterAssignments()
    {
        return $this->hasMany(SemesterAssignment::class, 'class_id');
    }
}
