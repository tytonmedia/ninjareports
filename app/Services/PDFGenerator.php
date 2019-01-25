<?php


namespace App\Services;
 
class PDFGenerator
{
    public function __construct() {
        
    }

    public function generate($html,$filePath)
    {
        $pathParts = pathinfo($filePath);
        try {
            $mpdf = new \Mpdf\Mpdf([
                'tempDir' => $pathParts['dirname'],
                'mode' => 'utf-8', 
            ]);
            $mpdf->WriteHTML($html);
            $mpdf->Output($filePath, 'F');
        } catch (MpdfException $e) {

        }
    }
}