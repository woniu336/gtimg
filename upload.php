<?php
header('Content-Type: application/json');

class Gtimg
{
    public $name = '腾讯Gtimg图床';
    public $ver = '1.0';
    private $token = 'userid=22510990; omaccesstoken=00c6c7281a6e2af46ed162fd0d19bef8201bd6b97b0e6df07c93577e6b7a97f207b38d5ee1614df8c2a46bd14bc28d311d1775906017ef308b31925c4d8e1ea0c8w6; omtoken=00c6c7281a6e2af46ed162fd0d19bef8201bd6b97b0e6df07c93577e6b7a97f207b38d5ee1614df8c2a46bd14bc28d311d1775906017ef308b31925c4d8e1ea0c8w6; srcaccessToken=86_2s8jTqfCkhUmOdT2vwrp7bpxlHakMWqcUlth8RZ9CH2Df0eAXJXvV2vmvQF21D4r2Z4QXJEKWeNslhBfhN7BOSvl-AQofQDSiP0mC9GyRp8';

    public function submit($file_path)
    {
        try {
            if (!file_exists($file_path)) {
                throw new Exception("文件不存在: " . $file_path);
            }

            $filePath = $file_path;
            $url = 'https://om.qq.com/image/orginalupload';
            $data = [];
            
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

function convertToWebp($imagePath) {
    try {
        // 检查文件是否为图片
        $imageInfo = @getimagesize($imagePath);
        if ($imageInfo === false) {
            error_log("文件不是有效的图片: " . $imagePath);
            return false;
        }

        // 获取图片类型
        $mime = $imageInfo['mime'];
        
        // 如果已经是webp格式，直接返回
        if ($mime === 'image/webp') {
            $originalSize = filesize($imagePath);
            return [
                'path' => $imagePath,
                'compression_info' => [
                    'original_size' => $originalSize,
                    'compressed_size' => $originalSize,
                    'compression_ratio' => 0,
                    'settings' => [
                        'quality' => 'original',
                        'width' => $imageInfo[0],
                        'height' => $imageInfo[1]
                    ]
                ]
            ];
        }

        // 根据原图片类型创建图片资源
        switch ($mime) {
            case 'image/jpeg':
                $source = imagecreatefromjpeg($imagePath);
                break;
            case 'image/png':
                $source = imagecreatefrompng($imagePath);
                break;
            case 'image/gif':
                $source = imagecreatefromgif($imagePath);
                break;
            default:
                error_log("不支持的图片格式: " . $mime);
                return false;
        }

        if (!$source) {
            error_log("创建图片资源失败");
            return false;
        }

        // 获取原图尺寸
        $width = imagesx($source);
        $height = imagesy($source);

        // 创建新的图片
        $newImage = imagecreatetruecolor($width, $height);

        // 处理透明通道
        if ($mime == 'image/png' || $mime == 'image/gif') {
            // 保持透明度
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            
            // 设置透明背景
            $transparent = imagecolorallocatealpha($newImage, 0, 0, 0, 127);
            imagefilledrectangle($newImage, 0, 0, $width, $height, $transparent);
        }

        // 复制图片
        if (!imagecopyresampled($newImage, $source, 0, 0, 0, 0, $width, $height, $width, $height)) {
            error_log("复制图片失败");
            return false;
        }

        // 获取新的文件路径
        $webpPath = preg_replace('/\.[^.]+$/', '.webp', $imagePath);

        // 根据原始图片类型和大小动态调整质量
        $originalSize = filesize($imagePath);
        $quality = 82; // 默认质量

        // PNG图片可能需要更高的质量来保持清晰度
        if ($mime === 'image/png') {
            $quality = 90;
        }

        // 对于小图片，提高质量
        if ($originalSize < 100 * 1024) { // 小于100KB
            $quality = 92;
        }

        // 转换为WebP
        if (!imagewebp($newImage, $webpPath, $quality)) {
            error_log("保存WebP失败");
            return false;
        }

        // 检查转换后的大小，如果比原图大，尝试调整质量
        $newSize = filesize($webpPath);
        if ($newSize > $originalSize) {
            // 逐步降低质量直到文件大小小于原图或达到最低质量
            for ($q = $quality - 5; $q >= 60; $q -= 5) {
                imagewebp($newImage, $webpPath, $q);
                $newSize = filesize($webpPath);
                if ($newSize <= $originalSize) {
                    $quality = $q;
                    break;
                }
            }
        }

        // 释放资源
        imagedestroy($source);
        imagedestroy($newImage);

        if (file_exists($webpPath) && filesize($webpPath) > 0) {
            $compressedSize = filesize($webpPath);
            
            // 无论大小如何，都删除原文件（非webp格式）
            if ($mime !== 'image/webp') {
                unlink($imagePath);
            }
            
            $compressionRatio = round(($originalSize - $compressedSize) / $originalSize * 100, 2);
            
            error_log("图片转换成功: {$imagePath} -> {$webpPath}");
            error_log("压缩率: {$compressionRatio}% (原始大小: " . round($originalSize/1024, 2) . "KB, 压缩后: " . round($compressedSize/1024, 2) . "KB)");
            
            return [
                'path' => $webpPath,
                'compression_info' => [
                    'original_size' => $originalSize,
                    'compressed_size' => $compressedSize,
                    'compression_ratio' => $compressionRatio,
                    'settings' => [
                        'quality' => $quality,
                        'width' => $width,
                        'height' => $height
                    ]
                ]
            ];
        } else {
            error_log("WebP文件写入失败或文件大小为0: " . $webpPath);
            return false;
        }
    } catch (Exception $e) {
        error_log("转换WebP失败: " . $e->getMessage());
        return false;
    }
}

function processImageAsync($localPath, $historyFile) {
    try {
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        
        // 转换为WebP
        $result = convertToWebp($localPath);
        
        if ($result) {
            // 更新历史记录
            $history = file_exists($historyFile) ? json_decode(file_get_contents($historyFile), true) : [];
            
            // 查找并更新最后一条记录
            if (!empty($history)) {
                $lastEntry = &$history[count($history) - 1];
                
                // 更新文件路径
                $lastEntry['local_path'] = $result['path'];
                $lastEntry['file_type'] = 'image/webp';
                $lastEntry['conversion_status'] = 'success';
                
                // 更新压缩信息
                if (isset($result['compression_info'])) {
                    // 使用实际文件大小更新原始大小
                    $result['compression_info']['original_size'] = $lastEntry['file_size'];
                    
                    // 获取转换后文件的实际大小
                    $compressedSize = filesize($result['path']);
                    $result['compression_info']['compressed_size'] = $compressedSize;
                    
                    // 重新计算压缩率
                    $compressionRatio = round(($lastEntry['file_size'] - $compressedSize) / $lastEntry['file_size'] * 100, 2);
                    $result['compression_info']['compression_ratio'] = $compressionRatio;
                    
                    $lastEntry['compression_info'] = $result['compression_info'];
                }
                
                file_put_contents($historyFile, json_encode($history, JSON_PRETTY_PRINT));
                
                error_log("历史记录已更新，压缩率: " . $compressionRatio . "%");
            }
        }
    } catch (Exception $e) {
        error_log("异步处理失败: " . $e->getMessage());
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
            // 重命名文件
            $newLocalPath = renameUploadedFile($result['local_path'], $result['remote_url']);
            $result['local_path'] = $newLocalPath;

            // 记录上传历史
            $historyFile = 'uploads/history.json';
            $history = file_exists($historyFile) ? json_decode(file_get_contents($historyFile), true) : [];
            
            $history[] = [
                'local_path' => $result['local_path'],
                'remote_url' => $result['remote_url'],
                'upload_time' => date('Y-m-d H:i:s'),
                'file_size' => $file['size'],
                'file_type' => $file['type'],
                'conversion_status' => 'pending'
            ];
            
            file_put_contents($historyFile, json_encode($history, JSON_PRETTY_PRINT));

            // 立即返回腾讯URL
            echo json_encode([
                'response' => ['code' => '0'],
                'data' => [
                    'url' => ['url' => $result['remote_url']],
                    'local_path' => $result['local_path'],
                    'conversion_status' => 'pending'
                ]
            ]);

            // 异步处理图片转换
            processImageAsync($newLocalPath, $historyFile);

        } catch (Exception $e) {
            error_log("文件处理失败: " . $e->getMessage());
            // 即使处理失败也返回腾讯URL
            echo json_encode([
                'response' => ['code' => '0'],
                'data' => [
                    'url' => ['url' => $result['remote_url']],
                    'local_path' => $result['local_path'],
                    'error' => $e->getMessage()
                ]
            ]);
        }
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