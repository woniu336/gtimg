## 开场白

通常你上传图片到大厂图床是没有备份的，什么京东，美团，百度啦，备份很重要不要忽略，但即使有备份，文件名也是随机返回的，你没法定义，

例如88.jpg，上传后返回https://qq.com/xxxxx.png，你有备份也没用，替换链接也找不回来。

所以，根据这个痛点，我把使用了两年的图床分享出来，她就是`腾讯图床`，你可以自己部署，下面的图片就是用此图床传的，缺点是不支持上传webp格式

特色是会在当前目录下生成一个uploads目录(如果没有手动创建)，里面是你上传的图片备份，会自动把图片名称替换成与返回的外链一致，举例：

> 外链: https://inews.gtimg.com/om_ls/OoGoIjnMKjvJeux0lY-wWDYDRB9LxidTHE_ak7FfBRaRkAA/0
>
> 那么图片的名称就是`OoGoIjnMKjvJeux0lY-wWDYDRB9LxidTHE_ak7FfBRaRkAA`



如果图床真挂了，怎么办，举例：

> 可以通过备份，再替换链接恢复图片，
>
> 替换的时候把前面的https://inews.gtimg.com/om_ls（固定不变的）替换，再把最后的`/0`替换成后缀（你能想到的方法）

![Image](https://inews.gtimg.com/om_ls/OBAevMiw9jkwhtUmq3b-Jok93wucOaRX0TFo5CiiEiYVMAA/0)



## 腾讯图床设置





1. **首先获取cookie** 

   登录[https://om.qq.com/userAuth/index](https://om.qq.com/userAuth/index)，可以微信扫码登录，刷新网页 F12 查看


![Image](https://inews.gtimg.com/om_ls/OOPlRSNvyB3i378FYznc9jSaGzgK0osWYf0ZpJ4-71OfQAA/0)





得到cookie后还没完，只需要cookie的后面一部分，从**userid**开始，例如



`userid=23510990; omaccesstoken=00d85d80422253f9a76dca9e3c8b1d89800ffffc350b972846a8cde5cfac7ac5380914b890a475cc8727dbcb851bc28dd36364ec08ce4786db648685abcff6064en9; omtoken=00d85d80422253f9a76dca9e3c8b1d89800ffffc350b972846a8cde5cfac7ac5380914b890a475cc8727dbcb851bc28dd36364ec08ce4786db648685abcff6064en9; logintype=4; srcopenid=osiAywoF-KqPC2wdCCRpPZi53IqA; srcaccessToken=86_HRgmjiH7F_1MgxCcZ93zLpX2TepHBTe8xbX27t0Zr3NPW5v92-yAVsCYuiPMpn8dOa4MsFNaSyP-iXOuUi2`



最好把**cookie** 填入upload.php的

```
private $token = ''
```

打开页面愉快上传吧，

## 我常用的备份方法

安装rclone, 设置好cloudflare r2 ，把图片定时备份到r2，以下是我常用的rclone备份公式：



```
rclone copy /www/wwwroot/1234.com/uploads r2:img/gtimg/uploads --ignore-existing -u -v -P --transfers=20 --ignore-errors --buffer-size=64M --check-first --checkers=15 --drive-acknowledge-abuse
```

`/www/wwwroot/1234.com/uploads`是需要备份的目录，

`r2:img/gtimg/uploads` 其中img是r2的存储名称

