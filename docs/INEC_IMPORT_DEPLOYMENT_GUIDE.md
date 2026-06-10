# INEC Import Deployment Guide

This guide explains how to prepare and import INEC election location data for Campaign Manager package deployments.

The INEC import system extends the existing location hierarchy:

```text
countries -> regions -> states -> local_government_areas -> wards -> polling_units
```

It also overlays electoral boundaries:

```text
senatorial_districts
federal_constituencies
```

Polling-unit-level links remain the most detailed source of electoral boundary mapping. LGA-level senatorial and federal constituency links are convenience links for package setup, filtering, and reporting.

## Purpose

The import system prepares the national INEC polling unit CSV into smaller, clean, import-ready CSV files. These files can be imported for different package scopes:

1. National package
2. State package
3. Senatorial district package
4. Federal constituency / House of Representatives package
5. Local government package
6. Ward package

The same raw national INEC CSV can be reused across all package types.

## Raw CSV Location

Place the raw INEC CSV at:

```text
app/imports/Nigeria_polling_units.csv
```

Run all commands from the project root.

The raw CSV must contain these columns:

```text
state,lg,ward,state_code,lg_code,ward_code,pu_code,code,location,ward_des,lg_des,senatorial,house_of_rep
```

Prepared files are written under:

```text
storage/app/imports/inec/prepared/
```

Failed import reports are written under:

```text
storage/app/imports/inec/reports/
```

## General Import Order

Use this order for every package type:

```text
States if needed
Senatorial districts
Federal constituencies
LGAs
Wards
Polling units
```

In a state package repo, the state may already be seeded, so state import may be skipped. In a national package repo, all states may be prepared and imported.

Always run dry-runs before real imports.

## Prepare All Nigeria Data

Use this for a national package.

```powershell
php artisan inec:prepare-csv app/imports/Nigeria_polling_units.csv
```

Output:

```text
storage/app/imports/inec/prepared/all/states.csv
storage/app/imports/inec/prepared/all/local_government_areas.csv
storage/app/imports/inec/prepared/all/wards.csv
storage/app/imports/inec/prepared/all/senatorial_districts.csv
storage/app/imports/inec/prepared/all/federal_constituencies.csv
storage/app/imports/inec/prepared/all/polling_units.csv
```

## Prepare Benue State Package

Use this for a Benue-only state package.

```powershell
php artisan inec:prepare-csv app/imports/Nigeria_polling_units.csv --state="Benue"
```

Output:

```text
storage/app/imports/inec/prepared/benue/
```

## Prepare One Senatorial District

Example: Benue South.

```powershell
php artisan inec:prepare-csv app/imports/Nigeria_polling_units.csv --state="Benue" --senatorial="Benue South"
```

Output:

```text
storage/app/imports/inec/prepared/benue/senatorial/benue-south/
```

## Prepare One Federal Constituency

Example: Apa/Agatu.

```powershell
php artisan inec:prepare-csv app/imports/Nigeria_polling_units.csv --state="Benue" --federal-constituency="Apa/Agatu"
```

Output:

```text
storage/app/imports/inec/prepared/benue/federal-constituency/apa-agatu/
```

## Prepare One Local Government

Example: Makurdi.

```powershell
php artisan inec:prepare-csv app/imports/Nigeria_polling_units.csv --state="Benue" --lga="Makurdi"
```

Output:

```text
storage/app/imports/inec/prepared/benue/lga/makurdi/
```

## Prepare One Ward

Example ward name.

```powershell
php artisan inec:prepare-csv app/imports/Nigeria_polling_units.csv --state="Benue" --lga="Makurdi" --ward="Ward Name"
```

Output:

```text
storage/app/imports/inec/prepared/benue/lga/makurdi/ward/ward-name/
```

## National Package Import

Dry-run:


```powershell
php artisan inec:import-states storage/app/imports/inec/prepared/all/states.csv --dry-run --create-missing-parents
php artisan inec:import-senatorial-districts storage/app/imports/inec/prepared/all/senatorial_districts.csv --dry-run
php artisan inec:import-federal-constituencies storage/app/imports/inec/prepared/all/federal_constituencies.csv --dry-run
php artisan inec:import-lgas storage/app/imports/inec/prepared/all/local_government_areas.csv --dry-run
php artisan inec:import-wards storage/app/imports/inec/prepared/all/wards.csv --dry-run
php artisan inec:import-polling-units storage/app/imports/inec/prepared/all/polling_units.csv --dry-run
```

Real import:

