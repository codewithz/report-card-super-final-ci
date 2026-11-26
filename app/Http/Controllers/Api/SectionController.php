<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClassListResource;
use App\Http\Resources\SectionResource;
use App\Models\Section;
use App\Services\ReportCardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SectionController extends Controller
{
    public function index(Request $request)
    {
        $sections = Section::visibleTo($request->user())
            ->withCount('students')
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get();

        return SectionResource::collection($sections);
    }

    // Every student in the section, with their general average and remark.
    public function students(Section $section, ReportCardService $reportCards)
    {
        Gate::authorize('view', $section);

        return ClassListResource::collection($reportCards->classList($section));
    }
}
