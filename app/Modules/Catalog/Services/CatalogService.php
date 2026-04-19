<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Contracts\CatalogServiceInterface;
use App\Modules\Catalog\DTOs\CatalogCourseDetailDTO;
use App\Modules\Catalog\DTOs\CatalogCourseDTO;
use App\Modules\Catalog\DTOs\CatalogLessonOutlineDTO;
use App\Modules\Catalog\DTOs\CatalogModuleOutlineDTO;
use App\Modules\Catalog\DTOs\PaginatedCoursesDTO;
use App\Modules\Course\Models\Course;
use App\Modules\Course\Models\CourseModule;
use App\Modules\Course\Models\Lesson;
use App\Shared\Exceptions\EntityNotFoundException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CatalogService implements CatalogServiceInterface
{
    private const int PER_PAGE = 15;

    private const int CACHE_TTL_SECONDS = 300; // 5 minutes

    public function browse(int $page = 1, ?string $category = null, ?string $sortBy = null): PaginatedCoursesDTO
    {
        $cacheKey = "catalog:browse:page={$page}:cat=" . ($category ?? 'all') . ":sort=" . ($sortBy ?? 'recent');

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($page, $category, $sortBy): PaginatedCoursesDTO {
            $query = Course::query()
                ->where('status', 'published')
                ->with('instructor');

            if ($category !== null && $category !== '') {
                $query->where('category', $category);
            }

            match ($sortBy) {
                'title' => $query->orderBy('title'),
                'oldest' => $query->orderBy('published_at', 'asc'),
                default => $query->orderByDesc('published_at'),
            };

            $paginator = $query->paginate(self::PER_PAGE, ['*'], 'page', $page);

            $courses = collect($paginator->items())->map(
                fn (Course $course): CatalogCourseDTO => $this->toCatalogCourseDTO($course),
            )->all();

            return new PaginatedCoursesDTO(
                data: $courses,
                currentPage: $paginator->currentPage(),
                lastPage: $paginator->lastPage(),
                perPage: $paginator->perPage(),
                total: $paginator->total(),
            );
        });
    }

    public function search(string $query, int $page = 1): PaginatedCoursesDTO
    {
        $cacheKey = 'catalog:search:q=' . md5($query) . ":page={$page}";

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($query, $page): PaginatedCoursesDTO {
            $searchTerm = '%' . $query . '%';

            $dbQuery = Course::query()
                ->where('status', 'published')
                ->where(function ($q) use ($searchTerm): void {
                    $q->where('title', 'ilike', $searchTerm)
                        ->orWhere('description', 'ilike', $searchTerm)
                        ->orWhere('category', 'ilike', $searchTerm);
                })
                ->with('instructor')
                ->orderByDesc('published_at');

            $paginator = $dbQuery->paginate(self::PER_PAGE, ['*'], 'page', $page);

            $courses = collect($paginator->items())->map(
                fn (Course $course): CatalogCourseDTO => $this->toCatalogCourseDTO($course),
            )->all();

            return new PaginatedCoursesDTO(
                data: $courses,
                currentPage: $paginator->currentPage(),
                lastPage: $paginator->lastPage(),
                perPage: $paginator->perPage(),
                total: $paginator->total(),
            );
        });
    }

    public function getCourseDetail(int $courseId): CatalogCourseDetailDTO
    {
        $cacheKey = "catalog:detail:{$courseId}";

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($courseId): CatalogCourseDetailDTO {
            $course = Course::with(['instructor', 'modules.lessons'])
                ->where('status', 'published')
                ->find($courseId);

            if ($course === null) {
                throw new EntityNotFoundException('Course not found.');
            }

            $modules = $course->modules
                ->sortBy('sort_order')
                ->values()
                ->map(function (CourseModule $module): CatalogModuleOutlineDTO {
                    $lessons = $module->lessons
                        ->sortBy('sort_order')
                        ->values()
                        ->map(fn (Lesson $lesson): CatalogLessonOutlineDTO => new CatalogLessonOutlineDTO(
                            id: $lesson->id,
                            title: $lesson->title,
                            contentType: $lesson->content_type,
                            sortOrder: $lesson->sort_order,
                        ))
                        ->all();

                    return new CatalogModuleOutlineDTO(
                        id: $module->id,
                        title: $module->title,
                        sortOrder: $module->sort_order,
                        lessons: $lessons,
                    );
                })
                ->all();

            $enrollmentCount = $course->enrollments()->count();

            // Average rating placeholder — ratings table not yet implemented
            $averageRating = null;

            return new CatalogCourseDetailDTO(
                id: $course->id,
                title: $course->title,
                description: $course->description,
                category: $course->category,
                instructorName: $course->instructor->name,
                instructorBio: $course->instructor->bio ?? null,
                publishedAt: $course->published_at->toIso8601String(),
                modules: $modules,
                enrollmentCount: $enrollmentCount,
                averageRating: $averageRating,
            );
        });
    }

    private function toCatalogCourseDTO(Course $course): CatalogCourseDTO
    {
        return new CatalogCourseDTO(
            id: $course->id,
            title: $course->title,
            descriptionSummary: Str::limit($course->description, 200),
            instructorName: $course->instructor->name,
            category: $course->category,
            publishedAt: $course->published_at->toIso8601String(),
        );
    }
}
