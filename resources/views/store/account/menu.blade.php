<div class="account-menu text-center">
    <a href="/account" @if($page == 'orders') class="active" @endif>Orders</a>
    <a href="/account/settings" @if($page == 'settings') class="active" @endif>Settings</a>
    <a href="/account/logout">Logout</a>
</div>