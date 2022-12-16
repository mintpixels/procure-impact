@extends('admin.layout')

@section('content')

<div id="settings-page" class="padded-content crud">

    <div class="section">

        <h4>Settings</h4>

        <form method="post" class="crud" action="/admin/settings" enctype="multipart/form-data">
            {{ csrf_field() }}

            <div class="columns field">
                <div class="field column">
                    <label for="name">Buyer Fee</label>
                    <input type="text" name="buyer_fee" value="{{ $settings->buyer_fee }}" required/>
                </div>

                <div class="field column">
                    <label for="name">Brand Fee</label>
                    <input type="text" name="brand_fee" value="{{ $settings->brand_fee }}" required />
                </div>
            </div>

            <div class="columns field">

                <div class="field column">
                    <label for="name">Order Email</label>
                    <input type="email" name="order_email" value="{{ $settings->order_email }}" required/>
                </div>
            </div>

        
            <div class="actions">
                <button>Save Settings</button>
            </div>
        </form>
    </div>
</div>

@stop
