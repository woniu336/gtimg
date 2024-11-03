import type { VercelRequest, VercelResponse } from '@vercel/node';
import formidable from 'formidable';
import type { Fields, Files } from 'formidable';
import fetch from 'node-fetch';
import OSS from 'ali-oss';
import FormData from 'form-data';
import { readFileSync } from 'fs';
import { Readable } from 'stream';

// 配置阿里云OSS客户端
const ossClient = new OSS({
  region: process.env.OSS_REGION,
  accessKeyId: process.env.OSS_ACCESS_KEY_ID,
  accessKeySecret: process.env.OSS_ACCESS_KEY_SECRET,
  bucket: process.env.OSS_BUCKET,
});

export const config = {
  api: {
    bodyParser: false,
  },
};

interface FormidableFile {
  filepath: string;
  originalFilename: string;
  mimetype: string;
  size: number;
}

async function streamToBuffer(stream: Readable): Promise<Buffer> {
  const chunks: Buffer[] = [];
  return new Promise((resolve, reject) => {
    stream.on('data', (chunk) => chunks.push(Buffer.from(chunk)));
    stream.on('error', (err) => reject(err));
    stream.on('end', () => resolve(Buffer.concat(chunks)));
  });
}

async function uploadToGtimg(file: FormidableFile) {
  try {
    console.log('开始读取文件:', file.filepath);
    let fileBuffer;
    try {
      fileBuffer = readFileSync(file.filepath);
      console.log('文件读取成功, 大小:', fileBuffer.length);
    } catch (err) {
      console.error('文件读取失败:', err);
      throw new Error(`文件读取失败: ${err.message}`);
    }

    const formData = new FormData();
    
    try {
      formData.append('Filedata', fileBuffer, {
        filename: file.originalFilename || 'image.jpg',
        contentType: file.mimetype,
      });
      console.log('文件已添加到FormData');
    } catch (err) {
      console.error('FormData添加文件失败:', err);
      throw new Error(`FormData添加文件失败: ${err.message}`);
    }

    // 添加必要的表单字段
    const fields = {
      subModule: 'userAuth_individual_head',
      id: 'WU_FILE_0',
      name: file.originalFilename || 'image.jpg',
      type: file.mimetype,
      lastModifiedDate: new Date().toUTCString(),
      appkey: '1',
      isRetImgAttr: '1',
      from: 'user'
    };

    Object.entries(fields).forEach(([key, value]) => {
      formData.append(key, value);
    });

    const ip = `${Math.floor(Math.random() * 92 + 48)}.${Math.floor(Math.random() * 230 + 10)}.${Math.floor(Math.random() * 230 + 10)}.${Math.floor(Math.random() * 230 + 10)}`;
    
    const headers = {
      ...formData.getHeaders(),
      'Accept': 'application/json, text/plain, */*',
      'Accept-Language': 'zh-CN,zh;q=0.9,en;q=0.8',
      'Connection': 'keep-alive',
      'Origin': 'https://om.qq.com',
      'Referer': 'https://om.qq.com/userReg/mediaInfo',
      'Sec-Fetch-Dest': 'empty',
      'Sec-Fetch-Mode': 'cors',
      'Sec-Fetch-Site': 'same-origin',
      'CLIENT-IP': ip,
      'X-FORWARDED-FOR': ip,
      'X-Requested-With': 'XMLHttpRequest',
      'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
      'Cookie': process.env.GTIMG_TOKEN || '',
    };

    console.log('准备发送请求...');

    let response;
    try {
      response = await fetch('https://om.qq.com/image/orginalupload', {
        method: 'POST',
        body: formData,
        headers,
        redirect: 'manual',
      });

      console.log('请求已发送, 状态码:', response.status);
      console.log('响应头:', response.headers);

      // 如果是重定向
      if (response.status === 302 || response.status === 301) {
        throw new Error('登录已过期，需要更新Cookie');
      }

      // 检查内容类型
      const contentType = response.headers.get('content-type');
      console.log('响应内容类型:', contentType);

      const responseText = await response.text();
      console.log('原始响应:', responseText.substring(0, 500));

      // 如果响应包含HTML标签，可能是登录页面
      if (responseText.includes('<!DOCTYPE html>') || responseText.includes('<html')) {
        throw new Error('收到HTML响应，可能需要更新Cookie');
      }

      try {
        const data = JSON.parse(responseText);
        console.log('解析后的响应:', data);
        return data;
      } catch (err) {
        console.error('JSON解析失败:', err);
        throw new Error(`无效的JSON响应: ${responseText.substring(0, 100)}...`);
      }

    } catch (err) {
      console.error('请求或解析失败:', err);
      throw new Error(`请求失败: ${err.message}`);
    }
  } catch (error) {
    console.error('上传到腾讯图床失败:', error);
    throw error;
  }
}

