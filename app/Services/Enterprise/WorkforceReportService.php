<?php

namespace App\Services\Enterprise;

use App\Models\PollingUnit;
use App\Models\User;
use App\Services\LocationScopeService;
use Illuminate\Database\Eloquent\Builder;

class WorkforceReportService
{
    public const GROUPS = [
        'state' => ['state_id', 'states'],
        'lga' => ['lga_id', 'local_government_areas'],
        'ward' => ['ward_id', 'wards'],
        'polling_unit' => ['polling_unit_id', 'polling_units'],
    ];

    private const BOUNDARIES = [
        'state_id' => 'state', 'lga_id' => 'lga',
        'ward_id' => 'ward', 'polling_unit_id' => 'polling_unit',
    ];

    public function __construct(private LocationScopeService $scope) {}

    public function members(User $viewer, array $filters = []): Builder
    {
        $query = User::query()->where('users.access_level', '!=', 'superadmin');
        $this->scope->applyScope($query, $viewer, 'users', 'users');
        $this->boundaries($query, $filters, 'users');
        if (!empty($filters['from'])) {
            $query->whereDate('users.created_at', '>=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $query->whereDate('users.created_at', '<=', $filters['to']);
        }

        return $query;
    }

    public function summary(User $viewer, array $filters = []): array
    {
        $members = $this->members($viewer, $filters);
        $units = PollingUnit::query();
        $this->scope->applyScope($units, $viewer, 'polling_units', 'polling_units');
        $this->boundaries($units, $filters, 'polling_units');
        $totalUnits = (clone $units)->count();
        $coveredUnits = (clone $units)->whereIn('polling_units.id',
            (clone $members)->whereNotNull('users.polling_unit_id')->select('users.polling_unit_id')
        )->count();

        return [
            'members' => (clone $members)->count(),
            'active' => (clone $members)->where('users.status', 'active')->count(),
            'registered_voters' => (clone $members)->whereRaw('LOWER(users.validVoter) = ?', ['yes'])->count(),
            'polling_units' => $totalUnits,
            'covered_polling_units' => $coveredUnits,
            'uncovered_polling_units' => max(0, $totalUnits - $coveredUnits),
            'coverage_percent' => $totalUnits > 0 ? round($coveredUnits * 100 / $totalUnits, 1) : 0,
        ];
    }

    public function distribution(User $viewer, array $filters = []): Builder
    {
        $group = $filters['group'] ?? 'state';
        if (!isset(self::GROUPS[$group])) {
            throw new \InvalidArgumentException('Unknown workforce report group.');
        }
        [$column, $table] = self::GROUPS[$group];

        return $this->members($viewer, $filters)
            ->leftJoin($table.' as report_location', 'users.'.$column, '=', 'report_location.id')
            ->select('users.'.$column.' as location_id', 'report_location.name as location_name')
            ->selectRaw('COUNT(*) as members')
            ->selectRaw("SUM(CASE WHEN users.status = 'active' THEN 1 ELSE 0 END) as active_members")
            ->selectRaw("SUM(CASE WHEN LOWER(users.validVoter) = 'yes' THEN 1 ELSE 0 END) as registered_voters")
            ->groupBy('users.'.$column, 'report_location.name')
            ->orderByDesc('members')->orderBy('users.'.$column);
    }

    /** All filters narrow an already authorized query; none replace its scope. */
    private function boundaries(Builder $query, array $filters, string $subject): void
    {
        foreach (self::BOUNDARIES as $key => $type) {
            if (!empty($filters[$key])) {
                $this->scope->applyBoundaryFilter($query, $type, (int) $filters[$key], $subject, $query->getModel()->getTable());
            }
        }
    }

    public static function csvCell(mixed $value): string
    {
        $value = (string) $value;
        // Prevent spreadsheet formula execution, including leading whitespace/control bytes.
        return preg_match('/^[\s\x00-\x1f]*[=+@-]/u', $value) ? "'".$value : $value;
    }
}
