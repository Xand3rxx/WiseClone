@extends('layouts.app')

@section('title', 'Manage Users')

@section('content')
<div class="toolbar py-5 py-lg-5" id="kt_toolbar">
    <div id="kt_toolbar_container" class="container-xxl d-flex flex-stack flex-wrap">
        <div class="page-title d-flex flex-column me-3">
            <h1 class="d-flex text-dark fw-bolder my-1 fs-3">User Management</h1>
            <span class="text-muted fw-bold fs-7">Create users, review accounts, and manage access.</span>
        </div>
        <a href="{{ route('admin.users.create') }}" class="btn btn-sm btn-primary fw-bolder">Create User</a>
    </div>
</div>

<div id="kt_content_container" class="d-flex flex-column-fluid align-items-start container-xxl">
    <div class="content flex-row-fluid" id="kt_content">
        <div class="card">
            <div class="card-header border-0 pt-5">
                <h3 class="card-title align-items-start flex-column">
                    <span class="card-label fw-bolder fs-3 mb-1">Users</span>
                    <span class="text-muted mt-1 fw-bold fs-7">Blocked users cannot sign in or transact.</span>
                </h3>
            </div>

            <div class="card-body py-4">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-5">
                        <thead>
                            <tr class="text-start text-muted fw-bolder fs-7 text-uppercase gs-0">
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Currency</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 fw-bold">
                            @foreach ($users as $managedUser)
                                <tr>
                                    <td>
                                        <span class="fw-bolder text-dark">{{ Str::title($managedUser->name) }}</span>
                                        <div class="text-muted fs-7">Joined {{ $managedUser->created_at->format('M d, Y') }}</div>
                                    </td>
                                    <td>{{ $managedUser->email }}</td>
                                    <td>{{ Str::title($managedUser->role?->name ?? 'Unavailable') }}</td>
                                    <td>{{ $managedUser->currency?->code ?? 'Unavailable' }}</td>
                                    <td>
                                        @if ($managedUser->isBlocked())
                                            <span class="badge badge-light-danger fw-bolder">Blocked</span>
                                        @else
                                            <span class="badge badge-light-success fw-bolder">Active</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex gap-2 justify-content-end">
                                            <a href="{{ route('admin.users.show', $managedUser->uuid) }}" class="btn btn-sm btn-light-primary fw-bolder">Details</a>

                                            @if ($managedUser->id !== auth()->id())
                                                @if ($managedUser->isBlocked())
                                                    <form method="POST" action="{{ route('admin.users.unblock', $managedUser->uuid) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="btn btn-sm btn-light-success fw-bolder">Unblock</button>
                                                    </form>
                                                @else
                                                    <form method="POST" action="{{ route('admin.users.block', $managedUser->uuid) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="btn btn-sm btn-light-warning fw-bolder">Block</button>
                                                    </form>
                                                @endif

                                                <form method="POST" action="{{ route('admin.users.destroy', $managedUser->uuid) }}" onsubmit="return confirm('Delete this user? Transaction history will be retained.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-light-danger fw-bolder">Delete</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-5">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
