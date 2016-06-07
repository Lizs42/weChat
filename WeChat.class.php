<?php

/**
 * 微信中常用的接口
 * Class WeChat
 */
class WeChat {

    public function checkError($error) {
        $message['display'] = 'true';
        try {
            if ($error) {
                throw new Exception($error);
            }
        } catch (Exception $e) {
            $message['error'] = $e->getMessage();
        }
        return $message;
    }

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

    /**
     * 获取微信支付文件：
     * 1、apiclient_cert.pem
     * 2、apiclient_key.pem
     * 3、rootca.pem
     * 为了保护支付文件的安全，一般不直接放在项目文件夹下面存放。
     * 而是将文件中的内容保存在数据库中，需要使用时，读取出来生成以上文件。
     * 而且，使用完之后删除掉生成的文件
     */
    public function getPayFile() {
        // 从数据库中将文件的内容拿出来
        $certs = array(
            'cert' => '',
            'key' => '',
            'root' => '',
        );
        // 检查证书是否上传完整
        if (empty($certs['cert']) || empty($certs['key']) || empty($certs['root'])) {
            $this->checkError('微信支付证书未上传完整');
        }

        $cert_file = __DIR__ . "/cert/apiclient_cert.pem";
        file_put_contents($cert_file, $certs['cert']);
        $key_file = __DIR__ . "/cert/apiclient_key.pem";
        file_put_contents($key_file, $certs['key']);
        $root_file = __DIR__ . "/cert/rootca.pem";
        file_put_contents($root_file, $certs['root']);
        $extras['CURLOPT_SSLCERT'] = $cert_file;
        $extras['CURLOPT_SSLKEY'] = $key_file;
        $extras['CURLOPT_CAINFO'] = $root_file;

        return $extras;
    }


    /**
     * 签名算法，生成微信请求参数：sign
     * @return string
     */
    function getSign($data) {
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
        $key = '';
        $stringSignTemp = $stringA . "key=" . $key;
        return strtoupper(md5($stringSignTemp));
    }

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

}