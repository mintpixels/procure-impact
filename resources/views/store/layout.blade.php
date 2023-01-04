<!doctype html>
<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" value="{{ csrf_token() }}">

        <title>
            @if(isset($pageTitle))
            {{ $pageTitle }} - Procure Impact
            @else
            Procure Impact
            @endif
        </title>

        <link href="https://fonts.googleapis.com/css?family=Playfair+Display:400,500,600,700" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css?family=Inter:400,500,600,700" rel="stylesheet">
        <link rel="stylesheet" href="https://use.typekit.net/shl1pyf.css">
        <link rel="icon" type="image/x-icon" href="/favicon.png">
        <script src="https://kit.fontawesome.com/21e055b1b3.js" crossorigin="anonymous"></script>
        
        <link rel="stylesheet" href="{{ mix('/css/store.css') }}" />

        @yield('head')
       
    </head>
    <body>

        <div class="overlay"></div>

        <div class="hello-bar">
        The bridge between high-quality products that transform lives and mission-driven companies.
        </div>

        @if(!isset($noheader))
            <div class="top-bar columns">
                <div class="column logo">
                    <a href="/"><img src="{{ asset('img/logo.svg') }}" /></a>
                </div>  

                <div class="column search">

                    <form method="get" action="/search">
                    <div class="search-box">
                        
                            <div class="icon">
                                <img src="{{ asset('img/search.svg') }}" />
                            </div>
                            <input type="text" name="qs" placeholder="Search" />
                    </div>
                    </form>

                </div>

                <div class="column links">
                    <span class="cart-icon">
                        <img src="{{ asset('img/cart.svg') }}" class="side-cart-trigger"/>
                        <span class="cart-item-count"></span>
                    </span>
                    <a href="/account"><img src="{{ asset('img/account.svg') }}" /></a>
                </div>
            </div>

            @include('store.snippets.menu')
        @endif

        <div class="content">
            @yield('content')
        </div>

        @include('store.snippets.footer')
        @include('store.snippets.side-cart')

        <script src="https://unpkg.com/dayjs@1.8.21/dayjs.min.js"></script>
        <script src="{{ mix('js/store.js') }}"></script>
    </body>
</html>

@yield('scripts')