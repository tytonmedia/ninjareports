<table width="100%" cellpadding="5" cellspacing="0" style="background:#fff">
    <tbody>
        <tr>
            <th style="background:#666;color:#fff;padding:5px;text-align:left;">Campaign</th>
            <th style="background:#666;color:#fff;padding:5px;text-align:left;">Visitotrs</th>
            <th style="background:#666;color:#fff;padding:5px;text-align:left;">New</th>
            <th style="background:#666;color:#fff;padding:5px;text-align:left;">Bounce %</th>
            <th style="background:#666;color:#fff;padding:5px;text-align:left;">Pages/visit</th>
            <th style="background:#666;color:#fff;padding:5px;text-align:left;">Avg. Time</th>
        </tr>
        @if(isset($mediums) && count($mediums) > 0)
        @foreach($mediums as $key => $medium)
        <tr>
            <td>{{$medium[0]}}</td>
            <td>{{$medium[1]}}</td>
            <td>{{round($medium[6], 0)}}</td>
            <td>{{round($medium[5],0)}}</td>
            <td>{{round($medium[7],0)}}</td>
            <td>{{ date("H:i:s", strtotime($medium[3]))}}</td>
            
            
        </tr>
        @endforeach
        @else
        <tr><h3><center>No data</center></h3></tr>
        @endif
</table>