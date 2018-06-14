<table width="100%">
    <tr>
        <th>Customer</th>
        <th>Created</th>
    </tr>
    @foreach($customers->autoPagingIterator() as $customer)
        <tr>
            <td>{{ $customer->email }}</td>
            <td>{{ date('Y/m/d H:i', $customer->created) }}</td>
        </tr>
    @endforeach
</table>