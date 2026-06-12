@extends('backend.template.backend-master')
@section('content')

@php
    $ajaxUrl = route($profileData->access_level.'.election.votesByPuData', $election->uuid);
    $snapshotUrl = route('election.report.scope-snapshot', $election->uuid);
    $baseReportUrl = route($profileData->access_level.'.election.votesByPu', $election->uuid);
    $initialFilters = array_filter($filters ?? [], fn ($value) => filled($value));
    $visibleFilters = array_values($filterOptions['visibility'] ?? []);
    $electionLabel = \Carbon\Carbon::parse($election->year)->year.' - '.$election->name;
    $partyVoteLabel = $election->party?->acronym ?: 'Our Votes';
@endphp

<style>
    .report-shell .card {
        border-radius: 6px;
    }

    .report-section-title {
        font-size: .78rem;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
        color: #6c757d;
        margin-bottom: .65rem;
    }

    .scope-card {
        border-left: 4px solid #007bff;
    }

    .scope-kicker {
        color: #6c757d;
        font-size: .75rem;
        text-transform: uppercase;
        font-weight: 700;
    }

    .scope-value {
        font-size: 1rem;
        font-weight: 700;
        margin-bottom: .5rem;
    }

    .scope-breadcrumb {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .35rem;
        margin-bottom: 0;
        padding-left: 0;
        list-style: none;
    }

    .scope-breadcrumb button {
        border: 0;
        background: transparent;
        padding: 0;
        color: #007bff;
        font-weight: 600;
    }

    .scope-breadcrumb .separator {
        color: #adb5bd;
    }

    .kpi-card {
        min-height: 92px;
        border-radius: 6px;
    }

    .kpi-label {
        font-size: .78rem;
        font-weight: 700;
        text-transform: uppercase;
        opacity: .85;
    }

    .kpi-value {
        font-size: 1.6rem;
        font-weight: 800;
        line-height: 1.1;
    }

    .drill-filter {
        display: none;
    }

    .drill-filter.is-visible {
        display: block;
    }

    .election-votes-table th {
        white-space: nowrap;
        vertical-align: middle;
    }

    .election-votes-table td.numeric,
    .election-votes-table th.numeric {
        text-align: center;
        white-space: nowrap;
    }

    .election-votes-table td.actions {
        white-space: nowrap;
    }

    .dark-mode .report-section-title,
    .dark-mode .scope-kicker {
        color: #ced4da;
    }

    .dark-mode .scope-card,
    .dark-mode .card {
        background-color: #343a40;
        color: #f8f9fa;
    }
</style>

