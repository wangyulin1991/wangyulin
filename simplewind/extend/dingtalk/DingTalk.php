<?php
namespace dingtalk;

class DingTalk{

    static public function push_msg_dingding($message, $webhook='') {
        if (empty($webhook)) {
            $webhook = "https://oapi.dingtalk.com/robot/send?access_token=29d473ca16d064a7b8a9e07130a77685eddfcf923fd52d7665fdd669eff7a656";
        }
        $data = array ('msgtype' => 'text', 'text' => array ('content' => $message));
        $data_string = json_encode($data);
        $result = self::request_by_curl($webhook, $data_string);
    }

    static public function request_by_curl($remote_server, $post_string) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $remote_server);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array ('Content-Type: application/json;charset=utf-8'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // 线下环境不用开启curl证书验证, 未调通情况可尝试添加该代码
        curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $data = curl_exec($ch);
        curl_close($ch);

        return $data;
    }
}