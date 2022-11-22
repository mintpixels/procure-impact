<!doctype html>
<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" value="{{ csrf_token() }}">

        <title>AimSurplus, LLC</title>

        <link href="https://fonts.googleapis.com/css?family=Montserrat:400,500,700|Saira:100, 200, 300,400,500,600,700,900|Teko:300,500" rel="stylesheet">
        <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.0.13/css/all.css" integrity="sha384-oi8o31xSQq8S0RpBcb4FaLB8LJi9AT8oIdmS1QldR8Ui7KUQjNAnDlJjp55Ba8FG" crossorigin="anonymous">
        <link rel="stylesheet" href="/css/store.css" />
    
        <script src="https://cdn.jsdelivr.net/npm/vue@3.2.41/dist/vue.global.min.js"></script>
       
    </head>
    <body class="checkout thankyou">

        <header>
            <a href="/">
                <img src="https://cdn11.bigcommerce.com/s-admq3scrtq/stencil/bb6e2680-7465-0139-0bad-32a6417f3c7a/e/a0fe7ea0-5f2b-0139-4d5e-266e81368fc3/img/store-logo.png">
            </a>
        </header>

        <div class="padded-content">
            <b>Thanks for Your Order.</b>
            <br><br>
            Your order number is <b>#{{ $order->id }}</b>.
            <br><br>

            Your order details are below.
            <br><br>
            <table style="width:100%">
                <thead>
                    <tr>
                        <th>Qty</th>
                        <th></th>
                        <th>Product</th>
                        <th>Price</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                        @if($item->product)
                        <tr>
                            <td class="text-center">
                                {{ $item->quantity }}
                            </td>
                            <td style="max-width:50px; max-height:50px;" >
                                <img src="{{ stripos($item->product->thumbnail, 'https') !== FALSE ? $item->product->thumbnail : env('AWS_CDN_PRODUCTS_PATH') . $item->product->thumbnail }}" style="max-width:50px; max-height:50px;" />
                            </td>
                            <td>
                                <a href="https://aimsurplus.com/{{ $item->product->handle }}/">{{ $item->product->name }}</a>
                                <br>
                                {{ strtoupper($item->product->sku) }}<br>
                                @if($item->properties && count($item->properties) > 0)
                                    @foreach($item->properties as $prop)
                                        {{ $prop['name'] }}: {{ $prop['value'] }} <br>
                                    @endforeach
                                @endif
                            </td>
                            <td class="text-right">
                                ${{ $item->line_price }}
                            </td>
                        </tr>
                        @endif
                    @endforeach
                    <tr>
                        <td></td>
                        <td></td>
                        <td class="text-right">Subtotal</td>
                        <td class="text-right">${{ $order->subtotal }}</td>
                    </tr>
                    @if($order->shipping > 0)
                    <tr>
                        <td></td>
                        <td></td>
                        <td class="text-right">Shipping</td>
                        <td class="text-right">${{ $order->shipping }}</td>
                    </tr>
                    @endif
                    @if($order->insurance  > 0)
                    <tr>
                        <td></td>
                        <td></td>
                        <td class="text-right">Insurance</td>
                        <td class="text-right">${{ $order->insurance }}</td>
                    </tr>
                    @endif
                    @if($order->tax > 0)
                    <tr>
                        <td></td>
                        <td></td>
                        <td class="text-right">Tax</td>
                        <td class="text-right">${{ $order->tax }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td></td>
                        <td></td>
                        <td class="text-right"><b>Total</b></td>
                        <td class="text-right"><b>${{ $order->total }}</b></td>
                    </tr>
                </tbody>
                </tbody>
            </table>

            <br><br>
            <a class="button" href="/">Continue Shopping</a>
        </div>
    </body>
</html>