<section class="content report-shell">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">
                            Election Votes Intelligence
                        </h3>
                        <div class="card-tools">
                            @if($canVerifyResults)
                                <span class="badge badge-success">Can Verify</span>
                            @endif
                            @if($canManageDisputes)
                                <span class="badge badge-danger">Dispute Authority</span>
                            @endif
                            <span class="badge badge-info">{{ $packageContext['label'] ?? 'Campaign Package' }}</span>
                            @if($canUploadPollingUnitResults)
                                <a href="{{ route($profileData->access_level.'.vote.add') }}" class="btn btn-default btn-sm">
                                    <i class="fa fa-plus-square"></i> Add Vote Records
                                </a>
                            @endif
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-4">
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <div class="report-section-title">Election Selection</div>
                                        <label for="election-selector">Election</label>
                                        <select id="election-selector" class="form-control">
                                            @foreach($filterOptions['elections'] ?? [] as $optionElection)
                                                <option value="{{ route($profileData->access_level.'.election.votesByPu', $optionElection->uuid) }}" @selected($optionElection->uuid === $election->uuid)>
                                                    {{ \Carbon\Carbon::parse($optionElection->year)->year }} - {{ $optionElection->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted d-block mt-2">Defaults to the latest election by election date.</small>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-8">
                                <div class="card scope-card mb-3">
                                    <div class="card-body">
                                        <div class="report-section-title">Scope Information</div>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="scope-kicker">Election</div>
                                                <div id="scope-election" class="scope-value">{{ $scopeSnapshot['election']['label'] ?? $electionLabel }}</div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="scope-kicker">Scope</div>
                                                <div id="scope-label" class="scope-value">{{ $scopeSnapshot['scope']['label'] ?? $scopeLabel }}</div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="scope-kicker">Coverage</div>
                                                <div id="scope-coverage" class="scope-value">
                                                    {{ number_format($scopeSnapshot['coverage']['lgas'] ?? 0) }} LGAs,
                                                    {{ number_format($scopeSnapshot['coverage']['wards'] ?? 0) }} Wards,
                                                    {{ number_format($scopeSnapshot['coverage']['polling_units'] ?? 0) }} PUs
                                                </div>
                                            </div>
                                        </div>
                                        <ul id="scope-breadcrumb" class="scope-breadcrumb"></ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="report-section-title">Drill-Down Filters</div>
                                <div class="row">
                                    <div class="col-md-3 drill-filter" data-level="region">
                                        <label>Region</label>
                                        <select class="form-control report-filter" data-key="region_id">
                                            <option value="">All Regions</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 drill-filter" data-level="state">
                                        <label>State</label>
                                        <select class="form-control report-filter" data-key="state_id">
                                            <option value="">All States</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 drill-filter" data-level="senatorial">
                                        <label>Senatorial District</label>
                                        <select class="form-control report-filter" data-key="senatorial_district_id">
                                            <option value="">All Districts</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 drill-filter" data-level="federal">
                                        <label>Federal Constituency</label>
                                        <select class="form-control report-filter" data-key="federal_constituency_id">
                                            <option value="">All Constituencies</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 drill-filter mt-2" data-level="lga">
                                        <label>Local Government</label>
                                        <select class="form-control report-filter" data-key="lga_id">
                                            <option value="">All LGAs</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 drill-filter mt-2" data-level="ward">
                                        <label>Ward</label>
                                        <select class="form-control report-filter" data-key="ward_id">
                                            <option value="">All Wards</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 drill-filter mt-2" data-level="polling_unit">
                                        <label>Polling Unit</label>
                                        <select class="form-control report-filter" data-key="polling_unit_id">
                                            <option value="">All Polling Units</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mt-2">
                                        <label>Verification</label>
                                        <select class="form-control report-filter" data-key="verification_status">
                                            <option value="">All</option>
                                            <option value="submitted">Submitted</option>
                                            <option value="verified">Verified</option>
                                            <option value="rejected">Rejected</option>
                                            <option value="pending">Pending</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mt-2">
                                        <label>Dispute</label>
                                        <select class="form-control report-filter" data-key="dispute_status">
                                            <option value="">All</option>
                                            <option value="normal">Normal</option>
                                            <option value="disputed">Disputed</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <button type="button" id="clear-filters" class="btn btn-secondary btn-sm">
                                        <i class="fas fa-undo"></i> Reset Drill-Down
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-3 col-sm-6 mb-2">
                                <div class="small-box bg-info kpi-card">
                                    <div class="inner">
                                        <div class="kpi-value" id="kpi-submitted">{{ number_format($scopeSnapshot['kpis']['submitted'] ?? 0) }}</div>
                                        <div class="kpi-label">Submitted Results</div>
                                    </div>
                                    <div class="icon"><i class="fas fa-file-alt"></i></div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-2">
                                <div class="small-box bg-success kpi-card">
                                    <div class="inner">
                                        <div class="kpi-value" id="kpi-verified">{{ number_format($scopeSnapshot['kpis']['verified'] ?? 0) }}</div>
                                        <div class="kpi-label">Verified Results</div>
                                    </div>
                                    <div class="icon"><i class="fas fa-check-circle"></i></div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-2">
                                <div class="small-box bg-danger kpi-card">
                                    <div class="inner">
                                        <div class="kpi-value" id="kpi-rejected">{{ number_format($scopeSnapshot['kpis']['rejected'] ?? 0) }}</div>
                                        <div class="kpi-label">Rejected Results</div>
                                    </div>
                                    <div class="icon"><i class="fas fa-times-circle"></i></div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-2">
                                <div class="small-box bg-warning kpi-card">
                                    <div class="inner">
                                        <div class="kpi-value" id="kpi-pending">{{ number_format($scopeSnapshot['kpis']['pending'] ?? 0) }}</div>
                                        <div class="kpi-label">Pending Verification</div>
                                    </div>
                                    <div class="icon"><i class="fas fa-clock"></i></div>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-3">
                            <div class="card-body py-2">
                                <div class="report-section-title mb-2">Exports</div>
                                <a data-export-base="{{ route('election.report.print', $election->uuid) }}" target="_blank" class="btn btn-default btn-sm report-export">
                                    <i class="fas fa-print"></i> Print View
                                </a>
                                <a data-export-base="{{ route('election.report.polling-units.csv', $election->uuid) }}" class="btn btn-success btn-sm report-export">
                                    <i class="fas fa-file-csv"></i> PU CSV
                                </a>
                                <a data-export-base="{{ route('election.report.polling-units.excel', $election->uuid) }}" class="btn btn-success btn-sm report-export">
                                    <i class="fas fa-file-excel"></i> PU Excel
                                </a>
                                <a data-export-base="{{ route('election.report.summary.csv', $election->uuid) }}" class="btn btn-info btn-sm report-export">
                                    <i class="fas fa-file-csv"></i> Summary CSV
                                </a>
                                <a data-export-base="{{ route('election.report.summary.excel', $election->uuid) }}" class="btn btn-info btn-sm report-export">
                                    <i class="fas fa-file-excel"></i> Summary Excel
                                </a>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <div class="report-section-title">Results Table</div>
                                <table id="puVotes" class="table table-bordered table-striped election-votes-table">
                                    <thead>
                                        <tr>
                                            <th>S/N</th>
                                            <th>PU</th>
                                            <th>Result</th>
                                            <th class="numeric">Total Votes</th>
                                            <th class="numeric">{{ $partyVoteLabel }}</th>
                                            <th class="numeric">Incidents</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="{{asset('assets/plugins/jquery/jquery.min.js')}}"></script>
<script>
    $(document).ready(function() {
        const filterOptions = @json($filterOptions);
        const visibleLevels = @json($visibleFilters);
        const initialFilters = @json($initialFilters);
        const snapshotUrl = "{{ $snapshotUrl }}";
        const baseReportUrl = "{{ $baseReportUrl }}";
        const filters = {
            region_id: "",
            state_id: "",
            senatorial_district_id: "",
            federal_constituency_id: "",
            lga_id: "",
            ward_id: "",
            polling_unit_id: "",
            verification_status: "",
            dispute_status: "",
            ...initialFilters
        };

        const levels = [
            { level: "region", key: "region_id", label: "Region", items: filterOptions.regions || [] },
            { level: "state", key: "state_id", label: "State", items: filterOptions.states || [] },
            { level: "senatorial", key: "senatorial_district_id", label: "Senatorial District", items: filterOptions.senatorial_districts || [] },
            { level: "federal", key: "federal_constituency_id", label: "Federal Constituency", items: filterOptions.federal_constituencies || [] },
            { level: "lga", key: "lga_id", label: "Local Government", items: filterOptions.lgas || [] },
            { level: "ward", key: "ward_id", label: "Ward", items: filterOptions.wards || [] },
            { level: "polling_unit", key: "polling_unit_id", label: "Polling Unit", items: filterOptions.polling_units || [] }
        ];

        const childKeys = {
            region_id: ["state_id", "senatorial_district_id", "federal_constituency_id", "lga_id", "ward_id", "polling_unit_id"],
            state_id: ["senatorial_district_id", "federal_constituency_id", "lga_id", "ward_id", "polling_unit_id"],
            senatorial_district_id: ["federal_constituency_id", "lga_id", "ward_id", "polling_unit_id"],
            federal_constituency_id: ["lga_id", "ward_id", "polling_unit_id"],
            lga_id: ["ward_id", "polling_unit_id"],
            ward_id: ["polling_unit_id"]
        };

        function numberFormat(value) {
            return new Intl.NumberFormat().format(value || 0);
        }

        function activeQuery() {
            const params = new URLSearchParams();
            Object.keys(filters).forEach((key) => {
                if (filters[key]) {
                    params.set(key, filters[key]);
                }
            });
            return params.toString();
        }

        function optionAllowed(level, item) {
            if (level === "state") {
                return !filters.region_id || String(item.region_id) === String(filters.region_id);
            }
            if (level === "senatorial") {
                return !filters.state_id || String(item.state_id) === String(filters.state_id);
            }
            if (level === "federal") {
                if (filters.senatorial_district_id) {
                    return String(item.senatorial_district_id) === String(filters.senatorial_district_id);
                }
                return !filters.state_id || String(item.state_id) === String(filters.state_id);
            }
            if (level === "lga") {
                if (filters.federal_constituency_id) {
                    return String(item.federal_constituency_id) === String(filters.federal_constituency_id);
                }
                if (filters.senatorial_district_id) {
                    return String(item.senatorial_district_id) === String(filters.senatorial_district_id);
                }
                return !filters.state_id || String(item.state_id) === String(filters.state_id);
            }
            if (level === "ward") {
                return !filters.lga_id || String(item.lga_id) === String(filters.lga_id);
            }
            if (level === "polling_unit") {
                return !filters.ward_id || String(item.ward_id) === String(filters.ward_id);
            }
            return true;
        }

        function shouldShowLevel(index) {
            const current = levels[index];
            if (!visibleLevels.includes(current.level)) {
                return false;
            }

            const firstVisible = levels.findIndex((level) => visibleLevels.includes(level.level));
            if (index === firstVisible) {
                return true;
            }

            const previousVisible = [...levels].slice(0, index).reverse().find((level) => visibleLevels.includes(level.level));
            return previousVisible ? Boolean(filters[previousVisible.key]) : true;
        }

        function populateSelect(levelConfig) {
            const select = $(`.report-filter[data-key="${levelConfig.key}"]`);
            const current = filters[levelConfig.key] || "";
            const defaultText = `All ${levelConfig.label}${levelConfig.level === "lga" ? "s" : ""}`;
            select.empty().append(new Option(defaultText, ""));

            levelConfig.items
                .filter((item) => optionAllowed(levelConfig.level, item))
                .forEach((item) => select.append(new Option(item.name, item.id)));

            select.val(current);
        }

        function renderFilters() {
            levels.forEach((levelConfig, index) => {
                populateSelect(levelConfig);
                $(`.drill-filter[data-level="${levelConfig.level}"]`).toggleClass("is-visible", shouldShowLevel(index));
            });

            $(".report-filter").each(function () {
                const key = $(this).data("key");
                if (Object.prototype.hasOwnProperty.call(filters, key)) {
                    $(this).val(filters[key] || "");
                }
            });
        }

        function updateExports() {
            const query = activeQuery();
            $(".report-export").each(function () {
                const base = $(this).data("export-base");
                $(this).attr("href", query ? `${base}?${query}` : base);
            });

            const nextUrl = query ? `${baseReportUrl}?${query}` : baseReportUrl;
            window.history.replaceState({}, "", nextUrl);
        }

        function renderSnapshot(snapshot) {
            $("#scope-election").text(snapshot.election?.label || "");
            $("#scope-label").text(snapshot.scope?.label || "");
            $("#scope-coverage").text(`${numberFormat(snapshot.coverage?.lgas)} LGAs, ${numberFormat(snapshot.coverage?.wards)} Wards, ${numberFormat(snapshot.coverage?.polling_units)} PUs`);
            $("#kpi-submitted").text(numberFormat(snapshot.kpis?.submitted));
            $("#kpi-verified").text(numberFormat(snapshot.kpis?.verified));
            $("#kpi-rejected").text(numberFormat(snapshot.kpis?.rejected));
            $("#kpi-pending").text(numberFormat(snapshot.kpis?.pending));

            const breadcrumb = $("#scope-breadcrumb");
            breadcrumb.empty();
            (snapshot.breadcrumb || []).forEach((item, index) => {
                if (index > 0) {
                    breadcrumb.append('<li class="separator">&rsaquo;</li>');
                }
                const button = $('<button type="button"></button>')
                    .text(item.label)
                    .attr("data-key", item.key || "")
                    .attr("data-id", item.id || "");
                breadcrumb.append($("<li></li>").append(button));
            });
        }

        function refreshSnapshot() {
            $.get(snapshotUrl, filters, renderSnapshot);
        }

        function clearChildren(parentKey) {
            (childKeys[parentKey] || []).forEach((key) => {
                filters[key] = "";
            });
        }

        $("#election-selector").on("change", function () {
            if (this.value) {
                const query = activeQuery();
                window.location.href = query ? `${this.value}?${query}` : this.value;
            }
        });

        $(".report-filter").on("change", function () {
            const key = $(this).data("key");
            filters[key] = $(this).val() || "";
            clearChildren(key);
            renderFilters();
            updateExports();
            table.ajax.reload();
            refreshSnapshot();
        });

        $("#clear-filters").on("click", function () {
            Object.keys(filters).forEach((key) => {
                filters[key] = "";
            });
            renderFilters();
            updateExports();
            table.ajax.reload();
            refreshSnapshot();
        });

        $("#scope-breadcrumb").on("click", "button", function () {
            const key = $(this).data("key");
            if (!key || key === "scope") {
                return;
            }
            clearChildren(key);
            renderFilters();
            updateExports();
            table.ajax.reload();
            refreshSnapshot();
        });

        const table = $('#puVotes').DataTable({
            processing: true,
            serverSide: true,
            searching: true,
            ajax:{
                url: "{{ $ajaxUrl }}",
                type: 'GET',
                data: function (data) {
                    Object.assign(data, filters);
                }
            },
            pageLength: 50,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            columns: [
                {
                    data: null,
                    render: function (data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    },
                    orderable: false,
                    searchable: false
                },
                { data: "polling_unit_name", searchable: true },
                { data: "result_sheet", orderable: false, searchable: false },
                { data: "total_votes_cast", searchable: false },
                { data: "votes_obtained", searchable: false },
                { data: "incident_report", searchable: true },
                { data: "status", searchable: false, orderable: false },
                { data: "governance_actions", searchable: false, orderable: false }
            ],
            columnDefs: [
                { targets: [3, 4, 5], className: 'numeric' },
                { targets: [7], className: 'actions' }
            ],
            responsive: true,
            autoWidth: false
        });

        renderFilters();
        updateExports();
        renderSnapshot(@json($scopeSnapshot));
        $('#puVotes_filter input').attr('placeholder', 'Search By PU Name');
    });
</script>

@endsection
