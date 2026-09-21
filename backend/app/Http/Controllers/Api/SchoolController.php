<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\School;

class SchoolController extends Controller
{
    public function index()
    {
        $schools = School::orderBy('name')->get();

        return response()->json($schools);
    }

    public function classes(School $school)
    {
        $classes = $school->classes()
            ->orderBy('level')
            ->orderBy('section')
            ->get();

        return response()->json($classes);
    }
}