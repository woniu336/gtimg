# 腾讯图床设置

获取token：

token 需要自己登录[腾讯开放平台](https://om.qq.com/userAuth/index)，然后登录一个账号刷新网页 F12 查看

需要提供 token的下面部分，例如：

`userid=68552314;omaccesstoken=001fceb42ca1d843edf1066170a2a2bc5d9788a63587b8419549426a6b40c2052799417defb079f6a54b391efbb481e3f5da50123896b210607ba3f2e8fb6cf08cnv;omtoken=001fceb42ca1d843edf1066172a2bc5d9788a63587b82195461536a6b40c2052766417defb079f6a54b391efbb481e3f5da506d896db210607ba3f2e8fb6cf08cnv;`

把**token** 填入upload.php的

```
private $token = ''
```

上传的图片会在当面目录生成一个uploads目录，里面是你上传的图片备份，然后图片名与图床保持一致，目的是万一图床失效挂壁了，可以通过备份，再替换链接恢复图片



举例：

图床url:  https://inews.gtimg.com/om_ls/OoGoIjnMKjvJeux0lY-wWDYDRB9LxidTHE_ak7FfBRaRkAA/0

图片名就是**`OoGoIjnMKjvJeux0lY-wWDYDRB9LxidTHE_ak7FfBRaRkAA`**

替换的时候把前面的https://inews.gtimg.com/om_ls（固定不变的）替换，再把最后的`/0`移除（你能想到的方法）

![](https://a2ecb11.webp.li/2023/09/7ad00f9ae9847e0f17f2bffdebf2b7af.webp)

