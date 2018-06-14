<table width="100%">
    <tr>
        <th>Type</th>
        <th>Description</th>
        <th>Amount</th>
        <th>Date</th>
    </tr>
    @foreach($payments->autoPagingIterator() as $payment)
        <tr>
            <td>{{ $payment->type }}</td>
            <td>{{ $payment->source }}</td>
            <td>${{ calculateStripeAmount($payment->amount) }}</td>
            <td>{{ date('Y/m/d H:i', $payment->created) }}</td>
        </tr>
    @endforeach
</table>