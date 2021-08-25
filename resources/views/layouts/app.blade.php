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
    <link rel="shortcut icon" href="{{ asset('favicon_io/favicon.ico') }}" >
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
    <br>
    <div class="container">
      <div class="navbar-header">
        <img src="{{ asset('favicon_io/favicon.ico') }}" height=30>
      </div>
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
  			<li>
          <a href="{{ route('locations.index') }}">
            @lang('messages.locations')
          </a>
        </li>
  			<li>
          <a href="{{ route('taxons.index') }}">
            @lang('messages.taxons')
          </a>
        </li>
  			<li>
          <a href="{{ route('individuals.index') }}">
            @lang('messages.individuals')
          </a>
        </li>
  			<li>
          <a href="{{ route('vouchers.index') }}">
            @lang('messages.vouchers')
          </a>
        </li>
  			<li>
          <a href="{{ route('projects.index') }}">
            @lang('messages.projects')
          </a>
        </li>
        <li>
          <a href="{{ route('datasets.index') }}">
            @lang('messages.datasets')
          </a>
        </li>
        <li>
          <a href="{{ route('tags.index') }}">
            @lang('messages.tags')
          </a>
        </li>
          <li class="dropdown">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
                @lang('messages.auxiliary') <span class="caret"></span>
            </a>
            <ul class="dropdown-menu" role="menu">
              <li>
                <a href="{{ route('references.index') }}">
                  @lang('messages.references')
                </a>
              </li>
        			<li>
                <a href="{{ route('biocollections.index') }}">
                  @lang('messages.biocollections')
                </a>
              </li>

        			<li>
                <a href="{{ route('persons.index') }}">
                  @lang('messages.persons')
                </a>
              </li>
        			<li>
                <a href="{{ route('traits.index') }}">
                  @lang('messages.traits')
                </a>
              </li>
              <!-- requires testing
        			<li>
                <a href="{{ route('forms.index') }}">
                  @lang('messages.forms')
                </a>
              </li>
            -->
            @can ('show', App\Models\User::class)
            <li>
              <a href="{{ route('users.index') }}">
                @lang('messages.users')
              </a>
            </li>
            @endcan

            </ul>
          </li>

      @if(Auth::user())
          <li class="dropdown">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
                @lang('messages.imports') <span class="caret"></span>
            </a>
            <ul class="dropdown-menu" role="menu">
              <li>
                <a href="{{ route('references.index') }}">
                  @lang('messages.references')
                </a>
              </li>
              <li><a href="{{ url('import/biocollections') }}">
                @lang('messages.biocollections')
              </a></li>
              <li><a href="{{ url('import/individuals') }}">
                @lang('messages.individuals')
              </a></li>
              <li><a href="{{ url('import/individuallocations') }}">
                @lang('messages.individuallocations')
              </a></li>
              <li><a href="{{ url('import/locations') }}">
                @lang('messages.locations')
              </a></li>
              <li><a href="{{ url('import/measurements') }}">
                @lang('messages.measurements')
              </a></li>
              <li><a href="{{ url('import/persons') }}">
                @lang('messages.persons')
              </a></li>
              <li><a href="{{ url('media/import-form') }}">
                @lang('messages.media_files')
              </a></li>
              <li><a href="{{ url('import/traits') }}">
                @lang('messages.traits')
              </a></li>
              <li><a href="{{ url('import/taxons') }}">
                @lang('messages.taxons')
              </a></li>
              <li><a href="{{ url('import/vouchers') }}">
                @lang('messages.vouchers')
              </a></li>
            </ul>
          </li>
        @endif





        <!-- Right Side Of Navbar
        </ul>
        <ul class="nav navbar-nav navbar-right">



        Authentication Links -->
        @if (Auth::guest())
          <li>
            <a href="{{ route('login') }}">
              @lang('messages.login')
            </a>
          </li>
          <li>
            <a href="{{ route('register') }}">
              @lang('messages.register')
            </a>
          </li>
        @else
          <li class="dropdown">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
                <i class="fas fa-user-cog fa-lg"></i><span class="caret"></span>
            </a>
            <ul class="dropdown-menu" role="menu">
              <li>
                <a href="{{ route('selfedit') }}">
                  <i class="fas fa-user-tag"></i>
                  @if (Auth::user()->person)
                  {{ Auth::user()->person->full_name }}
                  <br>
                  @else
                    {{ Auth::user()->email }}
                  @endif
                </a>
              </li>
              <li>
                <a href="{{ route('selfedit') }}">
                  @lang('messages.edit_profile')
                </a>
              </li>
              <li>
                <a href="{{ url('token') }}">
                  @lang('messages.api_token')
                </a>
              </li>

              @can ('index', App\Models\UserJob::class)
              <li>
                <a href="{{ route('userjobs.index') }}">
                  @lang('messages.userjobs')
                </a>
              </li>
              @endcan

              <li>
                <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                  Logout
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                  {{ csrf_field() }}
                </form>
              </li>
            </ul>
          </li>
        @endif



        <li class="dropdown">
          <a href="{{ url('docs')}}">
            <i class="fas fa-question-circle fa-lg"></i>
          </a>
        </li>

          <li class="dropdown">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
              <i class="fas fa-language fa-lg"></i>
              <span class="caret"></span>
            </a>
            <ul class="dropdown-menu" role="menu">
              @foreach (Config::get('languages') as $lang => $language)
                @if(Session::get('applocale') !== $lang)
                <li>
                  @if(Auth::user())
                    <a href="{{ url('home/'.$lang) }}">
                  @else
                    <a href="{{ url('welcome/'.$lang) }}">
                  @endif
                    {{ $language }}</a></li>
                @endif
              @endforeach
            </ul>
          </li>
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
