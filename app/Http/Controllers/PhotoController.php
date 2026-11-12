<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Support\Facades\Storage;

class PhotoController extends Controller
{
    public function student(Student $student)
    {
        $this->authorize('viewPhoto', $student);
        abort_unless($student->photo_path, 404);

        $response = Storage::disk('local')->response($student->photo_path);
        $response->headers->set('Cache-Control', 'private, max-age=3600');

        return $response;
    }

    public function teacher(Teacher $teacher)
    {
        $this->authorize('viewPhoto', $teacher);
        abort_unless($teacher->photo_path, 404);

        $response = Storage::disk('local')->response($teacher->photo_path);
        $response->headers->set('Cache-Control', 'private, max-age=3600');

        return $response;
    }
}
