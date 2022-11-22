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
        
        <link rel="stylesheet" href="{{ mix('/css/store.css') }}" />

        @yield('head')
       
    </head>
    <body class="checkout thankyou">

        <header>
            <a href="/">
                <img src="{{ asset('img/logo.svg') }}" style="width:175px" />
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
                        <th class="text-right">Price</th>
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
                                <img src="{{ $item->product->thumbnail }}" style="max-width:100px; max-height:100px;" />
                            </td>
                            <td>
                                <a href="{{ url('/products/' . $item->product->handle) }}/">{{ $item->product->name }}</a>
                                <br>
                                @if($item->variant->name)
                                    {{ $item->variant->name }}
                                    <br>
                                @endif
                                {{ $item->brand->name }}
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
