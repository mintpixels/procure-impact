<div id="side-cart" class="HERE">
    <div  style="height:100%">
        <div class="primary">
            <div class="header">
                <h4>Shopping Cart</h4>
            
                <img src="/img/x.svg" v-on:click="hideSideCart" />
            </div>

            <div class="empty-cart" v-if="cart.items.length == 0">
                <h5>Your cart is empty</h5>
                <p>It feels desperately alone</p>
                <button v-on:click="hideSideCart">Let's shop</button>
            </div>

            <div v-if="error" class="error">
                ${ error }
            </div>

            <div class="line-items">
                <div class="minimums" v-if="minimums.length > 0">
                    <p>You haven't met the suggested minimums set by the following merchants.</p>
                    <table>
                        <thead>
                        <tr>
                            <th>Merchant</th>
                            <th class="text-right">Total $</th>
                            <th class="text-right">Minimum $</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="min in minimums">
                            <td>${ min.brand.name }</td>
                            <td class="text-right">${ formatMoney(min.total) }</td>
                            <td class="text-right">${ formatMoney(min.min) }</td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <div class="item columns" v-for="item in cart.items">
                    <img class="remove" src="/img/x.svg" v-on:click="setItemQuantity(item, 0)" />
                  
                    <div class="column image">
                        <img v-if="item.variant.image != NULL" :src="item.product.images[item.variant.image]"/>
                        <img v-else :src="item.product.thumbnanil" />
                    </div>

                    <div class="column">
                        <div class="name">
                            <a :href="'/products/'+item.product.handle">${ item.product.name }</a>
                            <div class="variant-name">${ item.variant.name }</div>
                        </div>
                        <div class="brand">by ${ item.product.brand.name}</div>
                        <div class="price">
                            ${ formatMoney(item.price * item.quantity) }
                        </div>
                        <div class="quantity">
                            <i class="fas fa-chevron-down" v-on:click="setItemQuantity(item, item.quantity - 1)"></i>
                            <input v-model="item.quantity" type="text" @change="setItemQuantity(item, item.quantity)" />
                            <i class="fas fa-chevron-up" v-on:click="setItemQuantity(item, item.quantity + 1)"></i>
                        </div>
                        <div class="item-options" v-if="item.properties && item.properties.length > 0">
                            <div class="item-option" v-for="prop in item.properties">
                                <span class="option-name">${ prop.name }:</span>
                                <span class="option-value">${ prop.value }</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            
            <div class="footer" v-if="cart.items.length > 0">

                <div class="summary">
                    <h5>Order Summary</h5>

                    <div class="totals">
                        <div class="columns row">
                            <label class="column">Subtotal:</label>
                            <span class="column">${ formatMoney(cart.subtotal) }</span>
                        </div>
                    </div>

                    <div class="totals">
                        <div class="columns row">
                            <label class="column">Tax Estimate:</label>
                            <span class="column">${ formatMoney(0) }</span>
                        </div>
                    </div>

                    <div class="totals total">
                        <div class="columns row">
                            <label class="column">Total:</label>
                            <span class="column">${ formatMoney(cart.subtotal) }</span>
                        </div>
                    </div>

                    <div v-if="cart.items.length > 0">
                        <button class="button" v-if="!error" v-on:click="goToCheckout()">Submit PO for Review</button>
                    </div>
                </div>
                
            </div>
            
        </div>

    </div>
  </div>
</div>