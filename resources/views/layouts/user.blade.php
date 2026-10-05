@extends('layouts.app')

@section('body')
    @include('components.navbar')

    <div class="dashboard-layout">
        @include('components.user-sidebar')

        <main class="dashboard-main">
            @if(session('success'))
                <div class="alert alert-success alert-auto-dismiss">
                    <span><x-icon name="check-circle" size="14" /> {{ session('success') }}</span>
                    <button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;color:inherit;font-weight:bold;">&times;</button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-auto-dismiss">
                    <span><x-icon name="alert" size="14" /> {{ session('error') }}</span>
                    <button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;color:inherit;font-weight:bold;">&times;</button>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
@endsection
