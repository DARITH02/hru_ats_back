<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'student_code', 'group_id', 'major_id', 'year_level', 'status'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function group()
    {
        return $this->belongsTo(ClassGroup::class, 'group_id');
    }

    public function major()
    {
        return $this->belongsTo(Major::class);
    }

    public function classRoom()
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }

    public function attendanceRecords()
    {
        return $this->hasMany(Attendance::class, 'student_id');
    }
}
