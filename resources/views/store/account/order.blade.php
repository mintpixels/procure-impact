@extends('store.layout')

@section('content')

<div id="account-page" order="{{ $id }}" page="order" class="padded-content page" v-cloak>

    <h1 class="text-center" v-if="loaded">Order #${ order.id }</h1>
    
    @include('store.account.menu')
    
    <div v-if="!loaded" class="loader">
        <!-- <img src="/img/loading.gif" />   -->
    </div>

    <div v-else>
           
        <div class="account-view" v-if="order">
            <div class="columns">
                <div class="column">
                    <h2>Order Contents</h2>
                    <div class="order-items">
                        <div class="columns order-item" v-for="item in order.items">
                            <span class="column item-image">
                                <img v-if="item.product && item.product.thumbnail" :src="item.product.thumbnail.indexOf('http') == 0 ? item.product.thumbnail : '{{ env('AWS_CDN_PRODUCTS_PATH') }}' + item.product.thumbnail" />
                            </span>
                            <span class="column item-name">${ item.quantity } x ${ item.name }</span>
                            <span class="column line-price">${ formatMoney(item.line_price) }</span>
                        </div>
                        <div class="columns order-item summary">
                            <span class="column item-image"></span>
                            <span class="column item-name">Subtotal</span>
                            <span class="column line-price">${ formatMoney(order.subtotal) }</span>
                        </div>
                        <div class="columns order-item summary" v-if="order.insurance > 0">
                            <span class="column item-image"></span>
                            <span class="column item-name">Insurance</span>
                            <span class="column line-price">${ formatMoney(order.insurance) }</span>
                        </div>
                        <div class="columns order-item summary" v-if="order.tax > 0">
                            <span class="column item-image"></span>
                            <span class="column item-name">Tax</span>
                            <span class="column line-price">${ formatMoney(order.tax) }</span>
                        </div>
                        <div class="columns order-item summary">
                            <span class="column item-image"></span>
                            <span class="column item-name">Total</span>
                            <span class="column line-price total">${ formatMoney(order.total) }</span>
                        </div>
                    </div>
                </div>
                <div class="column order-details">
                    <div class="section">
                        <h2>Order Details</h2>
                        <div class="field">
                            <label>Order status:</label>
                            <span>${ order.status }</span>
                        </div>
                        <div class="field">
                            <label>Order date:</label>
                            <span>${ formatDate(order.created_at) }</span>
                        </div>
                        <div class="field">
                            <label>Order total:</label>
                            <span>${ formatMoney(order.total) }</span>
                        </div>
                    </div>

                    <div class="section" v-if="order.shipments.length > 0">
                        <h2>Ship To</h2>
                        <div v-for="shipment in order.shipments" class="ship-to">
                            ${ shipment.first_name } ${ shipment.last_name } <br>
                            <div v-if="shipment.company">
                                ${ shipment.company }
                            </div>
                            ${ shipment.address1 } ${ shipment.address2 }<br>
                            ${ shipment.city }, ${ shipment.state } ${ shipment.zip }
                        </div>
                    </div>

                    <div class="section" v-if="order.billing">
                        <h2>Bill To</h2>
                        ${ order.billing.first_name } ${ order.billing.last_name } <br>
                        <div v-if="order.billing.company">
                            ${ order.billing.company }
                        </div>
                        ${ order.billing.address1 } ${ order.billing.address2 }<br>
                        ${ order.billing.city }, ${ order.billing.state } ${ order.billing.zip }
                    </div>
                    
                    <div class="section" v-if="order.status == 'Completed'">
                        <h2>Actions</h2>

                        <a class="button" :href="'/account/orders/' + order.id + '/return'">Return Items</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@stop

