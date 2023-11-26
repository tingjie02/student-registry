<?php

namespace App\Imports;

use App\Models\Student;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Throwable;

class StudentsImport implements ToModel, WithHeadingRow, SkipsOnError
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */

    use Importable, SkipsFailures;

    private $errors = [];

    public function model(array $row)
    {
        try {
            $action = strtolower(trim($row['action'] ?? ''));

            switch ($action) {
                case 'create':
                    return $this->createStudent($row);

                case 'update':
                    return $this->updateStudent($row);

                case 'delete':
                    $this->deleteStudent($row);
                    break;
            }
        } catch (Throwable $e) {
            $this->errors[] = $e->getMessage();
            return null;
        }
    }

    private function createStudent($row)
    {
        try {
            return new Student([
                'name'         => $row['name'],
                'email'        => $row['email'],
                'address'      => $row['address'],
                'study_course' => $row['study_course'],
            ]);
        } catch (Throwable $e) {
            $this->errors[] = $e->getMessage();
        }
    }

    private function updateStudent($row)
    {
        try {
            $student = Student::where('email', $row['email'])->first();
            if ($student) {
                $student->update([
                    'name'         => $row['name'],
                    'address'      => $row['address'],
                    'study_course' => $row['study_course'],
                ]);
            }
            return $student;
        } catch (Throwable $e) {
            $this->errors[] = $e->getMessage();
        }
    }

    private function deleteStudent($row)
    {
        try {
            Student::where('email', $row['email'])->delete();
        } catch (Throwable $e) {
            $this->errors[] = $e->getMessage();
        }
    }

    public function onError(Throwable $e)
    {
        $this->errors[] = $e->getMessage();
    }

    public function getErrors()
    {
        return $this->errors;
    }
}
