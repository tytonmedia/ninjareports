<table width="100%" cellpadding="5" cellspacing="0" style="background:#fff">
    <tbody>
        <tr>
            <th style="background:#666;color:#fff;padding:5px;text-align:left;">Page</th>
            <th style="background:#666;color:#fff;padding:5px;text-align:left;">Pageviews</th>
            <th style="background:#666;color:#fff;padding:5px;text-align:left;">% Pageviews</th>
        </tr>
        @if(isset($path_clicks) && count($path_clicks) > 0)
        @foreach($path_clicks as $key => $path_click)
        <tr>
            <td>{{$key}}</td>
            <td>{{$path_click}}</td>
            <td>{{ round(($path_click * 100) / $total_click,2) }}%</td>
        </tr>
        @endforeach
        @else
        <tr><td><h3><center>No data</center></h3></td></tr>
        @endif
</table>