async function uploadToOSS(file: FormidableFile, remoteUrl: string) {
  try {
    const matches = remoteUrl.match(/\/([^\/]+)\/0$/);
    if (!matches) {
      throw new Error('无法从URL提取文件名');
    }

    const newFileName = matches[1];
    const extension = file.originalFilename?.split('.').pop() || 'jpg';
    const ossPath = `uploads/${new Date().getFullYear()}/${String(new Date().getMonth() + 1).padStart(2, '0')}/${String(new Date().getDate()).padStart(2, '0')}/${newFileName}.${extension}`;

    const fileBuffer = readFileSync(file.filepath);
    const result = await ossClient.put(ossPath, fileBuffer);
    return result.url;
  } catch (error) {
    console.error('上传到OSS失败:', error);
    throw error;
  }
}

export default async function handler(req: VercelRequest, res: VercelResponse) {
  console.log('收到上传请求');
  
  if (req.method !== 'POST') {
    console.log('非POST请求被拒绝');
    return res.status(405).json({ error: '方法不允许' });
  }

  try {
    const form = formidable({
      keepExtensions: true,
      maxFileSize: 10 * 1024 * 1024,
    });

    console.log('开始解析上传文件...');
    let fields, files;
    try {
      [fields, files] = await new Promise<[Fields, Files]>((resolve, reject) => {
        form.parse(req, (err, fields, files) => {
          if (err) {
            console.error('文件解析失败:', err);
            reject(err);
          }
          resolve([fields, files]);
        });
      });
      console.log('文件解析成功');
    } catch (err) {
      console.error('文件解析出错:', err);
      throw new Error(`文件解析失败: ${err.message}`);
    }

    const fileData = files.Filedata;
    if (!fileData || Array.isArray(fileData)) {
      console.error('没有接收到文件或文件格式错误');
      return res.status(400).json({ error: '没有接收到文件' });
    }

    const file = fileData as unknown as FormidableFile;
    console.log('文件信息:', {
      name: file.originalFilename,
      type: file.mimetype,
      size: file.size,
      path: file.filepath
    });

    // 文件类型验证
    const allowedTypes = ['image/jpeg', 'image/png'];
    if (!allowedTypes.includes(file.mimetype)) {
      return res.status(400).json({ error: '不支持的文件类型' });
    }

    // 文件大小验证 (10MB)
    const maxSize = 10 * 1024 * 1024;
    if (file.size > maxSize) {
      return res.status(400).json({ error: '文件大小超过限制' });
    }

    console.log('开始上传到腾讯图床...');
    const gtimgResult = await uploadToGtimg(file);
    console.log('腾讯图床返回结果:', gtimgResult);

    if (!gtimgResult?.data?.url?.url) {
      return res.status(500).json({ 
        response: { code: '1' },
        message: '上传到腾讯图床失败',
        debug: gtimgResult 
      });
    }

    const gtimgUrl = gtimgResult.data.url.url;
    console.log('获取到腾讯图床URL:', gtimgUrl);

    // 尝试上传到OSS
    let ossUrl = null;
    try {
      console.log('开始上传到OSS...');
      ossUrl = await uploadToOSS(file, gtimgUrl);
      console.log('OSS上传成功:', ossUrl);
    } catch (error) {
      console.error('OSS上传失败，但继续返回腾讯URL:', error);
    }

    // 返回结果
    return res.status(200).json({
      response: { code: '0' },
      data: {
        url: { url: gtimgUrl },
        oss_url: ossUrl,
      }
    });

  } catch (error) {
    console.error('上传处理失败:', error);
    return res.status(500).json({
      response: { code: '1' },
      message: error instanceof Error ? error.message : '上传失败',
      debug: {
        error: error instanceof Error ? {
          message: error.message,
          stack: error.stack,
        } : error,
        env: {
          hasGtimgToken: !!process.env.GTIMG_TOKEN,
          tokenLength: process.env.GTIMG_TOKEN?.length || 0,
        }
      }
    });
  }
} 