@extends('admin.layout')

@section('content')

<div id="buyer-page" class="padded-content crud">

    <div class="section">
        <div class="breadcrumb">
            <a href="/admin/buyers">Buyer</a> / {{ $buyer->name }}
        </div>


        @if($buyer->id)
            <h4>
                {{ $buyer->name }}
            </h4>
        @else
            <h4>New Buyer</h4>
        @endif


        <form method="post" class="crud" action="/admin/buyers/{{ $buyer->id ? $buyer->id : 'create' }}" enctype="multipart/form-data">
            {{ csrf_field() }}

            <div class="field">
                <label for="name">Name</label>
                <input type="text" name="name" value="{{ $buyer->name }}" required/>
            </div>

            <div class="field">
                <label for="name">Email</label>
                <input type="text" name="email" value="{{ $buyer->email }}" required/>
            </div>

            <div class="field">
                <label for="name">Description</label>
                <textarea name="description">{{ $buyer->description }}</textarea>
            </div>


            <div class="field">
                <label for="name">Type</label>
                <select name="type" required>
                    <option @if($buyer->type == 'Wholesale') selected @endif>Wholesale</option>
                    <option @if($buyer->type == 'Retail') selected @endif>Retail</option>
                </select>
            </div>

            <div class="field">
                <label for="name">Compliance Document</label>
                @if($buyer->document)
                    <a href="/documents/{{ $buyer->document }}">{{ $buyer->document }}</a>
                    <br><br>
                @endif
                <input type="file" name="document" />
            </div>

            <div class="actions">
                <button>Save Buyer</button>

                @if($buyer->id)
                    <!-- <a class="delete" data-entity="{{ $buyer->id }}" data-route="admin/buyer" data-type="buyer">Delete Buyer</a> -->
                @endif
            </div>
        </form>
    </div>
</div>

@stop
