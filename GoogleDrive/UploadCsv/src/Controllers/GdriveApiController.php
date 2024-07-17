<?php

namespace Googledrive\Uploadcsv\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use Carbon\Carbon;
use Googledrive\Uploadcsv\Helpers\Helper;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Googledrive\Uploadcsv\Models\GoogleRefToken;
use App\Models\Emp;


class GdriveApiController extends Controller
{
	public function hello()
	{
		$token = GoogleRefToken::latest()->first();
		 dd($token);
		echo 'hello12';
	}
	public function googleIndex()
	{
		$token = GoogleRefToken::latest()->first();
		$now = Carbon::now();
		return view('uploadcsv::form',compact('token','now'));
	}

    public function authenticate()
    {
        $gOauthURL = "https://accounts.google.com/o/oauth2/auth?scope=" 
        	. urlencode(Config::get('gdriveconfig.google_client_scope')) 
        	. "&redirect_uri=" . urlencode(Config::get('gdriveconfig.google_redirect_uri')) 
        	. "&client_id=" . urlencode(Config::get('gdriveconfig.google_client_id')) 
        	. "&access_type=offline&response_type=code";
        Log::info('gOauthURL = '.$gOauthURL);
        return redirect()->away($gOauthURL);
    }

    public function handlecallback(Request $request)
    {
        if ($request->has('code')) {
            $accessToken = $this->getAccessToken($request->input('code'));
            //Log::info('accessToken = ' . json_encode($accessToken));

            if (isset($accessToken['access_token']) && isset($accessToken['refresh_token'])) {
                $expiresAt = Carbon::now()->addSeconds($accessToken['expires_in']);
                GoogleRefToken::create([
                    'access_token' => $accessToken['access_token'],
                    'refresh_token' => $accessToken['refresh_token'],
                    'expires_at' => $expiresAt,
                ]);
                //Session::put('access_token', $accessToken['access_token']);
                //return redirect('/gindex');
                echo " Token Generated Successfully ";
            } else {
                //return redirect('/')->with('error', 'Failed to obtain access token.');
                echo "Failed to obtain access token.";
            }
        } else{
            echo "Authorization code not found.";
        }
        //return redirect('/')->with('error', 'Authorization code not found.');
    }

    private function getAccessToken($code)
    {
        $curlPost = 'client_id=' . Config::get('gdriveconfig.google_client_id') . 
            '&redirect_uri=' . Config::get('gdriveconfig.google_redirect_uri') . 
            '&client_secret=' . Config::get('gdriveconfig.google_client_secret') . 
            '&code=' . $code . '&grant_type=authorization_code';
        //dd($code);
        Log::info('curlPost = '. $curlPost);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, Config::get('gdriveconfig.Oauth2_TOKEN_URI'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        $data = json_decode(curl_exec($ch), true);
        //dd($data);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($http_code != 200) {
            Log::error('Error getting access token: ' . curl_error($ch));
            throw new \Exception('Error ' . $http_code . ': ' . curl_error($ch));
        }
        return $data;
    }

    public function refreshToken()
    {
        $token = GoogleRefToken::latest()->first();

        $curlPost = 'client_id=' . Config::get('gdriveconfig.google_client_id') . 
            '&client_secret=' . Config::get('gdriveconfig.google_client_secret') . 
            '&refresh_token=' . $token->refresh_token . 
            '&grant_type=refresh_token';

        //Log::info('curlPost = ' . $curlPost);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, Config::get('gdriveconfig.Oauth2_TOKEN_URI'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        $data = json_decode(curl_exec($ch), true);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($http_code != 200) {
            Log::error('Error refreshing access token: ' . curl_error($ch));
            throw new \Exception('Error ' . $http_code . ': ' . curl_error($ch));
        }

