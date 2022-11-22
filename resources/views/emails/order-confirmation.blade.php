@extends('emails.layout')

@section('content')

<b>Thanks for Your Order.</b>
<br><br>
Your order ID is #{{ $order->id }}.
<br><br>

Your order contains the following items:
<br>
<table style="width:100%">
    <tbody>
        @foreach($order->items as $item)
            @if($item->product)
            <tr>
                <td style="padding:10px;">
                    {{ $item->quantity }}
                </td>
                <td style="padding:20px;">
                    <a href="{{ url('/products/' . $item->product->handle) }}/">{{ $item->product->name }}</a>
                    <br>
                    @if($item->variant->name)
                        {{ $item->variant->name }}
                        <br>
                    @endif
                    {{ $item->brand->name }}
                    <br>
                    
                    @if($item->properties && count($item->properties) > 0)
                        @foreach($item->properties as $prop)
                            {{ $prop['name'] }}: {{ $prop['value'] }} <br>
                        @endforeach
                    @endif
                </td>
                <td style="text-align:right">
                    ${{ $item->line_price }}
                </td>
            </tr>
            @endif
        @endforeach
    </tbody>
</table>

<table style="width:200px;float:right">
    <tbody>
        <tr>
            <td>Subtotal</td>
            <td style="text-align:right">${{ $order->subtotal }}</td>
        </tr>
     
        @if($order->tax > 0)
        <tr>
            <td>Tax</td>
            <td style="text-align:right">${{ $order->tax }}</td>
        </tr>
        @endif
        <tr>
            <td><b>Total</b></td>
            <td style="text-align:right"><b>${{ $order->total }}</b></td>
        </tr>
    </tbody>
</table>

<div style="clear:both"></div>

@stop