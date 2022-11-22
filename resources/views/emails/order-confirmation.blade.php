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
                    <a href="https://aimsurplus.com/{{ $item->product->handle }}/">{{ $item->product->name }}</a>
                    <br>
                    {{ strtoupper($item->product->sku) }}
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
        @if($order->shipping > 0)
        <tr>
            <td>Shipping</td>
            <td style="text-align:right">${{ $order->shipping }}</td>
        </tr>
        @endif
        @if($order->insurance  > 0)
        <tr>
            <td>Insurance</td>
            <td style="text-align:right">${{ $order->insurance }}</td>
        </tr>
        @endif
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

<?php $laser = false; ?>
@foreach($order->items as $item)
    @if(in_array('Laser', $item->product->tagArray()))
        <?php $laser = true; ?>
    @endif
@endforeach

@if($laser)
   @include('emails.laser-snippet')
@endif

<br>
Thank you for your business and we look forward to serving you in the future!
<br><br>
<b>AimSurplus, Llc.</b><br>
<b>Phone:</b> 888-748-5252<br>
<b>Email:</b> sales@aimsurplus.com<br>
<b>Website:</b> www.aimsurplus.com 

@stop