        if (isset($data['access_token'])) {
            $expiresAt = Carbon::now()->addSeconds($data['expires_in']);
            $token->update([
                'access_token' => $data['access_token'],
                'expires_at' => $expiresAt,
            ]);
            return redirect('/');
        } else {
            //return redirect('/');
        }
    }

    public static function uploadLargeFile($model, $columns)
    {
        //$columns = ['id', 'name', 'email', 'address', 'mobile', 'role'];
        //$employee = Emp::all();
        $employee = $model::all();

        $csvData = [];
        $csvData[] = $columns;

        foreach ($employee as $employees) {
            $rowData = [];
            foreach ($columns as $column) {
                $rowData[] = $employees->$column;  // Get data of Particuler column
            }
            $csvDatas[] = $rowData;
        }

        foreach($csvDatas as $item) {                   
            $csvData[] = $item;
        }

        $newfileCsvDatalist =   array_chunk($csvData,9999);
        //dd('Csv Count : '.count($csvData));
        foreach($newfileCsvDatalist as $newfileCsvData)
        {
        	sleep(2);
            $startByte = 0;
            $fileName ="csv_file_". date("Y_m_d_h_i_s").".csv";
            $resumeParam['fileName'] = $fileName;
            $checknewfileCsvData= Helper::arrayToCsvString($newfileCsvData);
            //dd($checknewfileCsvData);
            $getUploadUrl = Helper::uploadLargeFile($resumeParam);
            //dd($getUploadUrl);
            $fileSize = strlen($checknewfileCsvData);

            $chunkSize = 262144; // 256 KB

            $gtoken = GoogleRefToken::first();
            while ($startByte < $fileSize) {
                $chunk = substr($checknewfileCsvData, $startByte, $chunkSize);
                //dd($chunk);
                $endByte = $startByte + strlen($chunk) - 1;
                //dd($endByte);
                $client =  new \GuzzleHttp\Client;
                $chunkHeaders = [
                    'Authorization' => 'Bearer ' . $gtoken->access_token,
                    'Content-Length' => strlen($chunk),
                    'Content-Range' => "bytes $startByte-$endByte/$fileSize",
                ];
                // echo "<pre>";
                // print_r($chunkHeaders);
                // dd($chunkHeaders);
                $uploadfile =  $client->put($getUploadUrl, [
                    'headers' => $chunkHeaders,
                    'body' => $chunk,
                ]);
                //dd($uploadfile);
                $startByte = $endByte + 1;

            }
            if (isset($uploadfile)) {
                $finalResponse = json_decode($uploadfile->getBody(), true);
                if (json_last_error() === JSON_ERROR_NONE) 
                {
                    $googleFileId = isset($finalResponse['id']) ? $finalResponse['id'] : '';
                    //dd($googleFileId);
                    echo "CSV File Upload in Google Drive..";
                    //return redirect('/')->with('success', 'CSV File has been uploaded.');
                } else {
                    echo "Error decoding JSON response\n";
                }
            }
        }

    }

    // public static function generateAndUploadCsv($model, $columns)
    // {
    //     $employees = $model::all();

    //     $csvData = [];
    //     $csvData[] = $columns;

    //     foreach ($employees as $employee) {
    //         $rowData = [];
    //         foreach ($columns as $column) {
    //             $rowData[] = $employee->$column;
    //         }
    //         $csvData[] = $rowData;
    //     }

    //     $newfileCsvDatalist = array_chunk($csvData, 9999);

    //     foreach ($newfileCsvDatalist as $newfileCsvData) {
    //         sleep(2);
    //         $startByte = 0;
    //         $fileName = "csv_file_" . date("Y_m_d_h_i_s") . ".csv";
    //         $resumeParam['fileName'] = $fileName;
    //         $csvString = Helper::arrayToCsvString($newfileCsvData);
    //         $uploadUrl = Helper::uploadLargeFile($resumeParam);
    //         $fileSize = strlen($csvString);
    //         $chunkSize = 262144;

    //         $gtoken = GoogleRefToken::first();
    //         while ($startByte < $fileSize) {
    //             $chunk = substr($csvString, $startByte, $chunkSize);
    //             $endByte = $startByte + strlen($chunk) - 1;
    //             $client = new \GuzzleHttp\Client;
    //             $chunkHeaders = [
    //                 'Authorization' => 'Bearer ' . $gtoken->access_token,
    //                 'Content-Length' => strlen($chunk),
    //                 'Content-Range' => "bytes $startByte-$endByte/$fileSize",
    //             ];
    //             $uploadfile = $client->put($uploadUrl, [
    //                 'headers' => $chunkHeaders,
    //                 'body' => $chunk,
    //             ]);
    //             $startByte = $endByte + 1;
    //         }

    //         if (isset($uploadfile)) {
    //             $finalResponse = json_decode($uploadfile->getBody(), true);
    //             if (json_last_error() === JSON_ERROR_NONE) {
    //                 $googleFileId = $finalResponse['id'] ?? '';
    //                 return redirect('/')->with('success', 'CSV File has been uploaded.');
    //             } else {
    //                 echo "Error decoding JSON response\n";
    //             }
    //         }
    //     }
    // }


}