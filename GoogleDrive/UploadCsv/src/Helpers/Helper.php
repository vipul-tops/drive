<?php

namespace Googledrive\Uploadcsv\Helpers;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Googledrive\Uploadcsv\Models\GoogleRefToken;


class Helper
{
   public static function uploadLargeFile($param)
    {
        $gtoken = GoogleRefToken::first();
        //dd($gtoken);
        $fileName = $param['fileName'];
        log::info('filename ==' . $fileName);
        $access_token = $gtoken->access_token;

        $folderID = Config::get('gdriveconfig.DRIVE_FOLDER_ID');
        $dFileResumableUri = Config::get('gdriveconfig.DRIVE_FILE_RESUMABLE_URI');

        if(empty($folderID))
        {
            $fileMetadata = [
                'name' => $fileName,
                'mimeType' => 'application/csv',
                'fields' => 'id'
            ]; 
        } else{
            $fileMetadata = [
                'name' => $fileName,
                'mimeType' => 'application/csv',
                'fields' => 'id',
                'parents' => [$folderID]
            ];
        }
        
        //Log::info('fileMetadata :- -'.$fileMetadata);
        //dd($fileMetadata);
        $driveUrl = $dFileResumableUri;
        $headers = [
            'Authorization' => 'Bearer ' . $access_token,
            'Content-Type' => 'application/json; charset=UTF-8',

        ];
        //dd([$access_token, $fileMetadata, $headers]);
        Log::info([$access_token,$fileMetadata,$headers]);
        $client = new \GuzzleHttp\Client;

        $response = $client->post($driveUrl, [
            'headers' => $headers,
            'json' => $fileMetadata,
        ]);
        //dd($response);
        //\Log::info('response :- -'.$response);
        
        $locationHeader = $response->getHeader('Location');
        //dd($locationHeader);
        $data = json_decode($response->getBody(), true);
        //dd($data);
        if (isset($locationHeader[0])) {
            $uploadUrl = $locationHeader[0];
            // Proceed with uploading the file to the $uploadUrl
        } else {
            // Handle error: Location header not found
            $uploadUrl = "";
        }
        //\Log::info('uploadUrl :- -'.$uploadUrl);
        return $uploadUrl;
    }

    public static function arrayToCsvString($array) {
    $csvString = '';
    foreach ($array as $row) {
        $escapedRow = array_map(function($field) {
            // Escape double quotes in the field by doubling them
            $field = str_replace('"', '""', $field);
            // Enclose the field in double quotes
            return '"' . $field . '"';
        }, $row);
        $csvString .= implode(',', $escapedRow) . "\n";
    }
    return $csvString;
    }
}
