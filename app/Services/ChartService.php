<?php


namespace App\Services;
 
class ChartService
{

    public function generateDonutChartData($array,$config)
    {
        if (!array_filter($config)) {
           return;
        }
        // ['label',data],['label',data]...
        return array_reduce($array,function ($result,$item) use($config) {
            $result[] = [$item[$config['label-key']],$item[$config['data-key']]];
            return $result;
        },[]);
    }

    public function getDonutChartImageUrl($data)
    {
        $slug = 'donut';
        $chartData = [
            'options' => [
                'data' => [
                    'type' => $slug,
                    'columns' => $data
                ]
            ]
        ];
        $json = json_encode($chartData);
        return $this->generateChartURL($slug,$json);
    }

    public function generateBarChartData($array,$config)
    {
        if (!array_filter($config)) {
            return;
        }
        return array_reduce($array,function ($result,$item) use($config){
            $result['labels'][] = $item[$config['label-key']];
            $result['data'][] = $item[$config['data-key']];
            return $result;
        },['labels'=>[],'data'=>[]]);
    }

    public function getBarChartImageUrl($labels,$labelData,$config)
    {
        $chartData = [
            'charturl' => [
              'type' => 'chartjs',
            ],
            'options' => [
                'type' => 'bar',
                'options' => [
                    'responsive' => true,
                    'legend' => [
                        'position' => 'top',
                        'display' => false
                    ],
                    'title' => [
                        'display' => true,
                        'text' => $config['title'],
                    ],
                    'scales' => [
                        'yAxes'=>[
                            [
                                'ticks' => [
                                    'suggestedMin'=> 0
                                ]
                            ]
                        ]
                    ]
                ],
                'data' => [
                    'labels' => $labels,
                    'datasets' => [
                        [
                            'backgroundColor' => $config['bar-color'],
                            'data' => $labelData
                        ]
                    ],
                ],
            ],
        ];
        $json = json_encode($chartData);
        return $this->generateChartURL('chartjs-bar-chart',$json);
    }


    public function getMapChartImageUrl($countryData)
    {
        $blueShades = $this->getBlueShades();
        $shadesByNumber = ['lightest','very_light','light','medium','dark','very_dark','darkest'];
        $countryValues = array_values($countryData);
        $highestValue = max($countryValues);
        $lowestValue = min($countryValues);

        $mapData = [];

        foreach ($countryData as $countryCode => $value) {
            $scaledValue = (7 - 1) * ($value - $lowestValue) / ($highestValue - $lowestValue) + 1;
            $mapData[$countryCode] = [
                'fillKey' => $shadesByNumber[round($scaledValue) - 1]
            ];
        }
        $chartData = array (
            'charturl' => 
            array (
              'type' => 'datamap',
            ),
            'options' => 
            array (
              'scope' => 'world',
              'projection' => 'mercator',
              'height' => 400,
              'width' => 600,
              'fills' => array_merge(['defaultFill' => '#e0e0e0'],$blueShades),
              'data' => $mapData,
            ),
        );
        $json = json_encode($chartData);
        return $this->generateChartURL('chloropleth',$json);
        
    }


    public function generateChartURL($slug,$json)
    {
        $key = env('CHARTURL_KEY');
        $token = env('CHARTURL_TOKEN');

        $raw_sig = hash_hmac('sha256', $json, $key, true);
        $encoded_sig = base64_encode($raw_sig);
        $url = "https://charturl.com/i/" . $token . "/" . $slug . "?d=" . urlencode($json) . "&s=" . urlencode($encoded_sig);
        return $url;
    }


    public function getBlueShades()
    {
        return [
            'lightest' => '#81cdfd',
            'very_light' => '#4fb9fd',
            'light' => '#1ca5fc',
            'medium' => '#038ce3',
            'dark' => '#026db0',
            'very_dark' => '#024e7e',
            'darkest' => '#012f4c',
        ];
    }
    
}

