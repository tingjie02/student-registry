<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Http\Resources\StudentResource;
use App\Imports\StudentsImport;
use Maatwebsite\Excel\Facades\Excel;

class StudentController extends Controller
{
    /**
     * Display a paginated list of students.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $students = Student::paginate($perPage);
        return StudentResource::collection($students);
    }

    /**
     * Store a newly created student in the database.
     */
    public function store(Request $request)
    {
        // Validate input data with rules
        $validatedData = $request->validate([
            'name' => 'required|max:255',
            'email' => 'required|max:255|email|unique:students,email',
            'address' => 'required',
            'study_course' => 'required|max:255',
        ]);

        // Process input data
        $validatedData['email'] = strtolower($validatedData['email']);

        // Create student
        $student = Student::create($validatedData);
        return new StudentResource($student);
    }

    /**
     * Display the specified student.
     */
    public function show(string $id)
    {
        // Find student by id
        $student = Student::find($id);
        return new StudentResource($student);
    }

    /**
     * Update the specified student in the database.
     */
    public function update(Request $request, string $id)
    {
        // Validate input data with rules
        $validatedData = $request->validate([
            'name' => 'string|max:255',
            'email' => 'email|max:255|unique:students,email,'.$id,
            'address' => 'string',
            'study_course' => 'string|max:255',
        ]);

        // Check if email is provided
        if(isset($validatedData['email'])) {
            $validatedData['email'] = strtolower($validatedData['email']);
        }

        // Find student by id
        $student = Student::find($id);

        // Check if student exists
        if (!$student) {
            return response()->json(['status' => 'error', 'message' => 'Student not found'], 404);
        }

        // Update student
        $student->update($request->all());
        return new StudentResource($student);
    }

    /**
     * Remove the specified student from the database.
     */
    public function destroy(Request $request, $id = null)
    {
        // Check if id is provided
        if ($id) {
            return $this->attemptDeletion(['id' => $id]);
        }

        // Check if email is provided
        if ($email = $request->query('email')) {
            return $this->attemptDeletion(['email' => $email]);
        }

        return response()->json(['status' => 'error', 'message' => 'No deletion criteria provided'], 400);
    }

    /**
     * Remove the specified student from the database.
     */
    public function search(Request $request)
    {
        // Get input data
        $name = $request->input('name');
        $email = $request->input('email');

        // Prepare query
        $query = Student::query();

        // Check if name is provided
        if ($name) {
            $query->where('name', '=', $name);
        }

        // Check if email is provided
        if ($email) {
            $query->orWhere('email', '=', strtolower($email));
        }

        // Get students
        $students = $query->get();
        return StudentResource::collection($students);
    }

    /**
     * Import students from a file.
     */
    public function import(Request $request)
    {
        // Validate input data with rules
        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv'
        ]);

        // Process input data
        $import = new StudentsImport();

        // Import students
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

        // Check if there were errors in the import process
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
        // Find student by id or email
        $student = Student::where($query)->first();

        // Check if student exists
        if (!$student) {
            return response()->json(['status' => 'error', 'message' => 'Student not found'], 404);
        }

        // Delete student
        $student->delete();
        return response()->json(['status' => 'success', 'message' => 'Deleted successfully'], 200);
    }
}