<?php

namespace App\Imports;

use App\Models\Student;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StudentsImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */

    use Importable;

    public function model(array $row)
    {
        $action = strtolower($row['action']);

        if ($action === 'create') {
            return new Student([
                'name' => $row['name'],
                'email' => $row['email'],
                'address' => $row['address'],
                'study_course' => $row['study_course'],
            ]);
        } elseif ($action === 'update') {
            $student = Student::where('email', $row['email'])->first();
            if ($student) {
                $student->update([
                    'name' => $row['name'],
                    'address' => $row['address'],
                    'study_course' => $row['study_course'],
                ]);
            }
            return $student;
        } elseif ($action === 'delete') {
            Student::where('email', $row['email'])->delete();
            return null;
        }
    }
}
