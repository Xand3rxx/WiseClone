@extends('layouts.app')
@section('title', 'Create Transaction')
@section('content')

<span id="source-url" data-currency-balance-url="{{ route('transaction.currency_balance') }}" data-source-url="{{ route('transaction.source_converter') }}" class="d-none"></span>

<div class="toolbar py-5 py-lg-5" id="kt_toolbar">
    <!--begin::Container-->
    <div id="kt_toolbar_container" class="container-xxl d-flex flex-stack flex-wrap">
        <!--begin::Page title-->
        <div class="page-title d-flex flex-column me-3">
            <!--begin::Title-->
            <h1 class="d-flex text-dark fw-bolder my-1 fs-3">Create</h1>
            <!--end::Title-->
            <!--begin::Breadcrumb-->
            <ul class="breadcrumb breadcrumb-dot fw-bold text-gray-600 fs-7 my-1">
                <!--begin::Item-->
                <li class="breadcrumb-item text-gray-600">
                    <a href="{{ route('home') }}" class="text-gray-600 text-hover-primary">Dashboard</a>
                </li>
                <!--end::Item-->
                <!--begin::Item-->
                <li class="breadcrumb-item text-gray-600">Transaction</li>
                <!--end::Item-->
                <!--begin::Item-->
                <li class="breadcrumb-item text-gray-500">Create Transaction</li>
                <!--end::Item-->
            </ul>
            <!--end::Breadcrumb-->
        </div>
        <!--end::Page title-->
    </div>
    <!--end::Container-->
</div>

