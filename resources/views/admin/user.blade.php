@extends('admin.layout')

@section('content')

<div id="user-page" class="padded-content crud">

    <div class="section">
        <div class="breadcrumb">
            <a href="/admin/users">Users</a> / {{ $user->name }}
        </div>


        @if($user->id)
            <h4>
                {{ $user->name }}
            </h4>
        @else
            <h4>New User</h4>
        @endif


        <form method="post" class="crud" action="/admin/users/{{ $user->id ? $user->id : 'create' }}" enctype="multipart/form-data">
            {{ csrf_field() }}

            <div class="field">
                <label for="name">Name</label>
                <input type="text" name="name" value="{{ $user->name }}" required/>
            </div>

            <div class="field">
                <label for="name">Email</label>
                <input type="text" name="email" value="{{ $user->email }}" required/>
            </div>

            <div class="field">
                <label for="name">Password</label>
                <input type="password" name="password" />
            </div>

            <div class="field">
                <label for="name">Brand</label>
                <select name="brand_id">
                    <option value="">All</option>
                    @foreach($brands as $brand)
                        <option value="{{ $brand->id }}" @if($brand->id == $user->brand_id) selected @endif>{{ $brand->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="name">Type</label>
                <select name="type" required>
                    <option @if($user->type == 'All') selected @endif>All</option>
                    <option @if($user->type == 'Wholesale') selected @endif>Wholesale</option>
                    <option @if($user->type == 'Retail') selected @endif>Retail</option>
                </select>
            </div>

            <div class="actions">
                <button>Save User</button>

                @if($user->id)
                    <a class="delete" data-entity="{{ $user->id }}" data-route="admin/users" data-type="user">Delete User</a>
                @endif
            </div>
        </form>
    </div>
</div>

@stop