```powershell
php artisan inec:import-states storage/app/imports/inec/prepared/all/states.csv --create-missing-parents
php artisan inec:import-senatorial-districts storage/app/imports/inec/prepared/all/senatorial_districts.csv
php artisan inec:import-federal-constituencies storage/app/imports/inec/prepared/all/federal_constituencies.csv
php artisan inec:import-lgas storage/app/imports/inec/prepared/all/local_government_areas.csv
php artisan inec:import-wards storage/app/imports/inec/prepared/all/wards.csv
php artisan inec:import-polling-units storage/app/imports/inec/prepared/all/polling_units.csv```

## State Package Import

Example: Benue.

If Benue is already seeded in the state package repo, skip `inec:import-states`.

Dry-run:

```powershell
php artisan inec:import-senatorial-districts storage/app/imports/inec/prepared/benue/senatorial_districts.csv --dry-run
php artisan inec:import-federal-constituencies storage/app/imports/inec/prepared/benue/federal_constituencies.csv --dry-run
php artisan inec:import-lgas storage/app/imports/inec/prepared/benue/local_government_areas.csv --dry-run
php artisan inec:import-wards storage/app/imports/inec/prepared/benue/wards.csv --dry-run
php artisan inec:import-polling-units storage/app/imports/inec/prepared/benue/polling_units.csv --dry-run
```

Real import:

```powershell
php artisan inec:import-senatorial-districts storage/app/imports/inec/prepared/benue/senatorial_districts.csv
php artisan inec:import-federal-constituencies storage/app/imports/inec/prepared/benue/federal_constituencies.csv
php artisan inec:import-lgas storage/app/imports/inec/prepared/benue/local_government_areas.csv
php artisan inec:import-wards storage/app/imports/inec/prepared/benue/wards.csv
php artisan inec:import-polling-units storage/app/imports/inec/prepared/benue/polling_units.csv
```

If the state is not seeded, run this first:

```powershell
php artisan inec:import-states storage/app/imports/inec/prepared/benue/states.csv --create-missing-parents
```

## Senatorial District Package Import

Example: Benue South.

Prepared directory:

```text
storage/app/imports/inec/prepared/benue/senatorial/benue-south/
```

Dry-run:

```powershell
php artisan inec:import-senatorial-districts storage/app/imports/inec/prepared/benue/senatorial/benue-south/senatorial_districts.csv --dry-run
php artisan inec:import-federal-constituencies storage/app/imports/inec/prepared/benue/senatorial/benue-south/federal_constituencies.csv --dry-run
php artisan inec:import-lgas storage/app/imports/inec/prepared/benue/senatorial/benue-south/local_government_areas.csv --dry-run
php artisan inec:import-wards storage/app/imports/inec/prepared/benue/senatorial/benue-south/wards.csv --dry-run
php artisan inec:import-polling-units storage/app/imports/inec/prepared/benue/senatorial/benue-south/polling_units.csv --dry-run
```

Real import:

```powershell
php artisan inec:import-senatorial-districts storage/app/imports/inec/prepared/benue/senatorial/benue-south/senatorial_districts.csv
php artisan inec:import-federal-constituencies storage/app/imports/inec/prepared/benue/senatorial/benue-south/federal_constituencies.csv
php artisan inec:import-lgas storage/app/imports/inec/prepared/benue/senatorial/benue-south/local_government_areas.csv
php artisan inec:import-wards storage/app/imports/inec/prepared/benue/senatorial/benue-south/wards.csv
php artisan inec:import-polling-units storage/app/imports/inec/prepared/benue/senatorial/benue-south/polling_units.csv
```

## Federal Constituency Package Import

Example: Apa/Agatu.

Prepared directory:

```text
storage/app/imports/inec/prepared/benue/federal-constituency/apa-agatu/
```

Dry-run:

```powershell
php artisan inec:import-senatorial-districts storage/app/imports/inec/prepared/benue/federal-constituency/apa-agatu/senatorial_districts.csv --dry-run
php artisan inec:import-federal-constituencies storage/app/imports/inec/prepared/benue/federal-constituency/apa-agatu/federal_constituencies.csv --dry-run
php artisan inec:import-lgas storage/app/imports/inec/prepared/benue/federal-constituency/apa-agatu/local_government_areas.csv --dry-run
php artisan inec:import-wards storage/app/imports/inec/prepared/benue/federal-constituency/apa-agatu/wards.csv --dry-run
php artisan inec:import-polling-units storage/app/imports/inec/prepared/benue/federal-constituency/apa-agatu/polling_units.csv --dry-run
```

Real import:

```powershell
php artisan inec:import-senatorial-districts storage/app/imports/inec/prepared/benue/federal-constituency/apa-agatu/senatorial_districts.csv
php artisan inec:import-federal-constituencies storage/app/imports/inec/prepared/benue/federal-constituency/apa-agatu/federal_constituencies.csv
php artisan inec:import-lgas storage/app/imports/inec/prepared/benue/federal-constituency/apa-agatu/local_government_areas.csv
php artisan inec:import-wards storage/app/imports/inec/prepared/benue/federal-constituency/apa-agatu/wards.csv
php artisan inec:import-polling-units storage/app/imports/inec/prepared/benue/federal-constituency/apa-agatu/polling_units.csv
```