<div id="kt_content_container" class="d-flex flex-column-fluid align-items-start container-xxl">
    <!--begin::Post-->
    <div class="content flex-row-fluid" id="kt_content">
        <!--begin::Layout-->
        <div class="d-flex flex-column flex-lg-row">
            <!--begin::Content-->
            <div class="flex-lg-row-fluid mb-10 mb-lg-0 me-lg-6 me-xl-6 col-7">
                <!--begin::Card-->
                <div class="card">
                    <!--begin::Card body-->
                    <div class="card-body p-12">
                        <!--begin::Form-->
                        <form method="POST" action="{{ route('transaction.store') }}">
                            @csrf
                            <div class="flex-lg-auto min-w-lg-300px">
                                @include('application.transactions.includes._source_breakdown')
                                <div class="separator separator-dashed mb-8"></div>
                                @include('application.transactions.includes._transaction_breakdown')
                                <div class="separator separator-dashed mb-8"></div>
                                @include('application.transactions.includes._recipient_breakdown')
                            </div>
                        </form>
                        <!--end::Form-->
                    </div>
                    <!--end::Card body-->
                </div>
                <!--end::Card-->
            </div>
            <!--end::Content-->
            <!--begin::Sidebar-->
            <div class="flex-lg-auto min-w-lg-300px col-5">
                <div class="pb-5 fs-6">
                    @if($user['latestCurrencyBalance'] && $user['latestCurrencyBalance']['USD'] == 0.0)
                        <!--begin::Refund button-->
                        <div class="flex-shrink-0 p-4 p-lg-0 me-lg-2 mb-3">
                            <a href="{{ route('fund_account') }}" class="btn btn-sm btn-primary fw-bolder w-100 w-lg-auto">Refund Dollar Account</a>
                        </div>
                        <!--end::Refund button-->
                    @endif

                    <div class="card-body d-flex flex-column flex-lg-row flex-stack p-lg-5">
                        <!--begin::Info-->
                        <div class="d-flex flex-column align-items-lg-start text-lg-start">
                            <!--begin::Title-->
                            <h3 class="fs-2x line-height-lg mb-5">
                                <span class="fw-bold">Your account balance</span>
                                <br />
                            </h3>
                            <div class="table-responsive">
                                <!--begin::Table-->
                                <table class="table align-middle table-row-dashed gy-5">
                                    <!--begin::Table head-->
                                    <thead class="border-bottom border-gray-200 fs-7 fw-bolder">
                                        <!--begin::Table row-->
                                        <tr class="text-start text-muted text-uppercase gs-0">
                                            <th class="min-w-100px">USD</th>
                                            <th class="min-w-100px">EUR</th>
                                            <th class="min-w-100px">NGN</th>
                                        </tr>
                                        <!--end::Table row-->
                                    </thead>
                                    <!--end::Table head-->
                                    <!--begin::Table body-->
                                    <tbody class="fs-4 fw-bold text-gray-600">
                                        <tr>
                                            <td>${{ number_format($user['latestCurrencyBalance']['USD'] ?? 0, 2) }}</td>
                                            <td>€{{ number_format($user['latestCurrencyBalance']['EUR'] ?? 0, 2) }}</td>
                                            <td>₦{{ number_format($user['latestCurrencyBalance']['NGN'] ?? 0, 2) }}</td>
                                        </tr>
                                    </tbody>
                                    <!--end::Table body-->
                                </table>
                                <!--end::Table-->
                            </div>

                        </div>
                        <!--end::Info-->
                    </div>
                    <!--end::Body-->

                <p class="fs-4 mb-4">This calculation is deduced from <strong>Wise</strong> provided on <a href=“https://wise.com/gb/pricing/send-money” target=“_blank”>Wise Fees For Sending Money</a> webpage.</p>

                    <div class="notice d-flex bg-light-warning rounded border-warning border border-dashed mb-9 p-6">
                        <!--begin::Icon-->
                        <!--begin::Svg Icon | path: icons/duotune/art/art006.svg-->
                        <span class="svg-icon svg-icon-2tx svg-icon-warning me-4">
                            <img src="{{ asset('assets/media/icons/duotune/general/gen044.svg') }}" />
                        </span>
                        <!--end::Svg Icon-->
                        <!--end::Icon-->
                        <!--begin::Wrapper-->
                        <div class="d-flex flex-stack flex-grow-1">
                            <!--begin::Content-->
                            <div class="fw-bold">
                                <div class="fs-6 text-gray-700"><strong>The Fixed Fee from <strong>Wise</strong> is charged based on assumption that charges will be made to someone outside of the <strong>SEPA(Single Euro Payments Area)</strong></div>
                            </div>
                            <!--end::Content-->
                        </div>
                        <!--end::Wrapper-->
                    </div>

                    <fieldset>
                        <legend class="mt-4 text-primary">Legend</legend>
                        <li>VP = Variable Percentage</li>
                        <li>EC = Exchange Rate</li>
                        <li>VF = Variable Fee</li>
                        <li>TC = Transfer Fee</li>
                        <li>ATC = Amount To Convert</li>
                        <li>TA = Target Amount</li>
                    </fieldset>

                    <fieldset>
                        <legend class="mt-4 text-primary">Calculation</legend>
                        <li><strong>Step 1:</strong> Variable Fee  = (VP/100) * Source Amount</li>
                        <li><strong>Step 2:</strong> Transfer Fee  = VF + Fixed Fee</li>
                        <li><strong>Step 3:</strong> ATC = Source Amount - Transfer Fee</li>
                        <li><strong>Step 4:</strong> Target Amount = ATC * EC</li>
                    </fieldset>

                    <div class="notice d-flex bg-light-warning rounded border-warning border border-dashed mb-9 mt-4 p-6">
                        <!--begin::Icon-->
                        <!--begin::Svg Icon | path: icons/duotune/art/art006.svg-->
                        <span class="svg-icon svg-icon-2tx svg-icon-warning me-4">
                            <img src="{{ asset('assets/media/icons/duotune/general/gen044.svg') }}" />
                        </span>
                        <!--end::Svg Icon-->
                        <!--end::Icon-->
                        <!--begin::Wrapper-->
                        <div class="d-flex flex-stack flex-grow-1">
                            <!--begin::Content-->
                            <div class="fw-bold">
                                <div class="fs-6 text-gray-700">Kindly note that the fallback rate used in this project are the same rates utilised by <strong>Wise</strong> on the day of this project upload.</div>
                            </div>
                            <!--end::Content-->
                        </div>
                        <!--end::Wrapper-->
                    </div>

                    <fieldset>
                        <legend class="mt-4 text-primary">Charges & Rate</legend>
                        <div class="table-responsive">
                            <!--begin::Table-->
                            <table class="table align-middle table-row-dashed gy-5">
                                <!--begin::Table head-->
                                <thead class="border-bottom border-gray-200 fs-7 fw-bolder">
                                    <!--begin::Table row-->
                                    <tr class="text-start text-muted text-uppercase gs-0">
                                        <th class="text-center">#</th>
                                        <th class="text-center">Source Currency</th>
                                        <th class="text-center">Target Currency</th>
                                        <th class="text-center">Rate</th>
                                        <th class="text-center">Variable Percentage</th>
                                        <th class="text-center">Fixed Fee</th>
                                    </tr>
                                    <!--end::Table row-->
                                </thead>
                                <!--end::Table head-->
                                <!--begin::Table body-->
                                <tbody class="fs-7 fw-bold text-gray-600">
                                    @foreach ($charges as $charge)
                                        <tr>
                                            <td class="text-center">{{ $loop->iteration }}</td>
                                            <td class="text-center">{{ !empty($charge['sourceCurrency']['code']) ? $charge['sourceCurrency']['code'] : 'Unavailable' }}</td>
                                            <td class="text-center">{{ !empty($charge['targetCurrency']['code']) ? $charge['targetCurrency']['code'] : 'Unavailable' }}</td>
                                            <td class="text-center">{{ !empty($charge['rate']) ? $charge['rate'] : '0' }}</td>
                                            <td class="text-center">{{ !empty($charge['variable_percentage']) ? $charge['variable_percentage'] : '0' }}%</td>
                                            <td class="text-center">{{ !empty($charge['fixed_fee']) ? $charge['fixed_fee'] : '0' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <!--end::Table body-->
                            </table>
                            <!--end::Table-->
                        </div>
                    </fieldset>
                </div>
            </div>
            <!--end::Sidebar-->
        </div>
        <!--end::Layout-->
    </div>
    <!--end::Post-->
</div>

@push('scripts')
    <script src="{{ asset('assets/js/cleave.min.js') }}"></script>
    <script>
        $('.select').select2({
            placeholder: 'Select...',
            closeOnSelect: false,
        });

        $('.select').val(null);

        $sourceCurrency = $('#source-amount');
        $sourceCurrency.val($sourceCurrency.attr('data-max'));

        // Numeral Formatting with decimal support
        const sourceAmount = new Cleave('#source-amount', {
          numeral: true,
          numeralThousandsGroupStyle: 'thousand',
          numeralDecimalScale: 2,
          numeralDecimalMark: '.',
          numeralPositiveOnly: true
        });

        const targetAmount = new Cleave('#target-amount', {
          numeral: true,
          numeralThousandsGroupStyle: 'thousand',
          numeralDecimalScale: 2,
          numeralDecimalMark: '.',
          numeralPositiveOnly: true
        });


    </script>
@endpush
@endsection


