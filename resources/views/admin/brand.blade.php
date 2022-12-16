@extends('admin.layout')

@section('content')

<div id="brand-page" class="padded-content crud">

    <div class="section">
        <div class="breadcrumb">
            <a href="/admin/brands">Vendors</a> / {{ $brand->name }}
        </div>


        @if($brand->id)
            <h4>
                {{ $brand->name }}
            </h4>
        @else
            <h4>New Brand</h4>
        @endif


        <form method="post" class="crud" action="/admin/brands/{{ $brand->id ? $brand->id : 'create' }}" enctype="multipart/form-data">
            {{ csrf_field() }}

            <div class="columns field">
                <div class="field column">
                    <label for="name">Name</label>
                    <input type="text" name="name" value="{{ $brand->name }}" required/>
                </div>
            </div>

            <div class="columns field">
                <div class="field column">
                    <label for="name">Contact Name</label>
                    <input type="text" name="contact_name" value="{{ $brand->contact_name }}" required/>
                </div>

                <div class="field column">
                    <label for="name">Contact Email</label>
                    <input type="email" name="contact_email" value="{{ $brand->contact_email }}" required/>
                </div>
            </div>

            <div class="columns field">
                <div class="field column">
                    <label for="name">Bill.com Vendor ID</label>
                    <input type="text" name="bill_id" value="{{ $brand->bill_id }}" />
                </div>
                <div class="field column">
                    <label for="name">Minimum Order</label>
                    <div class="input-with-label">
                        <span>$</span>
                        <input type="text" name="order_min" value="{{ $brand->order_min }}" />
                    </div>
                </div>
            </div>

            <div class="field">
                <label for="name">Description</label>
                <textarea name="description">{{ $brand->description }}</textarea>
            </div>

            <div class="field">
                <input type="checkbox" name="is_active" @if($brand->is_active) checked @endif/> This brand is enabled.
            </div>

            <div class="actions">
                <button>Save Vendor</button>

                @if($brand->id)
                    <!-- <a class="delete" data-entity="{{ $brand->id }}" data-route="admin/brands" data-type="brand">Delete Brand</a> -->
                @endif
            </div>
        </form>
    </div>
</div>

@stop
