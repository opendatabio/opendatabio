<!DOCTYPE html>
<html lang="{{ config('app.locale') }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'OpenDataBio') }}</title>

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">

    <!-- Chart.js -->
    <script src="{{ asset('js/Chart.min.js') }}"></script>

    <!-- Scripts -->
    <script>
        window.Laravel = {!! json_encode([
            'csrfToken' => csrf_token(),
        ]) !!};
    </script>
</head>
<body>
    <div id="app">
        <nav class="navbar navbar-default navbar-static-top">
            <div class="container">
                <div class="navbar-header">

                    <!-- Collapsed Hamburger -->
                    <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#app-navbar-collapse">
                        <span class="sr-only">Toggle Navigation</span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>

                    <!-- Branding Image -->
                    <a class="navbar-brand" href="{{ url('/') }}">
                        {{ config('app.name', 'OpenDataBio') }}
                    </a>
                </div>

                <div class="collapse navbar-collapse" id="app-navbar-collapse">
                    <!-- Left Side Of Navbar -->
                    <ul class="nav navbar-nav">
			<li><a href="{{ route('locations.index') }}">
@lang('messages.locations')
</a></li>
			<li><a href="{{ route('persons.index') }}">
@lang('messages.persons')
</a></li>
			<li><a href="{{ route('taxons.index') }}">
@lang('messages.taxons')
</a></li>
			<li><a href="{{ route('plants.index') }}">
@lang('messages.plants')
</a></li>
			<li><a href="{{ route('vouchers.index') }}">
@lang('messages.vouchers')
</a></li>
			<li><a href="{{ route('references.index') }}">
@lang('messages.references')
</a></li>
			<li><a href="{{ route('herbaria.index') }}">
@lang('messages.herbaria')
</a></li>
			<li><a href="{{ route('projects.index') }}">
@lang('messages.projects')
</a></li>
			<li><a href="{{ route('datasets.index') }}">
@lang('messages.datasets')
</a></li>
			<li><a href="{{ route('tags.index') }}">
@lang('messages.tags')
</a></li>
@can ('show', App\User::class)
			<li><a href="{{ route('users.index') }}">
@lang('messages.users')
</a></li>
@endcan
                        &nbsp;
                    </ul>

                    <!-- Right Side Of Navbar -->
                    <ul class="nav navbar-nav navbar-right">
                        <!-- Authentication Links -->
                        @if (Auth::guest())
                            <li><a href="{{ route('login') }}">
@lang('messages.login')
</a></li>
                            <li><a href="{{ route('register') }}">
@lang('messages.register')
</a></li>
                        @else
                            <li class="dropdown">
                                <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
                                    {{ Auth::user()->email }} <span class="caret"></span>
                                </a>

                                <ul class="dropdown-menu" role="menu">
			    <li><a href="{{ route('selfedit') }}">
@lang('messages.edit_profile')
</a></li>
                                    <li>
                                        <a href="{{ route('logout') }}"
                                            onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                            Logout
                                        </a>

                                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                            {{ csrf_field() }}
                                        </form>
                                    </li>
@can ('index', App\UserJobs::class)
			<li><a href="{{ route('userjobs.index') }}">
@lang('messages.userjobs')
</a></li>
@endcan
                                </ul>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </nav>
@if (session('status'))
    <div class="col-sm-5 col-sm-offset-3 alert alert-success"><!-- TODO: positioning! -->
        {{ session('status') }}
    </div>
@endif
        @yield('content')
    </div>

    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}"></script>
    <!-- page-specific scripts -->
    @stack('scripts')
</body>
</html>
