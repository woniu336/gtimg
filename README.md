# 注意事项

需要在img标签加防盗链

```
referrerpolicy="no-referrer"
```



后台默认登录用户名和密码：`admin/admin123`

修改密码：config.php

## 快速上手

### 1. 获取授权信息

首先需要获取腾讯开放媒体平台的 Cookie：

1. 访问 [腾讯开放媒体平台](https://om.qq.com/userAuth/index)
2. 使用微信扫码登录
3. 按 F12 打开开发者工具，找到 Cookie 信息

> 💡 **重要提示**：我们只需要 Cookie 中从 **userid** 开始的部分，例如：
```
userid=23510990; omaccesstoken=xxx; omtoken=xxx; logintype=4; srcopenid=xxx; srcaccessToken=xxx
```
![image.png](https://inews.gtimg.com/om_ls/OOPlRSNvyB3i378FYznc9jSaGzgK0osWYf0ZpJ4-71OfQAA/0)


### 2. 配置图床

将获取到的 Cookie 填入 后台管理的Token 配置中

完成配置后，就可以开始使用图床服务了！

### 3. 数据备份方案

为了确保数据安全，我推荐使用 rclone 配合 Cloudflare R2 进行自动备份。以下是经过优化的备份命令：

```bash
rclone copy /www/wwwroot/1234.com/uploads r2:img/gtimg/uploads \
    -u -v -P \
    --transfers=20 \
    --ignore-errors \
    --buffer-size=64M \
    --check-first \
    --checkers=15 \
    --drive-acknowledge-abuse
```

参数说明：
- `/www/wwwroot/1234.com/uploads`：本地图片目录
- `r2:img/gtimg/uploads`：R2 存储路径（其中 `img` 为你的 R2 存储桶名称）
- 其他参数已优化为最佳性能配置

## 特别说明

⚠️ **使用限制**：目前不支持上传 avif 格式的图片


## 图片恢复方案

如果图床服务出现问题，可以通过以下步骤快速恢复：

1. 使用备份中的图片文件
2. 批量替换链接前缀 `https://inews.gtimg.com/om_ls`
3. 把/0替换成.webp

这样的设计确保了即使服务中断，你的图片资源也不会丢失。

---