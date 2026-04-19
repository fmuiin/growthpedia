<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Contracts;

use App\Modules\Catalog\DTOs\CatalogCourseDetailDTO;
use App\Modules\Catalog\DTOs\PaginatedCoursesDTO;
use App\Shared\Contracts\ServiceInterface;

interface CatalogServiceInterface extends ServiceInterface
{
    public function browse(int $page = 1, ?string $category = null, ?string $sortBy = null): PaginatedCoursesDTO;

    public function search(string $query, int $page = 1): PaginatedCoursesDTO;

    public function getCourseDetail(int $courseId): CatalogCourseDetailDTO;
}
