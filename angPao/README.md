微信现金红包
===========

### 调用请求说明
+    请求 URL：`https://api.mch.weixin.qq.com/mmpaymkttransfers/sendredpack`
+    是否需要证书：是
+    请求方式： POST

### 请求参数（必填）
| 字段名 | 字段 | 示例值 | 类型 | 说明 |
|------------|--------|------------|---------|---------|
| 随机字符串 | nonce_str | 5K8264ILTKCH16CQ2502SI8ZNMTM67VS | String(32) | 随机字符串，不长于 32 位 |
| 商户号 | mch_billno | 1234567890 | String(32) | 微信支付的商户号 |
| 公众账号 appid | wxappid | wx1234567890abcdef | String(32) | 微信公众号的 appid (在`mp.weixin.qq.com`申请的) |
| 商户名称 | send_name | 京东商城 | String(32) | 红包发送者名称 |
|商户订单号 | mch_billno | 1234567890201606011234567890 | String(28) | 每个订单号必须唯一，组成：mch_id+yyyymmdd+10位一天内不能够重复的数字。|
| 用户 openid | re_openid | oxTWIuGaIt6gTKsQRLau2M0yL16E | String(32) | 接受红包的用户在 wxappid 下的 openid |
| Ip 地址 | client_ip | 192.168.0.1 | String(15) | 调用接口的机器的 IP 地址 |
| 付款金额 | total_amount | 1000 | int | 红包金额，**单位是分**。红包金额范围：￥1 ～ ￥200 |
| 红包发放总人数 | total_num | 1 | int | 红包发放总人数 |
| 红包祝福语 | wishing | 恭喜发财 | String(128) | 红包祝福语 |
| 活动名称 | act_name | 拜年 | String(32) | 活动名称 |
| 备注 | remark | 备注 | String(256) | 备注信息 |
| 签名 | sign | 5K8264ILTKCH16CQ2502SI8ZNMTM67VS | String(32) | 生成的签名 |
##### 数据示例：
```
<xml>
<sign><![CDATA[E1EE61A91C8E90F299DE6AE075D60A2D]]></sign>
<mch_billno><![CDATA[0010010404201411170000046545]]></mch_billno>
<mch_id><![CDATA[888]]></mch_id>
<wxappid><![CDATA[wxcbda96de0b165486]]></wxappid>
<send_name><![CDATA[send_name]]></send_name>
<re_openid><![CDATA[onqOjjmM1tad-3ROpncN-yUfa6uI]]></re_openid>
<total_amount><![CDATA[1000]]></total_amount>
<total_num><![CDATA[1]]></total_num>
<wishing><![CDATA[恭喜发财]]></wishing>
<client_ip><![CDATA[127.0.0.1]]></client_ip>
<act_name><![CDATA[拜年]]></act_name>
<remark><![CDATA[备注]]></remark>
<nonce_str><![CDATA[50780e0cca98c8c8e814883e5caa672e]]></nonce_str>
</xml>
```

### 实现步奏
+    获取随机字符串
```
/**
 * 获取指定长度的随机字符串
 * ASCII 码： a-z: 97-122; A-Z: 65-90
 * @param int $length
 * @return string
 */
public function getRandom($length = 32) {
   $nonce_str = '';
   for ($i=0; $i<$length; $i++) {
      $random = rand(0, 61);
      $c = $random < 10 ? rand(0, 9) : chr(rand(1, 26) + rand(0, 1)*32 + 64);
      $nonce_str .= $c;
   }
   return $nonce_str;
}
```

+    整合请求发送的数据
```
/**
* 微信发送红包的请求数据（除了签名参数 sign）
* @return array
*/
public function angPaoData() {
 // 活动名称
 $act_name = 'act_name';
 // 备注
 $remark = 'remark';
 $data = array(
    'nonce_str' => getRandom(32),
    'mch_billno' => $mch_billno.date('YmdHis').rand(1000, 9999),
    'mch_id' => $mch_billno, // 商户号
    'wxappid' => $wxappid, // 公众号 appid
    'send_name' => $send_name, // 商户名称
    're_openid' => $open_id, // 用户 openid
    'total_amount' => $total_amount, // 红包金额
    'total_num' => 1, // 红包发放人数
    'wishing' => $wishing, // 红包祝福语
    'client_ip' => $client_ip, // 当前客户端 IP 地址
    'act_name' => $act_name,
    'remark' => $remark,
 );
 return $data;
}
```

