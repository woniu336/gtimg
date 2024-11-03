# 腾讯图床设置

获取token：

token 需要自己登录[腾讯开放平台](https://om.qq.com/userAuth/index)，然后登录一个账号刷新网页 F12 查看

需要提供 token的下面部分，例如：

```
userid=68552314;omaccesstoken=001fceb42ca1d843edf1066170a2a2bc5d9788a63587b8419549426a6b40c2052799417defb079f6a54b391efbb481e3f5da50123896b210607ba3f2e8fb6cf08cnv;omtoken=001fceb42ca1d843edf1066172a2bc5d9788a63587b82195461536a6b40c2052766417defb079f6a54b391efbb481e3f5da506d896db210607ba3f2e8fb6cf08cnv;
```

把**token** 填入upload.php的

```
private $token = ''
```



![](https://a2ecb11.webp.li/2023/09/7ad00f9ae9847e0f17f2bffdebf2b7af.webp)

