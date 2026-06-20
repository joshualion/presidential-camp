<?php

namespace App\Services;

class ModuleGateService
{
    public function __construct(private PackageScopeService $scope)
    {
    }

    public function enabled(string $moduleKey): bool
    {
        $moduleKey = $this->normalize($moduleKey);

        if (!$this->scope->hasLocalLicense()) {
            return $this->scope->isManaged() || !$this->scope->isSelfHosted();
        }

        return in_array($moduleKey, array_map([$this, 'normalize'], $this->scope->enabledModules()), true);
    }

    public function disabled(string $moduleKey): bool
    {
        return !$this->enabled($moduleKey);
    }

    public function requireEnabled(string $moduleKey): void
    {
        abort_if($this->disabled($moduleKey), 403, 'This module is not enabled for the current license.');
    }

    private function normalize(string $moduleKey): string
    {
        return strtolower(trim(str_replace(['-', ' '], '_', $moduleKey)));
    }
}
