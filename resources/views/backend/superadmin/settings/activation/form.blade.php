@extends('backend.template.backend-master')
@section('content')

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Activate Your License</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                            <button type="button" class="btn btn-tool" data-card-widget="remove">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>

                    <div class="card-body" style="display: block;">
                        <!-- Activation Form -->
                        <form action="{{ route('validate.licence') }}" method="POST">
                            @csrf <!-- CSRF Token for security -->

                            <!-- Activation Code Input -->
                            <div class="form-group">
                                <label for="activation_code">Activation Code</label>
                                <input type="text" class="form-control" id="activation_code" name="activation_code" placeholder="Enter your activation code" autocomplete="off" required>
                                @error('activation_code')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Hidden Package Input -->
                            <input type="hidden" name="package" value="{{ optional(DB::table('system_settings')->first())->package }}">

                            <!-- Submit Button -->
                            <button type="submit" class="btn btn-primary">Activate License</button>
                        </form>

                        <br>

                        <!-- Footer Note -->
                        <div class="card-footer">
                            <strong>Note:</strong>
                            <p class="text-info">
                                Please enter the activation code that was emailed to you after your purchase. If you haven't received the code, please contact support.
                            </p>
                        </div>
                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
            <!-- /.col -->
        </div>
        <!-- /.row -->
    </div>
    <!-- /.container-fluid -->
</section>

@endsection