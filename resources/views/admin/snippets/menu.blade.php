
<div class="menu">
    @if(Auth::user()->isAdmin())
        <span>
            <a href="/admin/brands">Social Enterprises</a>
        </span>
    @endif
    @if(Auth::user()->isAdmin())
        <span>
            <a href="/admin/buyers">Purchasers</a>
        </span>
    @endif
    @if(Auth::user()->isAdmin())
        <span>
            <a href="/admin/categories">Categories</a>
        </span>
    @endif
    <span>
        <a href="/admin/products">Products</a>
    </span>
    <span>
        <a href="/admin/orders">Orders</a>
    </span>
    <span>
        <a href="/admin/customers">Customers</a>
    </span>
    <span>
        <a href="/admin/users">Users</a>
    </span>
</div>