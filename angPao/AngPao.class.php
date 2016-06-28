<?php

/**
 * 微信现金红包
 * Class AngPao
 */
class AngPao extends WeChat {

    /**
     * @param $url
     * @param $vars
     * @param int $second
     * @param array $aHeader
     * @return bool|mixed
     */
    function curlPostSsl($vars, $second=30, $aHeader=array())
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
            $this->checkError($error);
            curl_close($ch);
            return false;
        }
    }

    /**
     * 微信发送红包的请求数据（除了签名参数 sign）
     * @return array
     */
    public function angPaoData() {
        $mch_id = '';
        $data = array(
            'nonce_str' => $this->getRandom(32), // 随机字符串
            'mch_billno' => $mch_id.date('YmdHis').rand(1000, 9999), // 订单号
            'mch_id' => $mch_id, // 商户号
            'wxappid' => '', // 公众号 appid
            'send_name' => '', // 商户名称
            're_openid' => '', // 用户 openid
            'total_amount' => '', // 红包金额，单位：分
            'total_num' => 1, // 红包发放人数
            'wishing' => '', // 红包祝福语
            'client_ip' => '', // 当前客户端 IP 地址
            'act_name' => '', // 活动名称
            'remark' => '', // 备注
        );
        return $data;
    }

    /**
     * 发送红包
     * @return bool|mixed
     */
    public function angPaoPay() {
        // 红包请求数据
        $data = $this->angPaoData();
        // 参数 sign
        $data['sign'] = $this->getSign($data);
        // 将数据转换成 xml 格式
        $postXml = $this->array2xml($data);
        // 通过 CURL 发送请求
        $responseXml = $this->curlPostSsl($postXml);
        // 用完之后，及时删掉证书文件
        foreach ($this->getPayFile() as $file) {
            unlink($file);
        }
        return $responseXml;
    }
}