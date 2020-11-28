<!doctype html>
<html lang="{{ config('app.locale') }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Open Data Bio</title>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css?family=Raleway:100,600" rel="stylesheet" type="text/css">

        <!-- Styles -->
        <style>
            html, body {
                background-color: #fff;
                color: #636b6f;
                font-family: 'Raleway', sans-serif;
                font-weight: 100;
                height: 100vh;
                margin: 0;
            }

            .full-height {
                height: 100vh;
            }

            .flex-center {
                align-items: center;
                display: flex;
                justify-content: center;
            }

            .position-ref {
                position: relative;
            }

            .top-right {
                position: absolute;
                right: 10px;
                top: 18px;
            }

            .content {
                text-align: center;
            }

            .title {
                font-size: 84px;
            }
            .subtitle {
                font-size: 28px;
            }

            .links > a {
                color: #636b6f;
                padding: 0 25px;
                font-size: 12px;
                font-weight: 600;
                letter-spacing: .1rem;
                text-decoration: none;
                text-transform: uppercase;
            }

            .m-b-md {
                margin-bottom: 30px;
            }
        </style>
    </head>
    <body>
        <div class="flex-center position-ref full-height">
            @if (Route::has('login'))
                <div class="top-right links">
                    @if (Auth::check())
                        <a href="{{ url('/home') }}">
@lang('messages.home')
</a>
                    @else
                        <a href="{{ url('/login') }}">
@lang('messages.login')
</a>
                        <a href="{{ url('/register') }}">
@lang('messages.register')
</a>
                    @endif
                </div>
            @endif

            <div class="content">
                <div class="title m-b-md">
                    Open Data Bio
                </div>
                <div class="subtitle m-b-md">
		    @lang ('messages.version', ['version' => config('app.version')])
                </div>
                <div class="subtitle m-b-md">
		@lang ('messages.subtitle')
                </div>

                <div class="links">
                    <a href="{{ route('home') }}">
@lang ('messages.home')
</a>
                    <a href="{{ url('docs') }}" target="_blank">
@lang('messages.docs')
</a>
                    <a href="https://github.com/opendatabio/opendatabio" target="_blank">GitHub</a>
                </div>


		<div class="links" style="margin-top:20px;">
    <div class="links">
        @foreach (Config::get('languages') as $lang => $language)
                    <a href="{{ url('welcome/'.$lang) }}">{{$language}}</a>&nbsp;
        @endforeach
    </div>
            </div>
        </div>
    </body>
</html>
