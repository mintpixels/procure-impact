@extends('store.layout')

@section('content')

<div id="account-page"  page="orders" class="padded-content page" v-cloak>

    <h1 class="text-center">Orders</h1>
    
    @include('store.account.menu')
    
    <div v-if="!loaded" class="loader">
        <!-- <img src="/img/loading.gif" />   -->
    </div>
    <div v-else>
        <div class="account-view" v-if="view == 'Orders' && !order">
            <div v-for="order in orders" class="account-order">
                <div class="columns">
                    <div class="column order-image">
                        <img :src="order.items[0].product.thumbnail.indexOf('http') == 0 ? order.items[0].product.thumbnail : '{{ env('AWS_CDN_PRODUCTS_PATH') }}' + order.items[0].product.thumbnail" v-if="order.items.length > 0 && order.items[0].product && order.items[0].product.thumbnail" />
                    </div>
                    <div class="column">
                        <div class="order-name">
                            <a :href="'/account/orders/' + order.id + ''">Order #${ order.id }</a>
                        </div>
                        <div class="order-totals">
                            ${ order.items.length }
                            ${ order.items.length == 1 ? 'product' : 'products' }
                            totaling ${ formatMoney(order.total) }
                        </div>

                        <div class="order-placed">
                            <h5>Order Placed</h5>
                            ${ formatDate(order.created_at) }
                        </div>

                        <div class="order-updated">
                            <h5>Last Updated</h5>
                            ${ formatDate(order.updated_at) }
                        </div>
                    </div>

                    <div class="column status">
                        <span class="status">${ order.status }</span>
                        <div v-if="order.status == 'Completed'">
                            <a :href="'/account/orders/' + order.id + '/return'">Return Items?</a>
                        </div>
                    </div>
                    
                </div>
            </div>

            <div v-if="pages > 1" class="pagination">
                <span v-for="p in pages" v-on:click="changeOrderPage(p)" :class="{ active: p == page }">${ p }</span>
            </div>

        </div>
        
    </div>
</div>

@stop

