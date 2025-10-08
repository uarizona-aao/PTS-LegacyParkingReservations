<?php
declare(strict_types=1);

namespace App\Application\Services;

class BoxService {
    private $accessToken;
    private $apiUrl = 'https://api.box.com/2.0';
    
    public function __construct($accessToken) {
        $this->accessToken = $accessToken;
    }   
    
    private function makeCurlRequest($endpoint, $method = 'GET', $data = null) {
        $ch = curl_init($this->apiUrl . $endpoint);
    
        $headers = [ 
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json'
        ];  
    
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }   
        }   
    
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
        if (curl_errno($ch)) {
            throw new Exception('Curl error: ' . curl_error($ch));
        }   
    
        curl_close($ch);
    
        if ($httpCode >= 400) {
            throw new Exception('API error: ' . $response);
        }   
    
        return json_decode($response, true);
    } 

    public function createFolder($name, $parentFolderId = '0') {
        return $this->makeCurlRequest('/folders', 'POST', [
            'name' => $name,
            'parent' => ['id' => $parentFolderId]
        ]);
    }

    public function getFolderContents($id) {
        return $this->makeCurlRequest("/folders/$id/items?fields=shared_link,is_package,lock");
    }

    public function getFolderInformation($id) {
        return $this->makeCurlRequest("/folders/$id/");
    }

    public function uploadFile($filePath, $fileName, $folderId = '0') {
        if (!file_exists($filePath)) {
            throw new Exception("File not found: $filePath");
        }

        // Uploads have a special POST url separate from the main API.
        $ch = curl_init('https://upload.box.com/api/2.0/files/content');

        // Create the attributes part
        $attributes = json_encode([
            'name' => $fileName,
            'parent' => ['id' => $folderId]
        ]);

        // Prepare the file
        $cfile = new CURLFile($filePath, mime_content_type($filePath), $fileName);

        // Set up the POST data
        $data = [
            'attributes' => $attributes,
            'file' => $cfile
        ];

        // Set CURL options
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->accessToken,
                'Content-Type: multipart/form-data'
            ],
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_VERBOSE => true
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            throw new Exception('Upload failed: ' . curl_error($ch));
        }

        curl_close($ch);

        if ($httpCode >= 400) {
            throw new Exception('Upload failed with HTTP code ' . $httpCode . ': ' . $response);
        }

        return json_decode($response, true);
    }

    public function downloadFile($fileId, $savePath) {
        $ch = curl_init($this->apiUrl . "/files/$fileId/content");

        $headers = ['Authorization: Bearer ' . $this->accessToken];
        $fp = fopen($savePath, 'w');

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $success = curl_exec($ch);
        fclose($fp);

        if (!$success) {
            throw new Exception('Download failed: ' . curl_error($ch));
        }

        // Set file permissions to 744
                chmod($savePath, 0744);

        curl_close($ch);
        return true;
    }
}
?>
