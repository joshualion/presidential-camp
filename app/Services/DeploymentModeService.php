<?php

namespace App\Services;

class DeploymentModeService
{
    public const MANAGED = 'managed';
    public const SELF_HOSTED = 'self_hosted';

    public function mode(): string
    {
        $mode = strtolower((string) config('campaign.deployment_mode', self::MANAGED));

        return in_array($mode, [self::MANAGED, self::SELF_HOSTED], true) ? $mode : self::MANAGED;
    }

    public function isManaged(): bool
    {
        return $this->mode() === self::MANAGED;
    }

    public function isSelfHosted(): bool
    {
        return $this->mode() === self::SELF_HOSTED;
    }

    public function installerEnabled(): bool
    {
        return filter_var(config('campaign.installer_enabled', true), FILTER_VALIDATE_BOOLEAN);
    }

    public function installerShouldRedirect(): bool
    {
        return $this->isSelfHosted() && $this->installerEnabled();
    }
}
