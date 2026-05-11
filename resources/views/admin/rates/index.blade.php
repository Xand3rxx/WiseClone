@extends('layouts.app')

@section('title', 'Manage Rates')

@section('content')
<div class="toolbar py-5 py-lg-5" id="kt_toolbar">
    <div id="kt_toolbar_container" class="container-xxl d-flex flex-stack flex-wrap">
        <div class="page-title d-flex flex-column me-3">
            <h1 class="d-flex text-dark fw-bolder my-1 fs-3">Rate Management</h1>
            <span class="text-muted fw-bold fs-7">Update exchange rates and transaction fees by currency pair.</span>
        </div>
    </div>
</div>

<div id="kt_content_container" class="d-flex flex-column-fluid align-items-start container-xxl">
    <div class="content flex-row-fluid" id="kt_content">
        <div class="card">
            <div class="card-header border-0 pt-5">
                <h3 class="card-title align-items-start flex-column">
                    <span class="card-label fw-bolder fs-3 mb-1">Rates & Fees</span>
                    <span class="text-muted mt-1 fw-bold fs-7">Changes take effect immediately for new quotes and transfers.</span>
                </h3>
            </div>

            <div class="card-body py-4">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-5">
                        <thead>
                            <tr class="text-start text-muted fw-bolder fs-7 text-uppercase gs-0">
                                <th>Pair</th>
                                <th class="text-center">Rate</th>
                                <th class="text-center">Variable Fee (%)</th>
                                <th class="text-center">Fixed Fee</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 fw-bold">
                            @foreach ($charges as $charge)
                                <tr>
                                    <td>
                                        <span class="fw-bolder text-dark">{{ $charge->sourceCurrency->code }} -> {{ $charge->targetCurrency->code }}</span>
                                        <div class="text-muted fs-7">{{ $charge->sourceCurrency->name }} to {{ $charge->targetCurrency->name }}</div>
                                    </td>
                                    <td colspan="4">
                                        <form method="POST" action="{{ route('admin.rates.update', $charge) }}" class="row g-3 align-items-center justify-content-end">
                                            @csrf
                                            @method('PATCH')

                                            <div class="col-12 col-lg-3">
                                                <input type="number" name="rate" value="{{ old("rate.{$charge->id}", $charge->rate) }}" step="0.000001" min="0.000001" class="form-control form-control-solid text-center @error('rate') is-invalid @enderror" aria-label="Exchange rate">
                                            </div>
                                            <div class="col-12 col-lg-3">
                                                <input type="number" name="variable_percentage" value="{{ old("variable_percentage.{$charge->id}", $charge->variable_percentage) }}" step="0.0001" min="0" max="10" class="form-control form-control-solid text-center @error('variable_percentage') is-invalid @enderror" aria-label="Variable fee percentage">
                                            </div>
                                            <div class="col-12 col-lg-3">
                                                <input type="number" name="fixed_fee" value="{{ old("fixed_fee.{$charge->id}", $charge->fixed_fee) }}" step="0.0001" min="0" class="form-control form-control-solid text-center @error('fixed_fee') is-invalid @enderror" aria-label="Fixed fee">
                                            </div>
                                            <div class="col-12 col-lg-2 text-end">
                                                <button type="submit" class="btn btn-sm btn-primary fw-bolder w-100">Save</button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger mt-6">
                        <strong>Unable to update rate.</strong> Please check the highlighted values and try again.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
