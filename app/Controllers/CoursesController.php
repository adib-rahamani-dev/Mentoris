<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\PublicContentService;

final class CoursesController extends Controller
{
    public function index(Request $request): Response
    {
        $courses = array_map(fn (array $course): array => PublicContentService::course($course['slug']) ?? $course, PublicContentService::courses());
        return $this->view('pages.courses', [
            'title' => t('nav.courses') . ' | Mentoris Academy',
            'description' => t('empty.text'),
            'indexable' => $courses !== [],
            'courses' => $courses,
            'categories' => PublicContentService::courseCategories(),
            'statuses' => PublicContentService::courseStatusLabels(),
        ]);
    }

    public function show(Request $request, string $slug): Response
    {
        $course = PublicContentService::course($slug);
        if ($course === null) {
            return Response::html('<h1>404 - Course Not Found</h1>', 404);
        }
        return $this->view('pages.course-details', [
            'title' => $course['title'] . ' | Courses',
            'description' => $course['description'],
            'course' => $course,
        ]);
    }
}
