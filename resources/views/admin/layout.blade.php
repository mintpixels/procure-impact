<!doctype html>
<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" value="{{ csrf_token() }}">

        <title>Procure Impact</title>

        <link href="https://fonts.googleapis.com/css?family=Playfair+Display:400,500,600,700" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css?family=Inter:400,500,600,700" rel="stylesheet">
        <link rel="stylesheet" href="https://use.typekit.net/shl1pyf.css">
        <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
        <script src="https://kit.fontawesome.com/b493cac541.js" crossorigin="anonymous"></script>
        
        <link rel="stylesheet" href="{{ mix('/css/admin.css') }}" />

        @yield('head')
       
    </head>
    <body>

        <header>
            <div class="column logo">
                <a href="/"><img src="{{ asset('img/logo.svg') }}" /></a>
            </div>  

            @include('admin.snippets.menu')
        </header>

        <div id="content">
            @if(session()->has('status'))
            <div class="status">
                {{ session('status') }}
            </div>
            @endif
            @yield('content')
        </div>

        <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
        <script src="https://unpkg.com/dayjs@1.8.21/dayjs.min.js"></script>
        <script src="{{ mix('js/admin.js') }}"></script>
    </body>
</html>

@yield('scripts')