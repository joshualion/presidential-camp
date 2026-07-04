<?php

namespace App\Services;

use App\Models\User;

class MemberCapabilityService
{
    public const DASHBOARD = 'dashboard.view';
    public const PROFILE = 'profile.manage';
    public const ACCOUNT_SECURITY = 'account.security';
    public const NOTIFICATIONS = 'notifications.view';
    public const MESSAGING = 'messaging.use';
    public const COMMUNITY = 'community.use';
    public const AGENTS = 'agents.participate';
    public const DASHBOARD_ANNOUNCEMENTS = 'announcements.dashboard';
    public const VOTING_BLOCK = 'voting_block.view';
    public const REFERRALS = 'referrals.view';
    public const POLLING_UNIT_DETAILS = 'polling_unit.details';
    public const NOTICE_BOARD = 'announcements.board';
    public const ELECTION_REPORTS = 'elections.reports';
    public const ELECTION_SUBMISSION = 'elections.submit';

    public function __construct(
        private ModuleGateService $modules,
        private CommunityRealtimeService $community,
        private PublicRegistrationService $publicRegistration,
        private PollingUnitResultPermissionService $resultPermissions,
    ) {
    }

    public function catalog(): array
    {
        return (array) config('member_access.capabilities', []);
    }

    public function definition(string $capability): ?array
    {
        $definition = $this->catalog()[$capability] ?? null;

        return is_array($definition) ? $definition : null;
    }

    public function status(string $capability): ?string
    {
        return $this->definition($capability)['status'] ?? null;
    }

    public function allows(?User $user, string $capability): bool
    {
        if (!$this->isMember($user) || $user->status === 'inactive') {
            return false;
        }

        $definition = $this->definition($capability);
        if (!$definition || ($definition['status'] ?? null) !== 'available') {
            return false;
        }

        if (!empty($definition['module']) && $this->modules->disabled((string) $definition['module'])) {
            return false;
        }

        if (!$this->hasRequiredLocation($user, $definition['requires_location'] ?? null)) {
            return false;
        }

        return match ($definition['runtime_gate'] ?? null) {
            'community_enabled' => $this->community->enabled(),
            'public_registration_enabled' => $this->publicRegistration->enabled(),
            'approved_agent_assignment' => $this->modules->enabled('agents')
                && $this->resultPermissions->userHasAnyApprovedAssignment($user),
            default => true,
        };
    }

    public function denialMessage(string $capability): string
    {
        $definition = $this->definition($capability);

        if (!$definition) {
            return 'This member capability is not defined.';
        }

        if (($definition['status'] ?? null) !== 'available') {
            return ($definition['label'] ?? 'This member feature').' is not available yet.';
        }

        if (!empty($definition['module']) && $this->modules->disabled((string) $definition['module'])) {
            return ($definition['label'] ?? 'This member feature').' is not enabled for this license.';
        }

        if (($definition['runtime_gate'] ?? null) === 'public_registration_enabled' && !$this->publicRegistration->enabled()) {
            return ($definition['label'] ?? 'This member feature').' is disabled while public registration is closed.';
        }

        if (($definition['runtime_gate'] ?? null) === 'approved_agent_assignment') {
            return 'Election submission requires an active, approved polling-unit agent assignment.';
        }

        return 'Your member account does not currently meet the requirements for this feature.';
    }

    public function isMember(?User $user): bool
    {
        return $user?->access_level === 'user';
    }

    private function hasRequiredLocation(User $user, mixed $requirement): bool
    {
        if (!$requirement) {
            return true;
        }

        foreach ((array) $requirement as $attribute) {
            if (empty($user->{$attribute})) {
                return false;
            }
        }

        return true;
    }
}
