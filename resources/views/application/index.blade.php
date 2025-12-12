@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')

<!--begin::Toolbar-->
<div class="toolbar py-5 py-lg-5" id="kt_toolbar">
    <!--begin::Container-->
    <div id="kt_toolbar_container" class="container-xxl py-5">
        @if($user['latestCurrencyBalance'] && $user['latestCurrencyBalance']['USD'] == 1000)
        <div class="notice d-flex bg-light-primary rounded border-primary border border-dashed mb-9 p-6">
            <!--begin::Icon-->
            <!--begin::Svg Icon | path: icons/duotune/art/art006.svg-->
            <span class="svg-icon svg-icon-2tx svg-icon-primary me-4">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path opacity="0.3" d="M22 19V17C22 16.4 21.6 16 21 16H8V3C8 2.4 7.6 2 7 2H5C4.4 2 4 2.4 4 3V19C4 19.6 4.4 20 5 20H21C21.6 20 22 19.6 22 19Z" fill="black"></path>
                    <path d="M20 5V21C20 21.6 19.6 22 19 22H17C16.4 22 16 21.6 16 21V8H8V4H19C19.6 4 20 4.4 20 5ZM3 8H4V4H3C2.4 4 2 4.4 2 5V7C2 7.6 2.4 8 3 8Z" fill="black"></path>
                </svg>
            </span>
            <!--end::Svg Icon-->
            <!--end::Icon-->
            <!--begin::Wrapper-->
            <div class="d-flex flex-stack flex-grow-1">
                <!--begin::Content-->
                <div class="fw-bold">
                    <div class="fs-6 text-gray-700"><strong>Congrats!</strong> {{ !empty($user['name']) ? Str::title($user['name']) : 'Unavailable' }} you have a gift of ${{ $user['latestCurrencyBalance']['USD'] ?? 0 }}. Click the <strong>New Transaction</strong> button to make a transaction.</div>
                </div>
                <!--end::Content-->
            </div>
            <!--end::Wrapper-->
        </div>
        @endif

        @if(!$user['latestCurrencyBalance'])
        <div class="notice d-flex bg-light-warning rounded border-warning border border-dashed mb-9 p-6">
            <span class="svg-icon svg-icon-2tx svg-icon-warning me-4">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path opacity="0.3" d="M12 22C13.6569 22 15 20.6569 15 19C15 17.3431 13.6569 16 12 16C10.3431 16 9 17.3431 9 19C9 20.6569 10.3431 22 12 22Z" fill="black"/>
                    <path d="M19 15V18C19 18.6 18.6 19 18 19H6C5.4 19 5 18.6 5 18V15C6.1 15 7 14.1 7 13V10C7 7.6 8.7 5.6 11 5.1V3C11 2.4 11.4 2 12 2C12.6 2 13 2.4 13 3V5.1C15.3 5.6 17 7.6 17 10V13C17 14.1 17.9 15 19 15ZM11 10C11 9.4 11.4 9 12 9C12.6 9 13 8.6 13 8C13 7.4 12.6 7 12 7C10.3 7 9 8.3 9 10C9 10.6 9.4 11 10 11C10.6 11 11 10.6 11 10Z" fill="black"/>
                </svg>
            </span>
            <div class="d-flex flex-stack flex-grow-1">
                <div class="fw-bold">
                    <div class="fs-6 text-gray-700"><strong>Notice:</strong> No currency balance found. Admin accounts do not have transaction capabilities. Please use a customer account to make transactions.</div>
                </div>
            </div>
        </div>
        @endif

        <!--begin::Row-->
        <div class="row gy-0 gx-10">
            <div class="col-xl-8">
                <!--begin::Engage widget 2-->
                <div class="card card-xl-stretch bg-body border-0 mb-5 mb-xl-0">
                    <!--begin::Body-->
                    <div class="card-body d-flex flex-column flex-lg-row flex-stack p-lg-15">
                        <!--begin::Info-->
                        <div class="d-flex flex-column justify-content-center align-items-center align-items-lg-start me-10 text-center text-lg-start">
                            <!--begin::Title-->
                            <h3 class="fs-2x line-height-lg mb-5">
                                <span class="fw-bold">Hello, {{ !empty($user['name']) ? Str::title($user['name']) : 'Unavailable' }}</span><br>
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

                            <!--end::Title-->
                            <div class="fs-4 text-muted mb-7">
                                {{ config('app.tagline') }}<br>
                                Send money cheaper and easier than old-school banks and send money at the real exchange rate with no hidden fees.
                            </div>
                        </div>
                        <!--end::Info-->
                        <!--begin::Illustration-->
                        <img src="{{ asset('assets/media/illustrations/sketchy-1/11.png') }}" alt="" class="mw-200px mw-lg-250px mt-lg-n10" />
                        <!--end::Illustration-->
                    </div>
                    <!--end::Body-->
                </div>
                <!--end::Engage widget 2-->
            </div>
            <div class="col-xl-4">
                <!--begin::Mixed Widget 16-->
                <div class="card card-xl-stretch bg-body border-0" style="background-image: url({{ asset('assets/media/svg/shapes/abstract-2.svg') }}); background-position: right top; background-size: 45%; background-repeat:no-repeat;">
                    <!--begin::Body-->
                    <div class="card-body pt-5 mb-xl-9 position-relative">
                        <!--begin::Heading-->
                        <div class="d-flex flex-stack">
                            <!--begin::Title-->
                            <h4 class="fw-bolder text-gray-800 m-0">Account Summary</h4>
                            <!--end::Title-->
                        </div>
                        <!--end::Heading-->
                        <div class="separator my-2"></div>
                        <!--begin::Content-->
                        <div class="mt-10">
                            <!--begin::Text-->
                            <p class="fs-4"><span class="fw-bolder">Total Transactions: </span>{{ !empty($transactions) ? number_format($transactions->count()) : 0 }}</p>
                            <div class="separator separator-dashed"></div>
                            @if($user['latestCurrencyBalance'])
                            <p class="fs-4 mt-2"><span class="fw-bolder">Last Transaction: </span>{{ Carbon\Carbon::parse($user['latestCurrencyBalance']['created_at'], 'UTC')->isoFormat('MMMM Do YYYY, h:mm:ssa') }}</p>
                            @else
                            <p class="fs-4 mt-2"><span class="fw-bolder">Last Transaction: </span>N/A</p>
                            @endif
                            <div class="separator separator-dashed"></div>
                            <p class="fs-4 mt-2"><span class="fw-bolder">Default Currency: </span>{{ $user['currency']['name'].'('.$user['currency']['code'].')' }}</p>
                            <div class="separator separator-dashed"></div>
                            <!--end::Text-->
                        </div>
                        <!--end::Content-->
                    </div>
                    <!--end::Body-->
                </div>
                <!--end::Mixed Widget 16-->
            </div>
        </div>
        <!--end::Row-->
    </div>
    <!--end::Container-->
