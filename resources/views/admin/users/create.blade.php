@extends('layouts.app')

@section('title', 'Create User')

@section('content')
<div class="toolbar py-5 py-lg-5" id="kt_toolbar">
    <div id="kt_toolbar_container" class="container-xxl d-flex flex-stack flex-wrap">
        <div class="page-title d-flex flex-column me-3">
            <h1 class="d-flex text-dark fw-bolder my-1 fs-3">Create User</h1>
            <span class="text-muted fw-bold fs-7">Create customer or administrator accounts.</span>
        </div>
        <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-light-primary fw-bolder">Back to Users</a>
    </div>
</div>

<div id="kt_content_container" class="d-flex flex-column-fluid align-items-start container-xxl">
    <div class="content flex-row-fluid" id="kt_content">
        <div class="card">
            <div class="card-header border-0 pt-5">
                <h3 class="card-title align-items-start flex-column">
                    <span class="card-label fw-bolder fs-3 mb-1">Account Details</span>
                    <span class="text-muted mt-1 fw-bold fs-7">New users are email verified and start with zero balances.</span>
                </h3>
            </div>

            <form method="POST" action="{{ route('admin.users.store') }}">
                @csrf

                <div class="card-body py-4">
                    <div class="row g-6">
                        <div class="col-md-6">
                            <label class="form-label fw-bolder">Name</label>
                            <input type="text" name="name" value="{{ old('name') }}" class="form-control form-control-solid @error('name') is-invalid @enderror" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bolder">Email</label>
                            <input type="email" name="email" value="{{ old('email') }}" class="form-control form-control-solid @error('email') is-invalid @enderror" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bolder">Role</label>
                            <select name="role_id" class="form-select form-select-solid @error('role_id') is-invalid @enderror" required>
                                <option value="" disabled selected>Select role</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}" @selected(old('role_id') == $role->id)>{{ Str::title($role->name) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bolder">Default Currency</label>
                            <select name="currency_id" class="form-select form-select-solid @error('currency_id') is-invalid @enderror" required>
                                <option value="" disabled selected>Select currency</option>
                                @foreach ($currencies as $currency)
                                    <option value="{{ $currency->id }}" @selected(old('currency_id') == $currency->id)>{{ $currency->code }} - {{ $currency->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bolder">Password</label>
                            <input type="password" name="password" class="form-control form-control-solid @error('password') is-invalid @enderror" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bolder">Confirm Password</label>
                            <input type="password" name="password_confirmation" class="form-control form-control-solid" required>
                        </div>
                    </div>
                </div>

                <div class="card-footer d-flex justify-content-end gap-3">
                    <a href="{{ route('admin.users.index') }}" class="btn btn-light fw-bolder">Cancel</a>
                    <button type="submit" class="btn btn-primary fw-bolder">Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
