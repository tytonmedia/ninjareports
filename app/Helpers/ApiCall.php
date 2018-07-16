<?php

namespace App\Helpers;



class ApiCall
{



    public static  function getAuthenticationHeaders()
    {
        $APIKey = "pPKAQ-kxyJBDLdIpDpUK585L669jgIyF";
        $headers = array();
        $headers[] = "Authentication: Token $APIKey";
        $headers[] = "Content-Type: application/x-www-form-urlencoded";
        
        return $headers;
        
    }
    
    public static  function send($htmla,$pdf_dir)
    {
        $html = "html=".$htmla;
        $url = "https://htmlpdfapi.com/api/v1/pdf";
        $method = "get";        
                
        // Generated by curl-to-PHP: http://incarnate.github.io/curl-to-php/
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $html);
        curl_setopt($ch, CURLOPT_POST, 1);

        curl_setopt($ch, CURLOPT_HTTPHEADER, ApiCall::getAuthenticationHeaders());

         $result = curl_exec($ch);
       
       // $pdf_dir = public_path('files/pdf/5.pdf');
        
        $file = fopen($pdf_dir, "w+");
        fputs($file, $result);
        fclose($file);
        
        
       
        
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close ($ch);
        
        
     /*   $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://api.pdfshift.io/v2/convert/");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $htmla);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_USERPWD, "7303a3e152784bd0b80c61f0b292a2b9" . ":" . "");

        $headers = array();
        $headers[] = "Content-Type: application/x-www-form-urlencoded";
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close ($ch);
        
        $file = fopen($pdf_dir, "w+");
        fputs($file, $result);
        fclose($file);

        return $result;*/
        
       

    }


}
/*
if ($report->attachment_type == 'pdf') {
                    try {
                        $html = view('reports.templates.analytics', compact('report', 'top_5_sources','top_5_campaigns' ,'top_5_pageview', 'devices_graph_url', 'locations_graph_url', 'channel_graph_url','pageview_graph_url' ,'total_sessions', 'total_revenue', 'total_bounce_rate', 'total_pageviews', 'total_pages_per_visitor', 'total_new_visitors', 'ad_account_title'))->render();
                        $attachments = \App\Helpers\ApiCall::send($html,$pdf_dir . $pdf_file_name);
                        $attachments = [
                            [
                                'content' => base64_encode(file_get_contents($pdf_dir . $pdf_file_name)),
                                'type' => 'text/pdf',
                                'filename' => $report->title . '.pdf',
                                'disposition' => 'attachment',
                            ],
                        ];
                    //  echo $html;
                        //$attachments = $this->generatePdf($html, $pdf_dir, $pdf_file_name, $report);
                    } catch (\Throwable $e) {
                    }
                }
                */