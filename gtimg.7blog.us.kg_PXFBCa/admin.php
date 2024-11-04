<?php
session_start();
require_once 'config.php';

if (!isAdmin()) {
    header('Location: login.php');
    exit;
}

// 获取上传历史
function getUploadHistory() {
    $historyFile = 'uploads/history.json';
    if (file_exists($historyFile)) {
        $history = json_decode(file_get_contents($historyFile), true) ?? [];
        // 按上传时间倒序排序
        usort($history, function($a, $b) {
            return strtotime($b['upload_time']) - strtotime($a['upload_time']);
        });
        return $history;
    }
    return [];
}

// 处理删除请求
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    $filePath = $_POST['file_path'] ?? '';
    $remoteUrl = $_POST['remote_url'] ?? '';
    
    if (file_exists($filePath)) {
        unlink($filePath);
    }
    
    // 更新历史记录
    $history = getUploadHistory();
    $history = array_filter($history, function($item) use ($remoteUrl) {
        return $item['remote_url'] !== $remoteUrl;
    });
    file_put_contents('uploads/history.json', json_encode(array_values($history)));
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}

$history = getUploadHistory();
$totalSize = 0;
$totalCompressedSize = 0;
$totalFiles = count($history);

foreach ($history as $item) {
    $totalSize += $item['file_size'];
    if (isset($item['compression_info']['compressed_size'])) {
        $totalCompressedSize += $item['compression_info']['compressed_size'];
    }
}

$totalSavings = $totalSize - $totalCompressedSize;
$averageCompressionRatio = $totalSize > 0 ? ($totalSavings / $totalSize * 100) : 0;
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理后台 - 腾讯Gtimg图床</title>
    <style>
        :root {
            --primary-color: #3498db;
            --danger-color: #e74c3c;
            --success-color: #2ecc71;
            --background-color: #f0f4f8;
            --card-background: #ffffff;
            --text-color: #2c3e50;
            --border-color: #eaeaea;
            --hover-color: #f8f9fa;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Microsoft YaHei', sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
            padding: 20px;
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: var(--card-background);
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border-color);
        }

        .header h2 {
            font-size: 1.8rem;
            color: var(--primary-color);
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .stat-card {
            background: var(--card-background);
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-color);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--text-color);
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .table-container {
            overflow-x: auto;
            margin-top: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            background: var(--card-background);
        }

        .table th, 
        .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .table th {
            background-color: var(--hover-color);
            font-weight: 600;
            color: var(--text-color);
        }

        .table tr:hover {
            background-color: var(--hover-color);
        }

        .thumbnail {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .thumbnail:hover {
            transform: scale(1.05);
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            min-width: 100px;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: #2980b9;
        }

        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-danger:hover {
            background-color: #c0392b;
        }

        .btn-success {
            background-color: var(--success-color);
            color: white;
        }

        .btn-success:hover {
            background-color: #27ae60;
        }

        .actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .logout-btn {
            background-color: var(--danger-color);
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: background-color 0.3s ease;
        }

        .logout-btn:hover {
            background-color: #c0392b;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .stats {
                grid-template-columns: 1fr;
            }

            .table th, 
            .table td {
                padding: 0.75rem;
            }

            .actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }

        .copy-success {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--success-color);
            color: white;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: none;
            z-index: 1000;
        }

        .header-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .header a.btn-primary {
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: background-color 0.3s ease;
        }
        
        .header a.btn-primary:hover {
            background-color: #2980b9;
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 1rem;
            }
            
            .header-actions {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>图片管理后台</h2>
            <div class="header-actions">
                <a href="index.html" class="btn btn-primary">上传图片</a>
                <a href="logout.php" class="logout-btn">退出登录</a>
            </div>
        </div>

        <div class="stats">
            <div class="stat-card">
                <div class="stat-value"><?php echo $totalFiles; ?></div>
                <div class="stat-label">总文件数</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($totalSize / 1024 / 1024, 2); ?> MB</div>
                <div class="stat-label">原始总大小</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($totalCompressedSize / 1024 / 1024, 2); ?> MB</div>
                <div class="stat-label">压缩后总大小</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($averageCompressionRatio, 2); ?>%</div>
                <div class="stat-label">平均压缩率</div>
            </div>
        </div>
        
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>预览</th>
                        <th>上传时间</th>
                        <th>原始大小</th>
                        <th>压缩后大小</th>
                        <th>压缩率</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $item): ?>
                    <tr data-file="<?php echo htmlspecialchars($item['remote_url']); ?>">
                        <td>
                            <img src="<?php echo htmlspecialchars($item['local_path']); ?>" 
                                 alt="预览" class="thumbnail"
                                 onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgdmlld0JveD0iMCAwIDEwMCAxMDAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjEwMCIgaGVpZ2h0PSIxMDAiIGZpbGw9IiNmMGYwZjAiLz48dGV4dCB4PSI1MCIgeT0iNTAiIGZvbnQtc2l6ZT0iMTQiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGFsaWdubWVudC1iYXNlbGluZT0ibWlkZGxlIiBmaWxsPSIjOTk5Ij7lm77niYflpLHotKU8L3RleHQ+PC9zdmc+'">
                        </td>
                        <td><?php echo date('Y-m-d H:i:s', strtotime($item['upload_time'])); ?></td>
                        <td><?php echo number_format($item['file_size'] / 1024, 2); ?> KB</td>
                        <td>
                            <?php 
                            if (isset($item['compression_info']['compressed_size'])) {
                                echo number_format($item['compression_info']['compressed_size'] / 1024, 2) . ' KB';
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                        <td>
                            <?php 
                            if (isset($item['compression_info']['compression_ratio'])) {
                                echo number_format($item['compression_info']['compression_ratio'], 2) . '%';
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                        <td class="actions">
                            <button class="btn btn-primary" onclick="copyToClipboard('<?php echo $item['remote_url']; ?>')">复制链接</button>
                            <button class="btn btn-success" onclick="copyToClipboard('![Image](<?php echo $item['remote_url']; ?>)')">复制Markdown</button>
                            <button class="btn btn-danger" onclick="deleteImage('<?php echo $item['local_path']; ?>', '<?php echo $item['remote_url']; ?>')">删除</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="copySuccess" class="copy-success">已复制到剪贴板</div>

    <script>
        function showCopySuccess() {
            const successElement = document.getElementById('copySuccess');
            successElement.style.display = 'block';
            setTimeout(() => {
                successElement.style.display = 'none';
            }, 2000);
        }

        async function copyToClipboard(text) {
            try {
                const textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                showCopySuccess();
            } catch (error) {
                console.error('复制失败:', error);
                alert('复制失败，请手动复制');
            }
        }

        async function deleteImage(filePath, remoteUrl) {
            if (!confirm('确定要删除这张图片吗？')) {
                return;
            }

            try {
                const response = await fetch('admin.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=delete&file_path=${encodeURIComponent(filePath)}&remote_url=${encodeURIComponent(remoteUrl)}`
                });

                const data = await response.json();
                if (data.success) {
                    const row = document.querySelector(`tr[data-file="${remoteUrl}"]`);
                    if (row) {
                        row.remove();
                    }
                    location.reload();
                }
            } catch (error) {
                console.error('删除失败:', error);
                alert('删除失败，请稍后重试');
            }
        }
    </script>
</body>
</html> 