## Local Government Package Import

Example: Makurdi.

Prepared directory:

```text
storage/app/imports/inec/prepared/benue/lga/makurdi/
```

Dry-run:

```powershell
php artisan inec:import-senatorial-districts storage/app/imports/inec/prepared/benue/lga/makurdi/senatorial_districts.csv --dry-run
php artisan inec:import-federal-constituencies storage/app/imports/inec/prepared/benue/lga/makurdi/federal_constituencies.csv --dry-run
php artisan inec:import-lgas storage/app/imports/inec/prepared/benue/lga/makurdi/local_government_areas.csv --dry-run
php artisan inec:import-wards storage/app/imports/inec/prepared/benue/lga/makurdi/wards.csv --dry-run
php artisan inec:import-polling-units storage/app/imports/inec/prepared/benue/lga/makurdi/polling_units.csv --dry-run
```

Real import:

```powershell
php artisan inec:import-senatorial-districts storage/app/imports/inec/prepared/benue/lga/makurdi/senatorial_districts.csv
php artisan inec:import-federal-constituencies storage/app/imports/inec/prepared/benue/lga/makurdi/federal_constituencies.csv
php artisan inec:import-lgas storage/app/imports/inec/prepared/benue/lga/makurdi/local_government_areas.csv
php artisan inec:import-wards storage/app/imports/inec/prepared/benue/lga/makurdi/wards.csv
php artisan inec:import-polling-units storage/app/imports/inec/prepared/benue/lga/makurdi/polling_units.csv
```

## Ward Package Import

Example: Ward Name under Makurdi.

Prepared directory:

```text
storage/app/imports/inec/prepared/benue/lga/makurdi/ward/ward-name/
```

Dry-run:

```powershell
php artisan inec:import-senatorial-districts storage/app/imports/inec/prepared/benue/lga/makurdi/ward/ward-name/senatorial_districts.csv --dry-run
php artisan inec:import-federal-constituencies storage/app/imports/inec/prepared/benue/lga/makurdi/ward/ward-name/federal_constituencies.csv --dry-run
php artisan inec:import-lgas storage/app/imports/inec/prepared/benue/lga/makurdi/ward/ward-name/local_government_areas.csv --dry-run
php artisan inec:import-wards storage/app/imports/inec/prepared/benue/lga/makurdi/ward/ward-name/wards.csv --dry-run
php artisan inec:import-polling-units storage/app/imports/inec/prepared/benue/lga/makurdi/ward/ward-name/polling_units.csv --dry-run
```

Real import:

```powershell
php artisan inec:import-senatorial-districts storage/app/imports/inec/prepared/benue/lga/makurdi/ward/ward-name/senatorial_districts.csv
php artisan inec:import-federal-constituencies storage/app/imports/inec/prepared/benue/lga/makurdi/ward/ward-name/federal_constituencies.csv
php artisan inec:import-lgas storage/app/imports/inec/prepared/benue/lga/makurdi/ward/ward-name/local_government_areas.csv
php artisan inec:import-wards storage/app/imports/inec/prepared/benue/lga/makurdi/ward/ward-name/wards.csv
php artisan inec:import-polling-units storage/app/imports/inec/prepared/benue/lga/makurdi/ward/ward-name/polling_units.csv
```

## Inspect Generated CSV Files

Open files directly under:

```text
storage/app/imports/inec/prepared/
```

Example:

```powershell
Get-Content storage/app/imports/inec/prepared/benue/local_government_areas.csv -TotalCount 20
Get-Content storage/app/imports/inec/prepared/benue/polling_units.csv -TotalCount 20
```

Count rows:

```powershell
(Import-Csv storage/app/imports/inec/prepared/benue/polling_units.csv).Count
```

## Verify Counts After Import

Use database queries through Tinker:

```powershell
php artisan tinker
```

Then run:

```php
App\Models\State::where('name', 'Benue')->count();
App\Models\SenatorialDistrict::whereHas('state', fn ($q) => $q->where('name', 'Benue'))->count();
App\Models\FederalConstituency::whereHas('state', fn ($q) => $q->where('name', 'Benue'))->count();
App\Models\LocalGovernmentArea::whereHas('state', fn ($q) => $q->where('name', 'Benue'))->count();
App\Models\Ward::whereHas('localGovernmentArea.state', fn ($q) => $q->where('name', 'Benue'))->count();
App\Models\PollingUnit::whereHas('ward.localGovernmentArea.state', fn ($q) => $q->where('name', 'Benue'))->count();
```

