@extends('backend.template.backend-master')
@section('content')
@php
    $geography = $dashboardMetrics['geography'] ?? [];
    $coverage = $dashboardMetrics['coverage'] ?? [];
    $people = $dashboardMetrics['people'] ?? [];
    $election = $dashboardMetrics['election'] ?? [];
    $scopeName = optional($federalConstituency)->name ?? 'Federal Constituency';
@endphp

<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">{{ $dashboardMetrics['scope']['title'] ?? $packageUi->statisticsTitle() }}</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-minus"></i>
            </button>
        </div>
    </div>
    <div class="card-body">
        @include('backend.shared.dashboard-stat-grid', [
            'dashboardMetrics' => $dashboardMetrics,
            'groups' => ['coverage', 'people', 'membership_activity'],
        ])
    </div>
</div>

@include('backend.shared.dashboard-election-stat-section', ['dashboardMetrics' => $dashboardMetrics])

@include('backend.shared.dashboard-stat-polling', [
    'statsEndpoint' => route('federaladmin.dashboard.stats'),
    'pollInterval' => 30000,
])

@endsection
