@extends('layouts.app')

@section('body')
    @include('components.navbar')

    <main class="site-main">
        @if(session('success'))
            <div class="container" style="margin-top: 20px;">
                <div class="alert alert-success alert-auto-dismiss">
                    <span><x-icon name="check-circle" size="14" /> {{ session('success') }}</span>
                    <button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;color:inherit;font-weight:bold;">&times;</button>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="container" style="margin-top: 20px;">
                <div class="alert alert-danger alert-auto-dismiss">
                    <span><x-icon name="alert" size="14" /> {{ session('error') }}</span>
                    <button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;color:inherit;font-weight:bold;">&times;</button>
                </div>
            </div>
        @endif

        @yield('content')
    </main>

    @include('components.footer')
@endsection
