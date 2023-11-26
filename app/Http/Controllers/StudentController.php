<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Http\Resources\StudentResource;
use App\Imports\StudentsImport;
use Maatwebsite\Excel\Facades\Excel;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $students = Student::paginate($perPage);
        return StudentResource::collection($students);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|max:255',
            'email' => 'required|max:255|email|unique:students,email',
            'address' => 'required',
            'study_course' => 'required|max:255',
        ]);

        $validatedData['email'] = strtolower($validatedData['email']);

        $student = Student::create($validatedData);
        return new StudentResource($student);
    }

    public function show(string $id)
    {
        $student = Student::find($id);
        return new StudentResource($student);
    }

    public function update(Request $request, string $id)
    {
        $validatedData = $request->validate([
            'name' => 'string|max:255',
            'email' => 'email|max:255|unique:students,email,'.$id,
            'address' => 'string',
            'study_course' => 'string|max:255',
        ]);

        if(isset($validatedData['email'])) {
            $validatedData['email'] = strtolower($validatedData['email']);
        }

        $student = Student::find($id);

        if (!$student) {
            return response()->json(['status' => 'error', 'message' => 'Student not found'], 404);
        }

        $student->update($request->all());
        return new StudentResource($student);
    }

    public function destroy(Request $request, $id = null)
    {
        if ($id) {
            return $this->attemptDeletion(['id' => $id]);
        }

        if ($email = $request->query('email')) {
            return $this->attemptDeletion(['email' => $email]);
        }

        return response()->json(['status' => 'error', 'message' => 'No deletion criteria provided'], 400);
    }

    public function search(Request $request)
    {
        $name = $request->input('name');
        $email = $request->input('email');

        $query = Student::query();

        if ($name) {
            $query->where('name', '=', $name);
        }

        if ($email) {
            $query->orWhere('email', '=', strtolower($email));
        }

        $students = $query->get();
        return StudentResource::collection($students);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv'
        ]);

        $import = new StudentsImport();

        try {
            Excel::import($import, $request->file('file'));
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            return response()->json([
                'status' => 'error',
                'message' => 'Validation errors in the import process',
                'errors' => $failures
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'There was an error in the import process',
                'errors' => $e->getMessage()
            ], 500);
        }

        if (!empty($import->getErrors())) {
            return response()->json([
                'status' => 'error',
                'message' => 'There were errors in the import process',
                'errors' => $import->getErrors()
            ], 422);
        }

        return response()->json(['status' => 'success', 'message' => 'Imported successfully'], 200);
    }

    private function attemptDeletion($query)
    {
        $student = Student::where($query)->first();

        if (!$student) {
            return response()->json(['status' => 'error', 'message' => 'Student not found'], 404);
        }

        $student->delete();
        return response()->json(['status' => 'success', 'message' => 'Deleted successfully'], 200);
    }
}