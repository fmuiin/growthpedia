<?php

declare(strict_types=1);

namespace App\Modules\Discussion\Providers;

use App\Modules\Discussion\Contracts\DiscussionServiceInterface;
use App\Modules\Discussion\Events\CommentFlagged;
use App\Modules\Discussion\Listeners\CommentFlaggedListener;
use App\Modules\Discussion\Services\DiscussionService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class DiscussionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(DiscussionServiceInterface::class, DiscussionService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');

        Event::listen(CommentFlagged::class, CommentFlaggedListener::class);
    }
}
