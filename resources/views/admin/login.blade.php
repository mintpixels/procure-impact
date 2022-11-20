<!doctype html>
<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" value="{{ csrf_token() }}">

        <title>Procure Impact</title>

        <link href="https://fonts.googleapis.com/css?family=Montserrat:400,500,700|Saira:100, 200, 300,400,500,600,700,900|Teko:300,500" rel="stylesheet">
        <link rel="stylesheet" href="/css/admin.css" />
    </head>
    <body class="login">

        <div id="login-box">
            <form method="post" action="/admin/login">
                @csrf

                <div class="field">
                    <label>Email</label>
                    <input type="text" name="email" />
                </div>

                <div class="field">
                    <label>Password</label>
                    <input type="password" name="password" />
                </div>

                @if(session('error'))
                    <div class="error">{{ session('error') }}</div>
                @endif


                <button>Login</button>

            </form>
        </div>

    </body>
</html>
