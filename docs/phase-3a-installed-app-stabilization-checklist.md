# Phase 3A Installed App Stabilization Checklist

## Route-safe navigation

- Test a temporary XAMPP smoke URL such as `http://localhost/otu/public`.
- Login as superadmin and verify sidebar links open without dropping `/otu/public`.
- Verify each admin sidebar uses Laravel-generated URLs rather than root-relative internal paths.
- Verify the profile link opens the current user's profile under the active base path.
- Verify the logout link works under the active base path.
- Verify `/admin/license` opens through the sidebar/license status link.
- Verify widget links that already used route helpers still work.
- Verify production domain-root URLs do not include `/public` unless the deployment itself is intentionally using that base path.

## Member form AJAX

- Open the member create form under `http://localhost/otu/public`.
- Change access level and confirm the role dropdown fetches roles successfully.
- Open the member edit form and confirm role reload still uses the active base path.

## Package-aware roles and access levels

- Governorship user create shows governorship/state roles such as `Governorship Candidate`, `State Campaign Director`, and `State ICT Director`.
- Governorship user create does not offer national/regional access levels as package assignments.
- Senatorial user create shows senatorial roles such as `Senatorial Candidate`, `Senatorial Campaign Director`, `LGA Coordinator`, `Ward Coordinator`, and `Polling Unit Agent`.
- LGA/chairmanship user create shows `Chairmanship Candidate`, `LGA Campaign Director`, `Ward Coordinator`, and `Polling Unit Agent`.
- User edit forms do not show `Presidential Candidate`, `Vice Presidential Candidate`, or national campaign roles inside non-presidential licensed packages.
- Role AJAX works under a temporary `/public` smoke path such as `http://localhost/otu/public`.
- Invalid out-of-package role/access-level submissions are rejected with validation errors.
- Managed mode without a local license still uses the existing broad role table behavior.

## Package-aware roles and permissions management

- Governorship roles/permissions page hides presidential roles such as `Presidential Candidate`, `Vice Presidential Candidate`, and `National ICT Director`.
- Senatorial roles/permissions page hides presidential and governorship-only roles.
- LGA/chairmanship roles/permissions page shows `Chairmanship Candidate`, `LGA ICT Director`, `Ward Coordinator`, and `Polling Unit Agent`.
- Roles management page displays the active campaign package and scope notice.
- User create/edit role dropdown matches the package-aware roles shown in roles management.
- Permission assignment cannot target an out-of-package role.
- New role creation is limited to package-appropriate role/access-level combinations in licensed self-hosted installs.
- ICT Director roles can access intended member edit actions within their jurisdiction.
- Coordinator roles do not automatically receive ICT write capability.
- Managed mode role management still shows the broad database role list.
- No role rows are deleted from the database by package filtering.

## Package-aware location form UX

- Governorship profile completion shows the licensed state context and does not ask the user to choose another state.
- Governorship member create/edit only offers in-state districts, constituencies, LGAs, wards, and polling units.
- Senatorial member create/edit shows the licensed state/district context and only offers related LGAs, wards, and polling units.
- Federal constituency member create/edit shows the licensed state/constituency context and only offers related LGAs, wards, and polling units.
- LGA/chairmanship member create/edit shows the licensed state/LGA context and only offers wards and polling units inside the licensed LGA.
- Agent nomination/direct assignment only offers in-scope polling units.
- Location AJAX cascades work under a temporary `/public` smoke path such as `http://localhost/otu/public`.
- Out-of-scope state, district, constituency, LGA, ward, or polling-unit payloads are rejected server-side.
- Managed mode without a local license keeps the broad existing location field behavior.

## Package-aware UI labels

- Presidential installs still show `National Campaign Dashboard`, `National Statistics`, and `National Election Statistics`.
- Governorship installs show `State Campaign Dashboard`, `State Campaign Statistics`, and `State Election Statistics`.
- Governorship dashboards do not show `National Statistics` as the primary dashboard label.
- Senatorial installs show `Senatorial District Campaign Dashboard` and `Senatorial District Statistics`.
- Federal constituency installs show `Federal Constituency Campaign Dashboard` and `Federal Constituency Statistics`.
- LGA/chairmanship installs show `Local Government Campaign Dashboard`, `LGA Campaign Statistics`, and ward/polling-unit geography language.
- Shared election statistic widgets use the package-specific election statistics title.
- National-only geography/state chart blocks are hidden or replaced with package-native geography charts on scoped package dashboards.

## Package-aware dashboard charts

- Gender distribution chart renders without JavaScript errors.
- Age bracket distribution chart renders without JavaScript errors.
- Voter card/eligibility chart renders without JavaScript errors.
- Religion chart renders where it is enabled for the package.
- Package geography chart renders using package-native geography.
- Empty chart state renders clear copy instead of a broken blank chart.
- Chart AJAX requests succeed under a temporary `/public` smoke path such as `http://localhost/otu/public`.
- Governorship dashboard uses LGA distribution language/data, not geopolitical region as the primary chart.
- Senatorial and federal constituency dashboards use LGA/Ward-oriented geography language/data.
- LGA/chairmanship dashboard uses Ward/Polling Unit-oriented chart language/data.
- Managed mode still shows expected broad national/state chart behavior.

## Package-aware election statistics

- Superadmin dashboard shows the Election Statistics widget.
- Governorship/state package dashboard shows `State Election Statistics`.
- Senatorial package dashboard shows `Senatorial District Election Statistics`.
- Federal constituency package dashboard shows `Federal Constituency Election Statistics`.
- LGA/chairmanship package dashboard shows `LGA Election Statistics`.
- Ward detail dashboard shows `Ward Election Statistics`.
- Polling unit detail dashboard shows `Polling Unit Election Statistics`.
- Empty election data shows zero values and clear empty copy.
- Widget links work under a temporary `/public` smoke path such as `http://localhost/otu/public`.
- Widget links work under a virtual host or production domain root.
- Out-of-scope election results, incidents, and evidence are not visible in scoped packages.
- Managed mode still shows expected broad election statistics behavior.

## Dashboard drilldowns

- Region dashboard opens where the current role and license allow it.
- State dashboard opens where the current role and license allow it.
- Senatorial district dashboard opens where the current role and license allow it.
- Federal constituency dashboard opens where the current role and license allow it.
- LGA dashboard opens where the current role and license allow it.
- Ward dashboard opens where the current role and license allow it.
- Polling unit dashboard opens where the current role and license allow it.
- Out-of-scope dashboard URLs return a controlled 403 response.
- Missing or invalid dashboard UUIDs return a controlled 404 response.
- Managed mode drilldowns continue to open with the existing broad behavior.
- Dashboard drilldown pages receive a `dashboardMetrics` payload where their views use shared dashboard metric components.

## Manual browser spot checks

- Superadmin: Dashboard, Members, Elections, Location, Settings, Profile, Logout.
- National admin: Dashboard, Members, Elections, Location, Settings, Profile, Logout.
- State/senatorial/federal/LGA/ward/PU admins: Dashboard, scoped Members, Elections, Location/Profile where visible.

## Not covered in this phase

- Persistent package-specific role metadata columns or mapping tables.
- Installer, licensing, and location provisioning flows.
