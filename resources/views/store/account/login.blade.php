@extends('store.layout')

@section('content')

<div id="login-page" class="padded-content page">
    <h1>Account Sign In</h1>
            
    <form action="/account/login" method="post">
        
        <div class="field">
            <label>Email</label>
            <input type="email" name="email" />
        </div>

        <div class="field">
            <label>Password</label>
            <input type="password" name="password" />
        </div>

        @if(session('loginError'))
        <div class="error">
            {{ session('loginError') }}
        </div>
        @endif

        <div class="form-actions">
            <button>Sign In</button>
            <a class="forgot-password" href="/account/forgot">Forgot your password?</a>
        </div>
    </form>
</div>

@stop

