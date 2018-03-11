<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SendGrid extends Model
{
    public static function send($to, $subject, $template_id, $substitutions, $attachments, $from)
    {
        $request_body = [
            'personalizations' => [
                [
                    'to' => [
                        [
                            'email' => $to,
                        ],
                    ],
                    "substitutions" => $substitutions,
                    "subject" => $subject,
                ],
            ],
            'from' => [
                'email' => $from,
                'name' => 'Ninja Reports™',
            ],
            "template_id" => $template_id,
        ];
        if (count($attachments) > 0) {
            $request_body['attachments'] = $attachments;
        }
        $apiKey = env('SENDGRID_KEY');
        $sg = new \SendGrid($apiKey);

        $response = $sg->client->mail()->send()->post($request_body);
        $encodedString = json_encode($response);

       // file_put_contents('sendgrid_response.txt', $encodedString);
        return $response;
    }
}