@extends('store.layout')

@section('content')

<div id="account-page" page="settings" class="padded-content page">

    <h1 class="text-center">Account Settings</h1>

    @include('store.account.menu')

    @if ($errors->any())
        <div class="warning">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('error'))
        <div class="warning">{{ session('error' )}}</div>
    @endif

    @if(session('status'))
        <div class="status">{{ session('status' )}}</div>
    @endif

    <div class="narrow">
        <form method="POST" action="/account/settings" enctype="multipart/form-data">
            <div class="columns padded">

                <div class="column">
                    <div class="field">
                        <label>First Name *</label>
                        <input type="text" name="first_name" required value="{{ htmlspecialchars($customer->first_name, ENT_QUOTES) }}" />
                    </div>
                </div>
                <div class="column">
                    <div class="field">
                        <label>Last Name *</label>
                        <input type="text" name="last_name" required value="{{ htmlspecialchars($customer->last_name, ENT_QUOTES) }}" />
                    </div>
                </div>
            </div>

            <div class="columns padded">

                <div class="column">
                    <div class="field">
                        <label>Email Address *</label>
                        <input type="text" name="email" required value="{{ htmlspecialchars($customer->email, ENT_QUOTES) }}"  />
                    </div>
                </div>
                <div class="column">
                    <div class="field">
                        <label>Phone *</label>
                        <input type="text" name="phone" required value="{{ htmlspecialchars($customer->phone, ENT_QUOTES) }}" />
                    </div>
                </div>
            </div>

            <div class="columns padded">
                <div class="column">
                    <div class="field">
                        <label>Password</label>
                        <input type="password" name="password" />
                    </div>
                </div>
                <div class="column">
                    <div class="field">
                        <label>Current Password</label>
                        <input type="password" name="current_password" />
                    </div>
                </div>
            </div>

            <br><br>


            <div class="field">
                <label for="name">Compliance Document</label>
                @if(Auth::guard('customer')->user()->buyer->document)
                    <a href="/documents/{{ Auth::guard('customer')->user()->buyer->document }}">{{ Auth::guard('customer')->user()->buyer->document }}</a>
                    <br><br>
                @endif
                <input type="file" name="document" />
            </div>

            <br><br>

            <div class="text-center actions">
                <button>Update Settings</button>
            </div>
        </form>
    </div>
</div>

@stop

