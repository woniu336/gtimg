<?php
header('Content-Type: application/json');

class Gtimg
{
    public $name = '腾讯Gtimg图床';
    public $ver = '1.0';
    private $token = '';

    public function submit($file_path)
    {
        try {
            if (!file_exists($file_path)) {
                throw new Exception("文件不存在: " . $file_path);
            }

            $filePath = $file_path;
            $url = 'https://om.qq.com/image/orginalupload';
            $data = [];
            
            // 检查文件是否可读
            if (!is_readable($file_path)) {
                throw new Exception("文件不可读: " . $file_path);
            }

            if (class_exists('CURLFile')) {
                $data['Filedata'] = new \CURLFile(realpath($file_path));
            } else {
                $data['Filedata'] = '@'.realpath($file_path);
            }

            $data['subModule'] = 'userAuth_individual_head';
            $data['id'] = 'WU_FILE_0';
            $data['name'] = basename($file_path);
            $data['type'] = mime_content_type($file_path);
            $data['lastModifiedDate'] = date('D M d Y H:i:s \G\M\T+0800 (中国标准时间)');
            $data['appkey'] = '1';
            $data['isRetImgAttr'] = '1';
            $data['from'] = 'user';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
            curl_setopt($ch, CURLOPT_TIMEOUT, 120);
            
            $ip = mt_rand(48, 140) . "." . mt_rand(10, 240) . "." . mt_rand(10, 240) . "." . mt_rand(10, 240);
            $httpheader = [
                'Accept: */*',
                'Accept-Encoding: gzip, deflate, br',
                'Accept-Language: zh-CN,zh;q=0.9',
                'Connection: keep-alive',
                'Referer: https://om.qq.com/userReg/mediaInfo',
                'CLIENT-IP:' . $ip,
                'X-FORWARDED-FOR:' . $ip,
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/103.0.0.0 Safari/537.36',
                'Cookie: ' . $this->token
            ];
            
            curl_setopt($ch, CURLOPT_HTTPHEADER, $httpheader);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_ENCODING, "gzip");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            
            $html = curl_exec($ch);
            
            if (curl_errno($ch)) {
                throw new Exception('CURL错误: ' . curl_error($ch));
            }

            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $json = json_decode($html, true);
            if ($json === null) {
                throw new Exception('JSON解析失败: ' . json_last_error_msg());
            }

            if ($json['response']['code'] == '0') {
                $file_url = $json['data']['url']['url'];
                return [
                    'local_path' => $filePath,
                    'remote_url' => $file_url
                ];
            } else {
                $errorMsg = isset($json['response']['msg']) ? $json['response']['msg'] : '未知错误';
                throw new Exception("上传失败: " . $errorMsg);
            }
        } catch (Exception $e) {
            error_log("腾讯图床上传错误: " . $e->getMessage());
            return false;
        }
    }
}

function renameUploadedFile($originalPath, $remoteUrl) {
    if (!file_exists($originalPath)) {
        throw new Exception("原文件不存在: {$originalPath}");
    }

    if (preg_match('/\/([^\/]+)\/0$/', $remoteUrl, $matches)) {
        $newFileName = $matches[1];
        $extension = pathinfo($originalPath, PATHINFO_EXTENSION);
        $dirPath = dirname($originalPath);
        $newPath = $dirPath . '/' . $newFileName . '.' . $extension;

        if (rename($originalPath, $newPath)) {
            return $newPath;
        } else {
            throw new Exception("重命名失败");
        }
    } else {
        throw new Exception("无法从URL提取文件名");
    }
}

try {
    if (!isset($_FILES['Filedata'])) {
        throw new Exception('没有接收到文件');
    }

    $file = $_FILES['Filedata'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('文件上传失败，错误代码：' . $file['error']);
    }

    $allowed_types = ['image/jpeg', 'image/png'];
    if (!in_array($file['type'], $allowed_types)) {
        throw new Exception('不支持的文件类型');
    }

    $max_size = 10 * 1024 * 1024; // 10MB
    if ($file['size'] > $max_size) {
        throw new Exception('文件大小超过限制');
    }

    $tempPath = $file['tmp_name'];
    
    $uploadDir = 'uploads/' . date('Y/m/d/');
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            throw new Exception('创建上传目录失败');
        }
    }
    
    $fileInfo = pathinfo($file['name']);
    $newFileName = uniqid() . '_' . date('His') . '.' . $fileInfo['extension'];
    $targetPath = $uploadDir . $newFileName;

    if (!move_uploaded_file($tempPath, $targetPath)) {
        throw new Exception('移动文件失败');
    }

    chmod($targetPath, 0644);

    $gtimg = new Gtimg();
    $result = $gtimg->submit($targetPath);

    if ($result) {
        try {
            $newLocalPath = renameUploadedFile($result['local_path'], $result['remote_url']);
            $result['local_path'] = $newLocalPath;

        } catch (Exception $e) {
            error_log("文件处理失败: " . $e->getMessage());
            $compressionInfo = [
                'compressed' => false,
                'error' => $e->getMessage()
            ];
        }

        // 记录上传历史
        $historyFile = 'uploads/history.json';
        $history = file_exists($historyFile) ? json_decode(file_get_contents($historyFile), true) : [];
        
        $history[] = [
            'local_path' => $result['local_path'],
            'remote_url' => $result['remote_url'],
            'upload_time' => date('Y-m-d H:i:s'),
            'file_size' => $file['size'],
            'file_type' => $file['type'],
            'compression_info' => null
        ];
        
        file_put_contents($historyFile, json_encode($history, JSON_PRETTY_PRINT));

        echo json_encode([
            'response' => ['code' => '0'],
            'data' => [
                'url' => ['url' => $result['remote_url']],
                'local_path' => $result['local_path'],
                'compression_info' => null
            ]
        ]);
    } else {
        throw new Exception('上传到图床失败');
    }

} catch (Exception $e) {
    echo json_encode([
        'response' => ['code' => '1'],
        'message' => $e->getMessage()
    ]);
}
?>