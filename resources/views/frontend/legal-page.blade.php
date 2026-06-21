@extends('frontend.layouts.app')

@section('content')
    <div class="col-12 col-lg-8 mx-auto">
        <div class="card card-primary shadow-sm mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ $legalTitle }}</h5>
            </div>
            <div class="card-body">
                @if($legalContent !== '')
                    <div class="community-legal-content">
                        {!! $legalContent !!}
                    </div>
                @else
                    <p class="text-muted mb-0">{{ $emptyMessage }}</p>
                @endif
            </div>
        </div>
    </div>
@endsection