Check LGA convenience links:

```php
App\Models\LocalGovernmentArea::whereHas('state', fn ($q) => $q->where('name', 'Benue'))
    ->whereNull('senatorial_district_id')
    ->orWhereNull('federal_constituency_id')
    ->count();
```

Check polling-unit-level boundary links:

```php
App\Models\PollingUnit::whereHas('ward.localGovernmentArea.state', fn ($q) => $q->where('name', 'Benue'))
    ->whereNull('senatorial_district_id')
    ->orWhereNull('federal_constituency_id')
    ->count();
```

## Inspect Failed-Row Reports

Failed-row reports are generated under:

```text
storage/app/imports/inec/reports/
```

List reports:

```powershell
Get-ChildItem storage/app/imports/inec/reports
```

Open a report:

```powershell
Get-Content storage/app/imports/inec/reports/failed_polling_units_YYYY_MM_DD_HHMMSS.csv -TotalCount 50
```

Each report includes the original row columns plus `failure_reason`.

## Rerun Imports Safely

The import commands are idempotent. Running the same prepared file again should not create duplicates.

Matching rules include:

```text
states: INEC state code, then normalized name
LGAs: state + INEC LGA code, then state + normalized name
wards: LGA + INEC ward code, then LGA + normalized name
senatorial districts: state + normalized name
federal constituencies: state + normalized name
polling units: INEC full code, then ward + name + PU code
```

Use `--skip-existing` when you want existing rows left untouched.

Use `--update-existing` when you want existing matching rows refreshed from the prepared CSV.

## Existing Seeded States

Many package repos may already seed the relevant state.

If the state already exists:

```powershell
php artisan inec:import-states storage/app/imports/inec/prepared/benue/states.csv --dry-run
```

If the dry-run shows the state is skipped or only updates missing INEC code, you may skip state import and continue with:

```powershell
php artisan inec:import-senatorial-districts ...
php artisan inec:import-federal-constituencies ...
php artisan inec:import-lgas ...
php artisan inec:import-wards ...
php artisan inec:import-polling-units ...
```

## When To Use `--create-missing-parents`

Use `--create-missing-parents` when importing into a fresh or national package database where parent records may not exist.

Common use:

```powershell
php artisan inec:import-states storage/app/imports/inec/prepared/all/states.csv --create-missing-parents
```

Avoid using it casually on narrow package imports if you expect the state, LGAs, or wards to already exist. The default behavior is safer: missing parents cause failed rows and reports.

## When To Use `--update-existing`

Use `--update-existing` when you want to refresh existing records from the prepared CSV.

Examples:

```powershell
php artisan inec:import-lgas storage/app/imports/inec/prepared/benue/local_government_areas.csv --update-existing
php artisan inec:import-polling-units storage/app/imports/inec/prepared/benue/polling_units.csv --update-existing
```

This can update names, missing INEC codes, and nullable electoral boundary links where supported.

## Rollback

Before data import, migrations can be rolled back:

```powershell
php artisan migrate:rollback --step=4
```

After data import, rollback removes schema but does not automatically undo imported rows unless you explicitly delete them. Avoid rolling back after real imports without a database backup.

Recommended before production import:

```text
Take a database backup.
Run dry-runs.
Review failed reports.
Run real imports.
Verify counts.
```

## Troubleshooting

### CSV file not found

The input path is used exactly as passed. If the raw file is at:

```text
app/imports/Nigeria_polling_units.csv
```

Run:

```powershell
php artisan inec:prepare-csv app/imports/Nigeria_polling_units.csv
```

### State not found

The state may not be seeded or imported. Import states first, or use `--create-missing-parents` where appropriate.

### LGA not found

Run LGA import before ward or polling unit import. Confirm `local_government_areas.csv` was generated for the same package scope.

### Ward not found

Run ward import before polling unit import. Confirm the ward exists under the correct LGA.

### Failed rows generated

Inspect:

```text
storage/app/imports/inec/reports/
```

Open the latest failed report and read `failure_reason`.

### Duplicate rows

Imports are designed to match existing records and skip or update instead of duplicating. If duplicates appear, inspect whether existing records lack INEC codes and have spelling differences from the prepared CSV.

### LGA boundary columns are blank

During preparation, LGA-level `senatorial_district_name` and `federal_constituency_name` are populated only when the LGA maps cleanly to exactly one senatorial district and one federal constituency.

If an LGA maps to multiple electoral boundaries, the LGA-level fields are left blank. Polling-unit-level links still preserve the detailed mapping.

### Import order was run incorrectly

Rerun in the recommended order:

```text
States if needed
Senatorial districts
Federal constituencies
LGAs
Wards
Polling units
```

Because imports are idempotent, rerunning the correct order is safe after reviewing failed reports.