</div>
<!--end::Toolbar-->

<div id="kt_content_container" class="d-flex flex-column-fluid align-items-start container-xxl">
    <!--begin::Post-->
    <div class="content flex-row-fluid" id="kt_content">
        <!--begin::Card-->
        <div class="card">
            <div class="card-header border-0 pt-5">
                <h3 class="card-title align-items-start flex-column">
                    <span class="card-label fw-bolder fs-3 mb-1">Transaction History</span>
                    <span class="text-muted mt-1 fw-bold fs-7">All transactions created as of {{ date('M, d Y') }}</span>
                </h3>
            </div>

            <div class="card-body py-4">
                {{-- @if ($user['role']['name'] == 'customer') --}}
                <div class="card-title">
                    <!--begin::Search-->
                    <div class="d-flex align-items-center position-relative my-1">
                        <!--begin::Svg Icon | path: icons/duotune/general/gen021.svg-->
                        <span class="svg-icon svg-icon-1 position-absolute ms-6">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <rect opacity="0.5" x="17.0365" y="15.1223" width="8.15546" height="2" rx="1" transform="rotate(45 17.0365 15.1223)" fill="black"></rect>
                                <path d="M11 19C6.55556 19 3 15.4444 3 11C3 6.55556 6.55556 3 11 3C15.4444 3 19 6.55556 19 11C19 15.4444 15.4444 19 11 19ZM11 5C7.53333 5 5 7.53333 5 11C5 14.4667 7.53333 17 11 17C14.4667 17 17 14.4667 17 11C17 7.53333 14.4667 5 11 5Z" fill="black"></path>
                            </svg>
                        </span>
                        <!--end::Svg Icon-->
                        <input type="text" data-kt-user-table-filter="search" class="form-control form-control-solid w-250px ps-14" placeholder="Search by name, currency, type...">
                    </div>
                    <!--end::Search-->
                </div>
                    @include('application.includes._customer_table')
                {{-- @else --}}
                {{-- @endif --}}
            </div>
        </div>
    </div>
</div>
@include('application.includes._transaction_details_modal')
@endsection
