@extends('layouts.app')

@section('title', 'Change Password')

@section('content')
<div class="toolbar py-5 py-lg-5" id="kt_toolbar">
    <div id="kt_toolbar_container" class="container-xxl d-flex flex-stack flex-wrap">
        <div class="page-title d-flex flex-column me-3">
            <h1 class="d-flex text-dark fw-bolder my-1 fs-3">Change Password</h1>
            <span class="text-muted fw-bold fs-7">Update your account password.</span>
        </div>
    </div>
</div>

<div id="kt_content_container" class="d-flex flex-column-fluid align-items-start container-xxl">
    <div class="content flex-row-fluid" id="kt_content">
        <div class="card">
            <div class="card-header border-0 pt-5">
                <h3 class="card-title align-items-start flex-column">
                    <span class="card-label fw-bolder fs-3 mb-1">Security</span>
                    <span class="text-muted mt-1 fw-bold fs-7">Use a strong password with mixed case, numbers, and symbols.</span>
                </h3>
            </div>

            <form method="POST" action="{{ route('account.password.update') }}">
                @csrf
                @method('PATCH')

                <div class="card-body py-4">
                    <div class="row g-6">
                        <div class="col-md-4">
                            <label class="form-label fw-bolder">Current Password</label>
                            <input type="password" name="current_password" class="form-control form-control-solid @error('current_password') is-invalid @enderror" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bolder">New Password</label>
                            <input type="password" name="password" class="form-control form-control-solid @error('password') is-invalid @enderror" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bolder">Confirm New Password</label>
                            <input type="password" name="password_confirmation" class="form-control form-control-solid" required>
                        </div>
                    </div>
                </div>

                <div class="card-footer d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary fw-bolder">Change Password</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
