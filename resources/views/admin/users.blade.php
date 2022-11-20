@extends('admin.layout')

@section('content')

<div id="users-page" class="padded-content crud">

  <h1>
    Users
    <div class="actions">
      <a href="/admin/users/create" class="button small">Create User</a>
    </div>
  </h1>

  <div class="filter-bar">
    <form method="get" action="/admin/users">
        <input type="text" name="search" value="{{ isset($_GET['search']) ? $_GET['search'] : '' }}" placeholder="Search users..." />
    </form>
  </div>

  <div class="section">

    <div class="overflow">
        <table style="margin-top:20px">
            <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>

                @if(Auth::user()->isAdmin())
                <th>Brand</th>
                @endif

                <th>Type</th>
            </tr>
            </thead>
            
            <tbody>
            @foreach($users as $user)

                <tr>
                    <td>
                        <a href="/admin/users/{{ $user->id }}">{{ $user->name }}</a>
                    </td>
                    <td>
                        {{ $user->email }}
                    </td>

                    @if(Auth::user()->isAdmin())
                    <td>
                        {{ $user->brand->name ?? 'Admin User'}}
                    </td>
                    @endif

                    <td>
                        {{ $user->type }}
                    </td>
                </tr>

            @endforeach
            </tbody>
        </table>
    </div>

</div>

@stop