+    签名算法
```
/**
* 签名算法，生成微信请求参数：sign
* @return string
*/
function getSign($data) {
   global $_W;
   ksort($data, SORT_STRING);
   $stringA = '';
   // 第一步，将所有发送的参数按照 key=value 的格式组成字符串 stringA，
   // 并且 key 要按照 ASCII 码从小到大排序（字典序）
   foreach ($data as $k => $v) {
      if ($k && $v && $k != 'sign') {
         $stringA .= "{$k}={$v}&";
      }
   }
   // 第二步，在 stringA 最后拼接上 key 得到 stringSignTemp 字符串，
   // 并对 stringSignTemp 进行 MD5 运算，再将得到的字符串所有字符转换为大写
   // key 设置路径：微信商户平台(pay.weixin.qq.com)-->账户设置-->API安全-->密钥设置
   $setting = uni_setting($_W['uniacid'], array('payment'));
   $key = $setting['payment']['wechat']['apikey'];
   $stringSignTemp = $stringA . "key=" . $key;
   return strtoupper(md5($stringSignTemp));
}
```

+    发送数据的时候，不要忘记了将数据转换成指定的 xml 格式
```
/**
 * 将数据转换成符合传送要求的 xml 格式
 * @param $data
 * @return string
 */
function array2xml($data) {
   $xml = "<xml>";
   foreach ($data as $k => $v) {
      $xml .= "<" . $k . "><![CDATA[" . $v . "]]></" .$k . ">";
   }
   $xml .= "</xml>";
   return $xml;
}
```

+    获取微信支付证书文件

> 为了安全，一般将证书的内容保存到数据库中，使用的时候读取出来，保存到文件中去，用完之后，及时删除掉。

```
/**
* 获取微信支付文件：
* 1、apiclient_cert.pem
* 2、apiclient_key.pem
* 3、rootca.pem
*/
public function getPayFile() {
// 从数据库中取出来
 $sec = m('common')->getSec();
 $certs = iunserializer($sec['sec']);
 if (is_array($certs)) {
    if (empty($certs['cert']) || empty($certs['key']) || empty($certs['root'])) {
       message('未上传完整的微信支付证书，请到【系统设置】->【支付方式】中上传!', '', 'error');
    }
    $certfile = IA_ROOT . "/addons/sz_yi/cert/apiclient_cert.pem";
    file_put_contents($certfile, $certs['cert']);
    $keyfile = IA_ROOT . "/addons/sz_yi/cert/apiclient_key.pem";
    file_put_contents($keyfile, $certs['key']);
    $rootfile = IA_ROOT . "/addons/sz_yi/cert/rootca.pem";
    file_put_contents($rootfile, $certs['root']);
    $extras['CURLOPT_SSLCERT'] = $certfile;
    $extras['CURLOPT_SSLKEY'] = $keyfile;
    $extras['CURLOPT_CAINFO'] = $rootfile;
 } else {
    message('未上传完整的微信支付证书，请到【系统设置】->【支付方式】中上传!', '', 'error');
 }
 return @$extras ?: array();
}
```

+    用 CURL 发送数据
```
/**
 * @param $url
 * @param $vars
 * @param int $second
 * @param array $aHeader
 * @return bool|mixed
 */
function curl_post_ssl($vars, $second=30, $aHeader=array())
{
   $url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/sendredpack';
   $ch = curl_init($url);
   curl_setopt($ch, CURLOPT_TIMEOUT, $second);
   curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
   curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
   curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
   //cert 与 key 分别属于两个.pem文件
   //请确保您的libcurl版本是否支持双向认证，版本高于7.20.1
   foreach ($this->getPayFile() as $k => $v) {
      curl_setopt($ch, constant($k), $v);
   }
   if( count($aHeader) >= 1 ){
      curl_setopt($ch, CURLOPT_HTTPHEADER, $aHeader);
   }
   curl_setopt($ch, CURLOPT_POST, 1);
   curl_setopt($ch, CURLOPT_POSTFIELDS, $vars);
   $data = curl_exec($ch);
   if($data){
      curl_close($ch);
      return $data;
   }
   else {
      $error = curl_errno($ch);
      //echo "call faild, errorCode:$error\n";
      curl_close($ch);
      return false;
   }
}
```


+    发送红包
```
/**
* 发送红包
* @return bool|mixed
*/
public function angPaoPay() {
 // 红包请求数据
 $data = $this->angPaoData();
 $data['sign'] = $this->getSign($data);
 // 将数据转换成 xml 格式
 $postXml = array2xml($data);
 $responseXml = $this->curl_post_ssl($postXml);
 // 为了证书安全，及时删掉
 foreach ($this->getPayFile() as $file) {
    unlink($file);
 }
 return $responseXml;
}
```



