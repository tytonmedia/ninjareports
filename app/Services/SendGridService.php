<?php


namespace App\Services;
 
class SendGridService 
{

    public function sendTransactionalMail($data)
    {
        $to = [];
        $from = ['email' => 'reports@ninjareports.com',
                   'name' => 'Ninja Reports'];
        $attachments = [];

        if (array_key_exists('to',$data)) {
            $to[] = $data['to'];
        }

        if (array_key_exists('recipients',$data)) {
            $to = array_merge($to,$data['recipients']);
        }

        if (array_key_exists('from',$data)) {
            $from = $data['from'];
        }

        if (array_key_exists('subject',$data)) {
           $data['template_data']['email_subject'] = $data['subject'];
        }
        
        if (array_key_exists('attachments',$data)) {
            $attachments = array_map(function ($attachment) {
                return [
                    'content' => base64_encode(file_get_contents($attachment['file'])),
                    'type' => mime_content_type($attachment['file']),
                    'filename' => $attachment['name'],
                    'disposition' => 'attachment',
                ];
            },$data['attachments']);
        }

        $client = new \GuzzleHttp\Client();
        $postData = [
            'headers' => [
                'Authorization' => 'Bearer '.env('SENDGRID_KEY'),
                'Accept'     => 'application/json'
            ],
            'json' => [
                'personalizations' => [[
                    'to' => $to,
                    'dynamic_template_data' => $data['template_data'],
                ]],
                'from' => $from,
                'template_id' => $data['template_id']
            ]
        ];
        if ($attachments) {
            $postData['json']['attachments'] = $attachments;
        }

        $response = $client->request('POST', 'https://api.sendgrid.com/v3/mail/send',$postData);
        return $response;
    }
    
}

