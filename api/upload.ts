import { VercelRequest, VercelResponse } from '@vercel/node';
import formidable, { Fields, Files, File } from 'formidable';
import fetch from 'node-fetch';
import OSS from 'ali-oss';
import FormData from 'form-data';

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

interface FormidableFile extends File {
  type: string;
  size: number;
}

async function uploadToGtimg(file: FormidableFile) {
  try {
    const formData = new FormData();
    formData.append('Filedata', file.data, {
      filename: file.name,
      contentType: file.type,
    });
    formData.append('subModule', 'userAuth_individual_head');
    formData.append('id', 'WU_FILE_0');
    formData.append('name', file.name);
    formData.append('type', file.type);
    formData.append('lastModifiedDate', new Date().toUTCString());
    formData.append('appkey', '1');
    formData.append('isRetImgAttr', '1');
    formData.append('from', 'user');

    const ip = `${Math.floor(Math.random() * 92 + 48)}.${Math.floor(Math.random() * 230 + 10)}.${Math.floor(Math.random() * 230 + 10)}.${Math.floor(Math.random() * 230 + 10)}`;

    const response = await fetch('https://om.qq.com/image/orginalupload', {
      method: 'POST',
      body: formData,
      headers: {
        'Accept': '*/*',
        'Accept-Encoding': 'gzip, deflate, br',
        'Accept-Language': 'zh-CN,zh;q=0.9',
        'Connection': 'keep-alive',
        'Referer': 'https://om.qq.com/userReg/mediaInfo',
        'CLIENT-IP': ip,
        'X-FORWARDED-FOR': ip,
        'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/103.0.0.0 Safari/537.36',
        'Cookie': process.env.GTIMG_TOKEN || '',
      },
    });

    const data = await response.json();
    return data;
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
    const extension = file.name.split('.').pop();
    const ossPath = `uploads/${new Date().getFullYear()}/${String(new Date().getMonth() + 1).padStart(2, '0')}/${String(new Date().getDate()).padStart(2, '0')}/${newFileName}.${extension}`;

    const result = await ossClient.put(ossPath, file.data);
    return result.url;
  } catch (error) {
    console.error('上传到OSS失败:', error);
    throw error;
  }
}

export default async function handler(req: VercelRequest, res: VercelResponse) {
  if (req.method !== 'POST') {
    return res.status(405).json({ error: '方法不允许' });
  }

  try {
    const form = formidable();
    const [fields, files] = await new Promise<[Fields, Files]>((resolve, reject) => {
      form.parse(req, (err, fields, files) => {
        if (err) reject(err);
        resolve([fields, files]);
      });
    });

    const file = files.Filedata as FormidableFile;
    if (!file) {
      return res.status(400).json({ error: '没有接收到文件' });
    }

    // 文件类型验证
    const allowedTypes = ['image/jpeg', 'image/png'];
    if (!allowedTypes.includes(file.type)) {
      return res.status(400).json({ error: '不支持的文件类型' });
    }

    // 文件大小验证 (10MB)
    const maxSize = 10 * 1024 * 1024;
    if (file.size > maxSize) {
      return res.status(400).json({ error: '文件大小超过限制' });
    }

    // 先上传到腾讯图床
    const gtimgResult = await uploadToGtimg(file);
    if (!gtimgResult?.data?.url?.url) {
      return res.status(500).json({ error: '上传到腾讯图床失败' });
    }

    const gtimgUrl = gtimgResult.data.url.url;

    // 尝试上传到OSS
    let ossUrl = null;
    try {
      ossUrl = await uploadToOSS(file, gtimgUrl);
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
      message: error.message || '上传失败'
    });
  }
} 