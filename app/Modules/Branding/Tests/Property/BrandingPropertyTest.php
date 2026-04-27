<?php

declare(strict_types=1);

namespace App\Modules\Branding\Tests\Property;

use App\Models\User;
use App\Modules\Branding\DTOs\UpdateCreatorProfileDTO;
use App\Modules\Branding\DTOs\UpdatePlatformBrandingDTO;
use App\Modules\Branding\Models\CreatorProfile;
use App\Modules\Branding\Models\LandingPageSection;
use App\Modules\Branding\Models\PlatformBranding;
use App\Modules\Branding\Services\BrandingService;
use Eris\Generators;
use Eris\TestTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class BrandingPropertyTest extends TestCase
{
    use RefreshDatabase;
    use TestTrait;

    /**
     * Property 1: Update-then-read cycle for creator profile.
     * For any valid display name (non-empty, max 255 chars) and bio (max 5000 chars),
     * updating the creator profile and then reading it back returns the updated values.
     *
     * **Validates: Requirements 3.2**
     */
    public function test_update_then_read_cycle_preserves_creator_profile_values(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->limitTo(25);

        $this->forAll(
            Generators::suchThat(
                fn ($s) => strlen($s) > 0 && strlen($s) <= 255,
                Generators::string()
            ),
            Generators::suchThat(
                fn ($s) => strlen($s) <= 5000,
                Generators::string()
            )
        )->then(function ($displayName, $bio) {
            Cache::flush();
            CreatorProfile::query()->delete();

            $service = new BrandingService();

            $dto = new UpdateCreatorProfileDTO(
                displayName: $displayName,
                bio: $bio,
            );

            $service->updateCreatorProfile($dto);
            Cache::flush();
            $result = $service->getCreatorProfile();

            $this->assertEquals($displayName, $result->displayName);
            $this->assertEquals($bio, $result->bio);
        });
    }

    /**
     * Property 2: Landing page sections sorted ascending by sort_order.
     * For any set of landing page sections with unique sort_orders,
     * getLandingPageContent returns them sorted in ascending order.
     *
     * **Validates: Requirements 4.2**
     */
    public function test_landing_page_sections_always_sorted_by_sort_order(): void
    {
        $this->limitTo(25);

        $this->forAll(
            Generators::set(Generators::choose(1, 1000))
        )->then(function ($sortOrders) {
            if (empty($sortOrders)) {
                return;
            }

            Cache::flush();
            LandingPageSection::query()->delete();

            $sortOrdersArray = array_values($sortOrders);
            shuffle($sortOrdersArray);

            foreach ($sortOrdersArray as $order) {
                LandingPageSection::create([
                    'section_type' => 'hero',
                    'title' => "Section {$order}",
                    'sort_order' => $order,
                    'is_visible' => true,
                ]);
            }

            $service = new BrandingService();
            $result = $service->getLandingPageContent();

            $resultOrders = array_map(fn ($s) => $s->sortOrder, $result->sections);

            $sorted = $resultOrders;
            sort($sorted);
            $this->assertEquals($sorted, $resultOrders);
        });
    }

    /**
     * Property 3: Singleton constraint for creator_profiles.
     * No matter how many times getCreatorProfile is called (even with cache cleared),
     * there is always at most 1 creator_profiles record.
     *
     * **Validates: Requirements 3.5**
     */
    public function test_creator_profile_singleton_constraint(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->limitTo(25);

        $this->forAll(
            Generators::choose(1, 5)
        )->then(function ($callCount) {
            Cache::flush();
            CreatorProfile::query()->delete();

            $service = new BrandingService();

            for ($i = 0; $i < $callCount; $i++) {
                Cache::flush();
                $service->getCreatorProfile();
            }

            $this->assertLessThanOrEqual(1, CreatorProfile::count());
        });
    }

    /**
     * Property 4: Singleton constraint for platform_brandings.
     * After any number of updatePlatformBranding calls, there is always at most 1
     * platform_brandings record.
     *
     * **Validates: Requirements 5.2**
     */
    public function test_platform_branding_singleton_constraint(): void
    {
        $this->limitTo(25);

        $this->forAll(
            Generators::choose(1, 5),
            Generators::suchThat(
                fn ($s) => strlen($s) > 0 && strlen($s) <= 255,
                Generators::string()
            )
        )->then(function ($callCount, $siteName) {
            Cache::flush();
            PlatformBranding::query()->delete();

            $service = new BrandingService();

            for ($i = 0; $i < $callCount; $i++) {
                Cache::flush();
                $dto = new UpdatePlatformBrandingDTO(
                    siteName: $siteName . "_{$i}",
                );
                $service->updatePlatformBranding($dto);
            }

            $this->assertLessThanOrEqual(1, PlatformBranding::count());
        });
    }
}
