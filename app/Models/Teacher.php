<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'department_id', 'specialization', 'phone', 'status', 'telegram_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function classes()
    {
        return $this->hasMany(ClassRoom::class, 'teacher_id');
    }

    public function semesterAssignments()
    {
        return $this->hasManyThrough(SemesterAssignment::class, ClassRoom::class, 'teacher_id', 'class_id');
    }
}
