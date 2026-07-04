@extends('backend.template.backend-master')
@section('content')
    <div class="row">
            
            <div class="col-md-9">
                <div class="card card-primary">
                <div class="card-header p-2">
                    <h5 class="card-title">Select Volunteer Group(s)</h5>
                </div><!-- /.card-header -->
                <div class="card-body">
                    <div class="tab-content">

                    <!-- /.tab-pane -->
                    <div class="tab-pane active" id="editprofile">
                        <form action="{{ route($profileData->access_level.'.account.support-group.store') }}" method="POST">
                            @csrf
                    
                            <div class="form-group">
                                <label for="support_groups">Select at least one group:</label>
                                <div class="form-check-group row">
                                    @foreach ($supportGroups as $group)
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input 
                                                    type="checkbox" 
                                                    name="support_groups[]" 
                                                    id="group_{{ $group->id }}" 
                                                    value="{{ $group->id }}" 
                                                    class="form-check-input @error('support_groups') is-invalid @enderror"
                                                    {{ in_array($group->id, $userSupportGroups) ? 'checked' : '' }}
                                                >
                                                <label class="form-check-label" for="group_{{ $group->id }}">
                                                    {{ $group->name }}
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                    @error('support_groups')
                                        <div class="col-12">
                                            <span class="text-danger">{{ $message }}</span>
                                        </div>
                                    @enderror
                                </div>
                            </div>
                            
                            
                    
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </form>
                        
                    </div>
                    <!-- /.tab-pane -->


                    </div>
                    <!-- /.tab-content -->
                </div><!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
            <!-- /.col -->
    </div>
    <!-- /.row -->


<style>
If you’re not using a CSS framework, you can add custom styles:

css
Copy code
.form-check-group {
    display: flex;
    flex-wrap: wrap;
    gap: 15px; /* Add spacing between checkboxes */
}

.form-check-group .col-md-6 {
    flex: 0 0 50%; /* Each item takes up 50% of the row */
    max-width: 50%;
}

.form-check {
    display: flex;
    align-items: center;
}

.form-check-input {
    margin-right: 5px;
    transform: scale(1.2); /* Adjust size */
    accent-color:   {{ $SystemSetting->dark_theme_color ?? ' #008751'}} !important; /* Change color for modern browsers */
}

.form-check-label {
    font-size: 1.5rem;
    font-weight: 500;
}

</style>
@endsection
