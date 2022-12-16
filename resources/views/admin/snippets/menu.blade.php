
<div class="menu">
    <span>
        <a href="/admin/orders">Orders</a>
    </span>
    <span>
        <a href="/admin/products">Products</a>
    </span>
    @if(Auth::user()->isAdmin())
        <span>
            <a href="/admin/transactions">Transactions</a>
        </span>
        <span>
            <a href="/admin/categories">Categories</a>
        </span>
        <span>
            <a href="/admin/properties">Properties</a>
        </span>
        <span>
            <a href="/admin/brands">Vendors</a>
        </span>
        <span>
            <a href="/admin/buyers">Buyers</a>
        </span>
        <span>
            <a href="/admin/customers">Customers</a>
        </span>
        <span>
            <a href="/admin/users">Users</a>
        </span>
        <span>
            <a href="/admin/settings">Settings</a>
        </span>
    @endif

    <span>
        <a href="/admin/logout">Logout</a>
    </span>
</div>