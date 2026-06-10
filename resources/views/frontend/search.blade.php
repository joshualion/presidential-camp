@extends('frontend.layouts.app')

@section('content')
<div class="container py-4" id="search-results-root">
    <div class="row g-3">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="GET" action="{{ route('search.index') }}" class="row g-2 align-items-center">
                        <div class="col-12 col-md-6">
                            <input type="text" name="query" class="form-control" placeholder="Search posts, people..."
                                   value="{{ $term }}" />
                        </div>
                        <div class="col-12 col-md-3">
                            <select name="type" class="form-select">
                                <option value="all" {{ $type === 'all' ? 'selected' : '' }}>All</option>
                                <option value="people" {{ $type === 'people' ? 'selected' : '' }}>People</option>
                                <option value="posts" {{ $type === 'posts' ? 'selected' : '' }}>Posts</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary text-white flex-grow-1">Search</button>
                            <a href="{{ route('timeline') }}" class="btn btn-light">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12">
            <ul class="nav nav-pills mb-3">
                <li class="nav-item">
                    <a class="nav-link {{ $type === 'all' ? 'active' : '' }}" href="{{ route('search.index', ['query' => $term, 'type' => 'all']) }}">All</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $type === 'people' ? 'active' : '' }}" href="{{ route('search.index', ['query' => $term, 'type' => 'people']) }}">People</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $type === 'posts' ? 'active' : '' }}" href="{{ route('search.index', ['query' => $term, 'type' => 'posts']) }}">Posts</a>
                </li>
            </ul>
        </div>

        @if($type === 'all' || $type === 'people')
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">People</h6>
                    @if($people && method_exists($people, 'total'))<small class="text-muted">{{ $people->total() }} found</small>@endif
                </div>
                <div class="card-body">
                    @if($people && $people->count())
                        @foreach($people as $person)
                            <div class="d-flex align-items-center mb-3">
                                <img src="{{ $person->photo ? url('uploads/member_images/'.$person->photo) : url('uploads/no_image.jpg') }}" alt="avatar" class="rounded-circle me-3" width="48" height="48">
                                <div>
                                    <div class="fw-semibold">{{ $person->firstname }} {{ $person->lastname }}</div>
                                    <small class="text-muted">{{ $person->username ? '@'.$person->username : '' }}</small>
                                </div>
                                <a href="{{ $person->username ? url('/community/'.$person->username.'/profile/timeline') : url('/community/profile') }}" class="btn btn-light btn-sm ms-auto">View</a>
                            </div>
                        @endforeach
                        @if(method_exists($people, 'links')) {{ $people->withQueryString()->links() }} @endif
                    @else
                        <div class="text-muted">No people found.</div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        @if($type === 'all' || $type === 'posts')
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Posts</h6>
                    @if($posts && method_exists($posts, 'total'))<small class="text-muted">{{ $posts->total() }} found</small>@endif
                </div>
                <div class="card-body">
                    @if($posts && $posts->count())
                        @foreach($posts as $post)
                            <div class="mb-3 pb-3 border-bottom">
                                <div class="d-flex align-items-center mb-1">
                                    <strong class="me-2">{{ optional($post->user)->firstname }} {{ optional($post->user)->lastname }}</strong>
                                    <small class="text-muted">{{ optional($post->user)->username ? '@'.optional($post->user)->username : '' }}</small>
                                </div>
                                <div>{{ Str::limit(strip_tags($post->content), 160) }}</div>
                                <a href="{{ url('/community/feed') . '#post-' . $post->id }}" class="btn btn-link btn-sm ps-0">Open</a>
                            </div>
                        @endforeach
                        @if(method_exists($posts, 'links')) {{ $posts->withQueryString()->links() }} @endif
                    @else
                        <div class="text-muted">No posts found.</div>
                    @endif
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
