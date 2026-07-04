<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Services\AnnouncementAudienceService;
use App\Services\MemberCapabilityService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AnnouncementController extends Controller
{
    public function __construct(private readonly AnnouncementAudienceService $audiences)
    {
    }

    public function index(Request $request)
    {
        $profileData = $request->user();
        $announcements = $this->audiences->manageableBy($profileData)->paginate(20);
        $pageTitle = 'Announcements';

        return view('backend.announcements.index', compact('profileData', 'announcements', 'pageTitle'));
    }

    public function create(Request $request)
    {
        $profileData = $request->user();
        $scopeLabel = $this->audiences->publicationScopeLabel($profileData);
        $pageTitle = 'Create Announcement';

        return view('backend.announcements.create', compact('profileData', 'scopeLabel', 'pageTitle'));
    }

    public function store(Request $request)
    {
        $profileData = $request->user();
        $validated = $this->validated($request);
        $validated['created_by'] = $profileData->id;
        $validated = array_replace($validated, $this->audiences->publicationScope($profileData));
        $validated['published_at'] ??= now();

        Announcement::query()->create($validated);

        return redirect()->route($profileData->access_level.'.announcements.index')
            ->with('success', 'Announcement created successfully.');
    }

    public function edit(Request $request, Announcement $announcement)
    {
        $profileData = $request->user();
        abort_unless($this->audiences->canManage($profileData, $announcement), 403);
        $scopeLabel = $this->audiences->scopeLabel($announcement);
        $pageTitle = 'Edit Announcement';

        return view('backend.announcements.edit', compact('profileData', 'announcement', 'scopeLabel', 'pageTitle'));
    }

    public function update(Request $request, Announcement $announcement)
    {
        $profileData = $request->user();
        abort_unless($this->audiences->canManage($profileData, $announcement), 403);
        $announcement->update($this->validated($request));

        return redirect()->route($profileData->access_level.'.announcements.index')
            ->with('success', 'Announcement updated successfully.');
    }

    public function destroy(Request $request, Announcement $announcement)
    {
        $profileData = $request->user();
        abort_unless($this->audiences->canManage($profileData, $announcement), 403);
        $announcement->delete();

        return redirect()->route($profileData->access_level.'.announcements.index')
            ->with('success', 'Announcement deleted successfully.');
    }

    public function noticeIndex(Request $request)
    {
        $profileData = $request->user();
        $this->assertCanRead($profileData);
        $announcements = $this->audiences->visibleToMember($profileData)->paginate(15);
        $pageTitle = 'Notice Board';

        return view('backend.user.notice-board', compact('profileData', 'announcements', 'pageTitle'));
    }

    public function noticeShow(Request $request, Announcement $announcement)
    {
        $profileData = $request->user();
        $this->assertCanRead($profileData);
        $announcement = $this->audiences->visibleToMember($profileData)->findOrFail($announcement->id);
        $pageTitle = $announcement->title;

        return view('backend.user.notice-show', compact('profileData', 'announcement', 'pageTitle'));
    }

    private function assertCanRead(\App\Models\User $user): void
    {
        $allowed = $user->access_level === 'user'
            ? app(MemberCapabilityService::class)->allows($user, MemberCapabilityService::NOTICE_BOARD)
            : $this->audiences->canPublish($user);

        abort_unless($allowed, 403);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:20000'],
            'priority' => ['required', 'integer', Rule::in([0, 1, 2, 3])],
            'is_active' => ['required', 'boolean'],
            'published_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:published_at'],
        ]);
    }
}
