@extends('layouts.app')

@section('title', 'User Details')

@section('content')
<div class="toolbar py-5 py-lg-5" id="kt_toolbar">
    <div id="kt_toolbar_container" class="container-xxl d-flex flex-stack flex-wrap">
        <div class="page-title d-flex flex-column me-3">
            <h1 class="d-flex text-dark fw-bolder my-1 fs-3">{{ Str::title($managedUser->name) }}</h1>
            <span class="text-muted fw-bold fs-7">{{ $managedUser->email }}</span>
        </div>
        <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-light-primary fw-bolder">Back to Users</a>
    </div>
</div>

<div id="kt_content_container" class="d-flex flex-column-fluid align-items-start container-xxl">
    <div class="content flex-row-fluid" id="kt_content">
        <div class="row g-6 mb-8">
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h3 class="fw-bolder mb-5">Profile</h3>
                        <p><span class="fw-bolder">Role:</span> {{ Str::title($managedUser->role?->name ?? 'Unavailable') }}</p>
                        <p><span class="fw-bolder">Default Currency:</span> {{ $managedUser->currency?->code ?? 'Unavailable' }}</p>
                        <p><span class="fw-bolder">Email Verified:</span> {{ $managedUser->email_verified_at ? 'Yes' : 'No' }}</p>
                        <p>
                            <span class="fw-bolder">Status:</span>
                            @if ($managedUser->trashed())
                                <span class="badge badge-light-dark fw-bolder">Deleted</span>
                            @elseif ($managedUser->isBlocked())
                                <span class="badge badge-light-danger fw-bolder">Blocked</span>
                            @else
                                <span class="badge badge-light-success fw-bolder">Active</span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card h-100">
                    <div class="card-body">
                        <h3 class="fw-bolder mb-5">Balances</h3>
                        <div class="row g-4">
                            <div class="col-md-4">
                                <div class="border border-dashed rounded p-4">
                                    <div class="text-muted fw-bold">USD</div>
                                    <div class="fs-3 fw-bolder">${{ number_format((float) ($managedUser->latestCurrencyBalance?->USD ?? 0), 2) }}</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border border-dashed rounded p-4">
                                    <div class="text-muted fw-bold">EUR</div>
                                    <div class="fs-3 fw-bolder">€{{ number_format((float) ($managedUser->latestCurrencyBalance?->EUR ?? 0), 2) }}</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border border-dashed rounded p-4">
                                    <div class="text-muted fw-bold">NGN</div>
                                    <div class="fs-3 fw-bolder">₦{{ number_format((float) ($managedUser->latestCurrencyBalance?->NGN ?? 0), 2) }}</div>
                                </div>
                            </div>
                        </div>

                        @if (! $managedUser->trashed() && $managedUser->id !== auth()->id())
                            <div class="d-flex gap-3 mt-6">
                                @if ($managedUser->isBlocked())
                                    <form method="POST" action="{{ route('admin.users.unblock', $managedUser->uuid) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-light-success fw-bolder">Unblock User</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.users.block', $managedUser->uuid) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-light-warning fw-bolder">Block User</button>
                                    </form>
                                @endif

                                <form method="POST" action="{{ route('admin.users.destroy', $managedUser->uuid) }}" onsubmit="return confirm('Delete this user? Transaction history will be retained.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-light-danger fw-bolder">Delete User</button>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header border-0 pt-5">
                <h3 class="card-title align-items-start flex-column">
                    <span class="card-label fw-bolder fs-3 mb-1">Transactions</span>
                    <span class="text-muted mt-1 fw-bold fs-7">All transactions where this user is sender or recipient.</span>
                </h3>
            </div>
            <div class="card-body py-4">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-5">
                        <thead>
                            <tr class="text-start text-muted fw-bolder fs-7 text-uppercase gs-0">
                                <th>From</th>
                                <th>To</th>
                                <th class="text-center">Amount</th>
                                <th class="text-center">Source</th>
                                <th class="text-center">Target</th>
                                <th class="text-center">Type</th>
                                <th class="text-center">Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 fw-bold">
                            @forelse ($transactions as $transaction)
                                <tr>
                                    <td>{{ $transaction->senderNameFor($managedUser->id) }}</td>
                                    <td>{{ $transaction->receiverNameFor($managedUser->id) }}</td>
                                    <td class="text-center text-{{ $transaction->type()->signClass }}">{{ $transaction->type()->sign }}{{ $transaction->amount() }}</td>
                                    <td class="text-center">{{ $transaction->sourceCurrency?->code ?? 'Unavailable' }}</td>
                                    <td class="text-center">{{ $transaction->targetCurrency?->code ?? 'Unavailable' }}</td>
                                    <td class="text-center"><span class="badge badge-{{ $transaction->type()->class }} fw-bolder">{{ $transaction->type()->name }}</span></td>
                                    <td class="text-center"><span class="badge badge-{{ $transaction->status()->class }} fw-bolder">{{ $transaction->status()->name }}</span></td>
                                    <td>{{ $transaction->created_at->format('M d, Y h:i A') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted">No transactions found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-5">
                    {{ $transactions->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
