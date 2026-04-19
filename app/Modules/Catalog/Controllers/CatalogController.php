<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Contracts\CatalogServiceInterface;
use App\Shared\Exceptions\EntityNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CatalogController extends Controller
{
    public function __construct(
        private readonly CatalogServiceInterface $catalogService,
    ) {}

    public function index(Request $request): Response
    {
        $page = (int) $request->query('page', 1);
        $category = $request->query('category');
        $sortBy = $request->query('sort');

        $result = $this->catalogService->browse(
            page: $page,
            category: is_string($category) ? $category : null,
            sortBy: is_string($sortBy) ? $sortBy : null,
        );

        $categories = $this->getAvailableCategories();

        return Inertia::render('Catalog/CatalogIndex', [
            'courses' => $result,
            'filters' => [
                'category' => $category,
                'sort' => $sortBy,
            ],
            'categories' => $categories,
        ]);
    }

    public function search(Request $request): Response
    {
        $query = $request->query('q', '');
        $page = (int) $request->query('page', 1);

        if (! is_string($query) || $query === '') {
            return Inertia::render('Catalog/CatalogIndex', [
                'courses' => [
                    'data' => [],
                    'currentPage' => 1,
                    'lastPage' => 1,
                    'perPage' => 15,
                    'total' => 0,
                ],
                'filters' => [
                    'category' => null,
                    'sort' => null,
                ],
                'categories' => $this->getAvailableCategories(),
                'searchQuery' => '',
            ]);
        }

        $result = $this->catalogService->search(query: $query, page: $page);

        return Inertia::render('Catalog/CatalogIndex', [
            'courses' => $result,
            'filters' => [
                'category' => null,
                'sort' => null,
            ],
            'categories' => $this->getAvailableCategories(),
            'searchQuery' => $query,
        ]);
    }

    public function show(int $courseId): Response|RedirectResponse
    {
        try {
            $courseDetail = $this->catalogService->getCourseDetail($courseId);
        } catch (EntityNotFoundException) {
            return redirect()->route('catalog.index')
                ->with('error', 'Course not found.');
        }

        return Inertia::render('Catalog/CourseDetail', [
            'course' => $courseDetail,
        ]);
    }

    /**
     * @return array<string>
     */
    private function getAvailableCategories(): array
    {
        return \App\Modules\Course\Models\Course::query()
            ->where('status', 'published')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->all();
    }
}
