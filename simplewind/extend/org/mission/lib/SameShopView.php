<?php
namespace org\mission\lib;

use org\mission\core\Step;
use think\Db;

class SameShopView extends Step
{
    public function execute()
    {
        $order = Db::name('order')->alias('a')
            ->join('task b','a.task_id=b.id')
            ->where(['a.id'=>$this->nowStep['order_id']])
            ->field('a.order_number, b.product_price, b.product_link, b.brush_action_input')
            ->find();
        if (!$order){
            $this->res = ['status'=>false,'msg'=>'未能找到订单'];
            return;
        }
        $shop_input = json_decode($order['brush_action_input'],true);
        if (isset($shop_input[$this->nowStep['step_type']])&&!empty($shop_input[$this->nowStep['step_type']])) {
            foreach ($shop_input[$this->nowStep['step_type']] as $value){
                if ($value['action']=='check') {
                    if (!isset($this->data[$value['ziduan']])||empty($this->data[$value['ziduan']])) {
                        $this->res = ['status'=>false,'msg'=>'请填写淘口令'];
                        return;
                    }
                    $kouling = $this->getKouling($this->data[$value['ziduan']]);
                    if (empty($kouling)) {
                        $this->res = ['status'=>false,'msg'=>'淘口令错误'];
                        return;
                    }
                    $link = parse_url(htmlspecialchars_decode($value['value']));
                    if (!isset($link['query'])) {
                        $this->res = ['status'=>false,'msg'=>'宝贝链接不对'];
                        return;
                    }
                    parse_str($link['query'], $query_arr);
                    if (empty($query_arr['id']) || $query_arr['id'] != $this->getProductId($kouling)) {
                        $this->res = ['status'=>false,'msg'=>'宝贝链接不对'];
                        return;
                    }
                }
            }
        }

        $this->res = ['status'=>true];
    }
    protected function makeTip()
    {
        $step = Db::name('order')->alias('a')
            ->join('task t','a.task_id = t.id','LEFT')
            ->field('a.*, t.brush_action_input')
            ->where(['a.id'=>$this->nowStep['order_id']])
            ->find();

        $brush_action_input = json_decode($step['brush_action_input'],true);
        $show = [];
        if (isset($brush_action_input[$this->nowStep['step_type']])&&!empty($brush_action_input[$this->nowStep['step_type']])) {
            $i = 1;
            foreach ($brush_action_input[$this->nowStep['step_type']] as $v){
                if($v['action']=='show'){
                    if ($v['input_type']=='image') {
                        $show[] = ['label'=>'商品图片', 'type'=> 'img', 'content'=>cmf_get_image_preview_url($v['value'])];
                    } else  {
                        if (preg_match("/[0-9]+[.0-9]*/i", $v['value'])) {
                            $show[] = ['label'=>'价格', 'type'=> 'text', 'content'=>$v['value'] . '元'];
                        } else {

                            $show[] = ['label'=>'名称'.$i, 'type'=> 'text', 'content'=>$v['value']];
                            $i++;
                        }
                    }

                }
            }
        }
        $this->nowStep['params'] = $show;
        $description = cmf_replace_content_file_url(htmlspecialchars_decode($this->nowStep['step_instruction']));
        $html = '<!DOCTYPE html><html><head>';
        $html .= '<title>' . $this->nowStep['step_name'] . '</title>';
        $html .= '<meta charset="utf-8">';
        $html .= '<meta content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no" name="viewport">';
        $html .= '</head><body>';
        $html .= '<div class="content">'.$description.'</div>';
        $html .= '<div class="bottom_btn">';
        $html .= '<from action="/api/task/index/doStep" method="post">';
        $html .= '</div></from>';
        $html .= '</body></html>';
        $this->nowStep['html'] = $html;
    }
    private function getKouling($tbToken) {
        //$reg = "#\x{ffe5}([a-zA-Z0-9]{11})\x{ffe5}#isu";
        $reg = "#[\x{0020}-\x{FFFF}]([a-zA-Z0-9]{11})[\x{0020}-\x{FFFF}]#isu";
        if(preg_match ($reg, $tbToken, $m) == 1)
        {
            return $m[0];
        }
        return '';
    }
    private function getProductId($kouling) {
        $url1 ='https://api.open.21ds.cn/apiv1/tpwdtoid?apkey=b0f46b4f-d0c2-f928-9b2e-6649e95c9a25&tpwd='.$kouling;
        $url2 ='https://api.open.21ds.cn/apiv1/jiexitkl?apkey=b0f46b4f-d0c2-f928-9b2e-6649e95c9a25&kouling='.$kouling;
        $res = json_decode(sendCurl($url1));
        if ($res) {
            if ($res->code == 200) {
                return $res->data;
            } else {
                $res = json_decode(sendCurl($url2));
                if ($res->code == 200) {
                    return $this->getItemid($res->data->url);
                }
            }
        }
        return '';
    }
    function getItemid($tbToken) {
        $reg = '^https:\/\/a\.m\.taobao\.com/i(\d+)\.htm\?*^';
        if(preg_match ($reg, $tbToken, $m) == 1)
        {
            return $m[1];
        }
        return '';
    }
}