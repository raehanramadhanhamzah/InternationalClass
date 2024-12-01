<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudyProgram extends Model
{
    protected $fillable = [
        'study_program_Name',
        'degree',
        'study_program_Description',
        'International_Accreditation',
        'study_program_Image',
        'ID_Faculty'
    ];

    public function faculty()
    {
        return $this->belongsTo(Faculty::class, 'ID_Faculty');
    }
    public function programs()
    {
        return $this->hasMany(Program::class, 'ID_study_program');
    }
    public function staff()
    {
        return $this->hasMany(Staff::class, 'ID_study_program'); 
    }
}