@if(!empty($rbacPackageMode))
    <div class="alert alert-info">
        <strong>Package-aware RBAC:</strong>
        Showing roles available for {{ $rbacPackageDisplay ?? 'this campaign package' }}
        @if(!empty($rbacScopeDisplay))
            / {{ $rbacScopeDisplay }}
        @endif.
        Roles from other campaign packages are hidden but not deleted.
    </div>
@endif
