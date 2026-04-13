<?php

declare(strict_types=1);

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Contracts\MembershipPlanServiceInterface;
use App\Modules\Admin\DTOs\CreateMembershipPlanDTO;
use App\Modules\Admin\DTOs\UpdateMembershipPlanDTO;
use App\Modules\Admin\Exceptions\PlanHasActiveSubscriptionsException;
use App\Modules\Admin\Requests\CreateMembershipPlanRequest;
use App\Modules\Admin\Requests\UpdateMembershipPlanRequest;
use App\Modules\Course\Models\Course;
use App\Modules\Subscription\Models\MembershipPlan;
use App\Shared\Exceptions\EntityNotFoundException;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MembershipPlanController extends Controller
{
    public function __construct(
        private readonly MembershipPlanServiceInterface $membershipPlanService,
    ) {}

    public function index(): Response
    {
        $plans = MembershipPlan::with('courses')
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('Admin/MembershipPlanList', [
            'plans' => $plans,
        ]);
    }

    public function create(): Response
    {
        $courses = Course::where('status', 'published')
            ->orderBy('title')
            ->get(['id', 'title']);

        return Inertia::render('Admin/MembershipPlanCreate', [
            'courses' => $courses,
        ]);
    }

    public function store(CreateMembershipPlanRequest $request): RedirectResponse
    {
        $dto = new CreateMembershipPlanDTO(
            name: $request->validated('name'),
            description: $request->validated('description'),
            price: (string) $request->validated('price'),
            billingFrequency: $request->validated('billing_frequency'),
            courseIds: $request->validated('course_ids', []),
        );

        $plan = $this->membershipPlanService->createPlan($dto);

        return redirect()->route('admin.plans.index')
            ->with('success', 'Membership plan created successfully.');
    }

    public function edit(int $planId): Response|RedirectResponse
    {
        $plan = MembershipPlan::with('courses')->find($planId);

        if ($plan === null) {
            return redirect()->route('admin.plans.index')
                ->with('error', 'Membership plan not found.');
        }

        $courses = Course::where('status', 'published')
            ->orderBy('title')
            ->get(['id', 'title']);

        return Inertia::render('Admin/MembershipPlanEdit', [
            'plan' => $plan,
            'courses' => $courses,
        ]);
    }

    public function update(int $planId, UpdateMembershipPlanRequest $request): RedirectResponse
    {
        try {
            $dto = new UpdateMembershipPlanDTO(
                name: $request->validated('name'),
                description: $request->validated('description'),
                price: $request->validated('price') !== null ? (string) $request->validated('price') : null,
                billingFrequency: $request->validated('billing_frequency'),
                courseIds: $request->validated('course_ids'),
            );

            $this->membershipPlanService->updatePlan($planId, $dto);
        } catch (EntityNotFoundException $e) {
            return redirect()->route('admin.plans.index')
                ->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Membership plan updated successfully.');
    }

    public function deactivate(int $planId): RedirectResponse
    {
        try {
            $this->membershipPlanService->deactivatePlan($planId);
        } catch (EntityNotFoundException $e) {
            return redirect()->route('admin.plans.index')
                ->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Membership plan deactivated successfully.');
    }

    public function destroy(int $planId): RedirectResponse
    {
        try {
            $this->membershipPlanService->deletePlan($planId);
        } catch (PlanHasActiveSubscriptionsException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        } catch (EntityNotFoundException $e) {
            return redirect()->route('admin.plans.index')
                ->with('error', $e->getMessage());
        }

        return redirect()->route('admin.plans.index')
            ->with('success', 'Membership plan deleted successfully.');
    }
}
