<table width="100%" cellpadding="5" cellspacing="0" style="background:#fff">
    <tbody>
        <tr>
            <th style="background:#666;color:#fff;padding:5px;text-align:left;">Source</th>
            <th style="background:#666;color:#fff;padding:5px;text-align:left;">Visits</th>
            <th style="background:#666;color:#fff;padding:5px;text-align:left;">New</th>
            <th style="background:#666;color:#fff;padding:5px;text-align:left;">Bounce %</th>
            <th style="background:#666;color:#fff;padding:5px;text-align:left;">Pages/Visit</th>
            <th style="background:#666;color:#fff;padding:5px;text-align:left;">Revenue</th>
        </tr>
        @if(isset($sources) && count($sources) > 0)
        @foreach($sources as $key => $insight)
        <tr>
            <td>{{$insight[0]}}</td>
            <td>{{$insight[1]}}</td>
            <td>{{round($insight[6], 0)}}</td>
            <td>{{round($insight[5], 0)}}%</td>
            <td>{{number_format((float)$insight[7], 2, '.', '')}}</td>
            <td>${{$insight[8]}}</td>
            
        </tr>
        @endforeach
        @else
        <tr><td><h3><center>No data</center></h3></td></tr>
        @endif
</table>