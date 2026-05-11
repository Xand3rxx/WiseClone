<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<!--begin::Head-->
<head>
    <meta charset="utf-8" />
    <meta name="description" content="Banks charge a lot for overseas transfers. We don't. Transfer money abroad easily and quickly with our low cost money transfers." />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="author" content="Anthony Joboy (+2349035547107)" />
    <meta property="og:locale" content="en_US" />
    <meta property="og:type" content="website" />
    <meta property="og:title" content="{{ config('app.name') }}" />
    <meta name="copyright" content="Wise(Clone). All Rights Reserved" />

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title') | {{ config('app.name') }}</title>

    <link rel="shortcut icon" href="{{ asset('assets/media/logos/brand_flag.svg') }}" />

    <!--begin::Fonts-->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700" />
    <!--end::Fonts-->
    <!--begin::Global Stylesheets Bundle(used by all pages)-->
    <link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
    <!--end::Global Stylesheets Bundle-->
</head>
<!--end::Head-->

<body id="kt_body" class="bg-body">
    @include('layouts.partials._messages')

    <!--begin::Main-->
    <!--begin::Root-->
    <div class="d-flex flex-column flex-root">
        @yield('content')
    </div>
    <!--end::Root-->
    <!--end::Main-->
    <!--begin::Javascript-->
    <!--begin::Global Javascript Bundle(used by all pages)-->
    <script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>

    <!--end::Global Javascript Bundle-->
    <!--begin::Page Custom Javascript(used by this page)-->
    @if(request()->routeIs('register'))
        <script src="{{ asset('assets/js/custom/authentication/sign-up/general.js') }}"></script>
    @elseif(request()->routeIs('login'))
        <script src="{{ asset('assets/js/custom/authentication/sign-in/general.js') }}"></script>
    @endif
    <script>
        /**
         * @description Display session message with Sweet Alert
         * @param string message
         * @param string type
         *
         * @returns toast
         */
        //Feedback from session message to be displayed with Sweet Alert
        function displayMessage(message, type) {
            const Toast = swal.mixin({
                toast: true,
                position: "top-end",
                showConfirmButton: false,
                timer: 8000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener("mouseenter", Swal.stopTimer);
                    toast.addEventListener("mouseleave", Swal.resumeTimer);
                },
            });
            Toast.fire({
                icon: type,
                //   type: 'success',
                title: message,
            });
        }
    </script>
    @stack('scripts')
    <!--end::Page Custom Javascript-->
    <!--end::Javascript-->
</body>
<!--end::Body-->
</html>
