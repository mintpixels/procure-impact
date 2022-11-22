@extends('store.layout')

@section('content')

<div id="login-page" class="padded-content page" style="max-width:500px;margin:auto">
<br><br><br>
    <h1 style="margin-bottom:40px">Account Sign In</h1>
            
    <form action="/account/login" method="post">
        
        <div class="field">
            <label style="font-size:18px">Email</label>
            <input type="email" name="email" />
        </div>

        <div class="field">
            <label style="font-size:18px">Password</label>
            <input type="password" name="password" />
        </div>

        @if(session('loginError'))
        <div class="error">
            {{ session('loginError') }}
        </div>
        @endif

        <div class="form-actions">
            <button>Sign In</button>
            <!-- &nbsp;&nbsp;
            <a class="forgot-password" href="/account/forgot">Forgot your password?</a> -->
        </div>
    </form>
</div>

@stop

