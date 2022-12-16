<!doctype html>
<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" value="{{ csrf_token() }}">

        <title>Procure Impact</title>

        <link href="https://fonts.googleapis.com/css?family=Playfair+Display:400,500,600,700" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css?family=Inter:400,500,600,700" rel="stylesheet">
        <link rel="stylesheet" href="https://use.typekit.net/shl1pyf.css">
        <script src="https://kit.fontawesome.com/21e055b1b3.js" crossorigin="anonymous"></script>
        
        <script src="https://unpkg.com/vue@next"></script>
        
        <link rel="stylesheet" href="{{ mix('/css/store.css') }}" />
       
    </head>
    <body class="checkout">

        <header>
            <a href="/">
                <img src="/img/logo.svg" style="height:60px">
            </a>
        </header>

        <div id="checkout" data-checkout-id="{{ $checkout->guid }}" v-cloak>

            <div id="checkout-content" class="content columns" :class="{ loaded: loaded }">

                <div class="column information">
                    <!-- <div class="section" data-section="customer">
                        <h2>
                            <span class="number">1</span>
                            Customer 
                            <span class="email" v-if="stepIndex > 1">
                                <span class="info">${ checkout.email }</span>
                                <a v-on:click="step = 'customer';stepIndex = 1;" v-if="!checkout.customer_id">Edit</a>
                                <a v-on:click="signout()" v-if="checkout.customer_id">Sign Out</a>
                            </span> 
                        </h2>
                        <div class="customer-section section-content" :class="{ show: stepIndex == 1 }">
                            <div v-if="!showSignIn">
                                <label>Email</label>
                                <div class="columns layout">
                                    <div class="field customer-input column">
                                        <input type="text" v-model="checkout.email" />

                                        <div class="suboption">
                                            Already have an account? <a v-on:click="showSignIn = true">Sign in now</a>
                                        </div>
                                    </div>
                                    <div class="column customer-button">
                                        <button v-on:click="saveCustomer" :disabled="!validCustomer()">Continue as Guest</button>
                                    </div>
                                </div>
                            </div>
                            <div v-else>
                                <div class="field">
                                    <label>Email</label>
                                    <input type="text" v-model="signinEmail" />
                                </div>

                                <div class="field">
                                    <label>Password</label>
                                    <input type="password" v-model="signinPassword" />
                                </div>

                                <div class="error" v-if="signinError">${ signinError }</div>

                                <button v-on:click="attemptSignin">Sign In</button>
                                <a class="cancel-signin" v-on:click="showSignIn = false;signinError = ''">cancel</a>
                            </div>
                        </div>  
                    </div> -->

                    <div class="section" data-section="customer">
                        <h3><span class="number">1</span> Billing  <a v-on:click="step = 'billing';stepIndex = 1;" v-if="stepIndex > 1">Edit</a></h3>

                        <div class="billing-address">
                            
                            <div class="section-content" :class="{ show: step == 'billing' }">
                                <h5>Billing Address</h5>
                                <select v-model="checkout.billing.id" v-on:change="selectBillingAddress" v-if="checkout.addresses.length > 0">
                                    <option value="">New Address</option>
                                    <option v-for="(address, i) in checkout.addresses" :value="address.id">
                                        ${ address.first_name } ${ address.last_name },
                                        ${ address.address1 } ${ address.address2 },
                                        ${ address.city }, ${ address.state } ${ address.zip }
                                    </option>
                                </select>

                                <div v-if="checkout.billing.id == '' || checkout.addresses.length == 0">
                                    @include('store.snippets.address', ['address' => 'checkout.billing', 'shipment' => '{}', 'onChange' => ''])
                                </div>
                                <button v-on:click="saveBilling(checkout.billing)" :disabled="!validAddress(checkout.billing)">Continue</button>
                            </div>

                            <div class="shipping-summary section-content" :class="{ show: stepIndex > 2 }" v-if="checkout.billing.first_name">
                                <h5>Billing Address</h5>
                                ${ checkout.billing.first_name } ${ checkout.billing.last_name }<br>
                                ${ checkout.billing.address1 } ${ checkout.billing.address2 }<br>
                                ${ checkout.billing.city }, ${ checkout.billing.state } ${ checkout.billing.zip }

                            </div>
                        </div>

                    </div>

                    <div class="section" data-section="shipping">
                        <h3>
                            <span class="number">2</span> 
                            Shipping 
                            <a v-on:click="step = 'shipping';stepIndex = 3" v-if="stepIndex > 3">Edit</a></h3>
                        </h3>
                        <div class="shipping-section section-content" :class="{ show: stepIndex > 3 }">
                            <div class="shipment-details">
                                 
                                    <div>
                                        <h5>Shipping Address</h5>
                                        <div v-if="checkout.use_billing">
                                            ${ checkout.billing.first_name }
                                            ${ checkout.billing.last_name }<br>
                                            ${ checkout.billing.address1 }
                                            ${ checkout.billing.address2 }<br>
                                            ${ checkout.billing.city },
                                            ${ checkout.billing.state }
                                            ${ checkout.billing.zip }<br>
                                        </div>
                                        <div v-else>
                                            ${ checkout.shipping.first_name }
                                            ${ checkout.shipping.last_name }<br>
                                            ${ checkout.shipping.address1 }
                                            ${ checkout.shipping.address2 }<br>
                                            ${ checkout.shipping.city },
                                            ${ checkout.shipping.state }
                                            ${ checkout.shipping.zip }<br>
                                        </div>
                                    </div>

                            </div>
                        </div>
                        <div class="shipping-section section-content" :class="{ show: step == 'shipping' }">

                            <div class="use-billing"  v-if="!checkout.is_pickup">
                                <input id="use-billing" type="checkbox" v-model="checkout.use_billing" @change="saveUseBilling" :disabled="!shippingCanBeDifferent()" /> 
                                <label for="use-billing">Use Billing Address</label>
                            </div>

                            <div v-if="!checkout.use_billing" class="shipping-address">
                                <!-- <select v-model="checkout.shipping.id" v-on:change="selectShippingAddress" v-if="checkout.addresses.length > 0">
                                    <option value="">New Address</option>
                                    <option v-for="(address, i) in checkout.addresses" :value="address.id">
                                        ${ address.first_name } ${ address.last_name },
                                        ${ address.address1 } ${ address.address2 },
                                        ${ address.city }, ${ address.state } ${ address.zip }
                                    </option>
                                </select> -->
                                @include('store.snippets.address', ['address' => 'checkout.shipping', 'shipment' => '{}', 'onChange' => ''])
                            </div>
                            <br>
                            <button v-on:click="saveShipping" :disabled="!validShipping()">Continue</button>
                        </div>
                    </div>

                    <div class="section" data-section="customer" v-if="!checkout.approved">

                        <h3>
                            <span class="number">3</span> 
                            Submit 
                        </h3>

                        <div class="section-content" :class="{ show: stepIndex == 4 }">

                          

                            <div class="subsection">

                                <div v-if="checkout.customer && !checkout.customer.buyer.document" class="no-document">
                                    You do not currently have a compliance documented added to your account. 
                                    One will need to be provided before your wholesale order can be completed.
                                </div>

                                <h5>Terms and Conditions</h5>
                                <p>
                                    <input type="checkbox" class="confirm" v-model="termsAccepted" /> Yes, I agree with the <a target="_blank" href="/pages/terms-conditions">terms and conditions</a>
                                </p>

                                <button v-on:click="placeOrder()">Submit Purchase Order</button>
                            </div>
                        </div>
                    </div>

                    <div class="section" v-else>

                        <h3>
                            <span class="number">3</span> 
                            Payment 
                        </h3>
                      
                        <div class="section-content" :class="{ show: stepIndex == 4 }">

                            <div class="subsection payment-options" v-cloak>
                                
                                <h5>Terms and Conditions</h5>
                                <p>
                                    <input type="checkbox" class="confirm" v-model="termsAccepted" /> Yes, I agree with the <a target="_blank" href="/pages/terms-conditions">terms and conditions</a>
                                </p>

                                <div class="cc-inputs">
                                    <h5>
                                        <input type="radio" name="paymentType" value="card" v-model="paymentType"/>
                                        Pay with credit card
                                    </h5>
                                    
                                    <form  method="post" id="payment-form" :class="{ show: paymentType == 'card' }">
                                        <div class="form-row">
                                            <label for="card-element">
                                            Credit or debit card
                                            </label>
                                            <div id="card-element">
                                            <!-- A Stripe Element will be inserted here. -->
                                            </div>

                                            <!-- Used to display Element errors. -->
                                            <div id="card-errors" role="alert"></div>
                                        </div>

                                        <button :disabled="!termsAccepted">Complete Order</button>
                                    </form>
                                   
                                </div>

                                <h5>
                                    <input type="radio" name="paymentType" value="po" v-model="paymentType"/>
                                    Pay with purchase order
                                </h5>

                                <div class="subsection" v-if="paymentType === 'po'">

                                    <button v-on:click="placeOrder()" :disabled="!canCompleteOrder()">Complete Order</button>
                                    </div>

                                <div id="payment-error" v-if="paymentError" style="margin:10px 0; color:red;font-size:13px;">
                                    There was an error with the payment. ${ paymentError }
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            <div class="column order-summary">

                <div class="box">
                    <div class="section summary">
                        <h4>
                            Order Summary
                            <a href="/#cart">Edit Cart</a>
                        </h4>
                    </div>

                    <div class="section items">

                        <div class="item columns" v-for="item in checkout.items">
                            <div class="column image">
                                <img v-if="item.product.thumbnail" :src="item.product.thumbnail.indexOf('http') == 0 ? item.product.thumbnail : '{{ env('AWS_CDN_PRODUCTS_PATH') }}' + item.product.thumbnail"/>
                            </div>

                            <div class="column">
                                <div class="name">${ item.quantity } x ${ item.product.name }</div>
                                <div class="sku">${ item.variant.name }</div>
                                <div class="item-options" v-if="item.properties && item.properties.length > 0">
                                    <div class="item-option" v-for="prop in item.properties">
                                        <span class="option-name">${ prop.name }:</span>
                                        <span class="option-value">${ prop.value }</span>
                                    </div>
                                </div>
                            </div>

                            <div class="column subtotal">
                                <div class="price">
                                    ${ formatMoney(item.price * item.quantity) }
                                </div>
                            </div>
                        </div>
                        
                    </div>

                    <div class="section subtotal">
                        <div class="columns row">
                            <label class="column">Subtotal</label>
                            <span class="column">${ formatMoney(checkout.subtotal) }</span>
                        </div>
                        <!-- 
                        <div class="columns row">
                            <label class="column">Discount Code:</label>
                            <span class="column"><a>Add Code</a></span>
                        </div> -->

                        <div class="columns row">
                            <label class="column">Shipping</label>
                            <span class="column">${ formatMoney(checkout.shipping) }</span>
                        </div>

                        <div class="columns row">
                            <label class="column">Tax</label>
                            <span class="column">${ formatMoney(checkout.tax) }</span>
                        </div>
                        
                    </div>

                    <div class="section total">
                        <div class="columns row total">
                            <label class="column">Total:</label>
                            <span class="column">${ formatMoney(checkout.total) }</span>
                        </div>
                    </div>

                </div>

            </div>

        </div>

    </div>

    <script src="{{ mix('js/store.js') }}"></script>
    <script src="https://js.stripe.com/v3/"></script>
    <script>
        var stripe = Stripe('{{ env("STRIPE_PUBLIC_KEY") }}');

        // Fetches a payment intent and captures the client secret
        async function initialize() {
            console.log('init');
            // Custom styling can be passed to options when creating an Element.
            const style = {
                base: {
                    // Add your base input styles here. For example:
                    fontSize: '16px',
                    color: '#32325d',
                },
                };

            const elements = stripe.elements();
                        // Create an instance of the card Element.
            const card = elements.create('card', {style});

            // Add an instance of the card Element into the `card-element` <div>.
            card.mount('#card-element');

            const form = document.getElementById('payment-form');
            form.addEventListener('submit', async (event) => {
                event.preventDefault();

                const {token, error} = await stripe.createToken(card);

                if (error) {
                   console.log('error', error);
                } else {
                    axios.post(`/checkout/{{ $checkout->guid }}/complete`, { token: token.id }).then(function (response) {
                        window.location.reload();
                    }).catch(function(error) {
                        console.log('error', error);
                    });
                }
            });
        }

        setTimeout(() => {
            initialize();    
        }, 1000);
        
        
    </script>
    </body>

</html>