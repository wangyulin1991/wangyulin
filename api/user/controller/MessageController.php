<?php
namespace api\user\controller;

use cmf\controller\RestUserBaseController;
use think\Db;

class MessageController extends RestUserBaseController
{
    //首页显示最新消息
    public function new_one()
    {
        $where = 'a.status = 0 and a.rec_user_id ='.$this->userId;
        $result = Db::name('message')->alias('a')
            ->join('message_text b','a.message_id=b.id and b.status = 1')
            ->where($where)->field('a.id,a.status,a.message_id,b.message_text,b.send_time')->find();

        if($result){
            $result['message_text']=mb_substr(cmf_replace_content_file_url(htmlspecialchars_decode($result['message_text'])),0,10,'utf-8');
            $this->success('获取成功！', $result);
        }else{
            $this->error('暂无消息！');
        }
    }
    //站内通知列表
    public function listing()
    {
        $where = 'a.rec_user_id ='.$this->userId;
        $result = Db::name('message')->alias('a')
            ->join('message_text b','a.message_id=b.id and b.status = 1')
            ->where($where)->field('a.id,a.status,a.message_id,b.message_text,b.send_time')->order('b.id desc')->limit(0,10)->select()->toArray();
        if($result){
            foreach ($result as $k=>$v){
                $result[$k]['message_text']=mb_substr(cmf_replace_content_file_url(htmlspecialchars_decode($v['message_text'])),0,10,'utf-8');
            }
            $this->success('获取成功！', $result);
        }else{
            foreach ($result as $k=>$v){
                $result[$k]['message_text']='暂无消息通知';
            }
            $this->error('获取失败！',$result);
        }
    }

    //通知详情
    public function detail_msg()
    {
        $mid = $this->request->param("id");
        $msgid = $this->request->param("message_id");
        $result = Db::name('message_text')
            ->where('id',$msgid)->find();
        if($result){
            $result['message_text']=cmf_replace_content_file_url(htmlspecialchars_decode($result['message_text']));
            Db::name('message')->where('id',$mid)->update(['status'=>1]);
            $this->success('获取成功！', $result);
        }else{
            $this->error('获取失败！');
        }
    }

    //弃用
    //查看通知后，状态改成已查看
    public function Mstatus()
    {
        $mid = $this->request->param("id");
        if($mid){
            $where = 'id = '.$mid;
            $result = Db::name('message')
                ->where($where)->update(['status'=>1]);
            if($result){
                $this->success('更改状态成功！');
            }else{
                $this->error('更改状态失败！');
            }
        }else{
            $this->error('查看失败！');
        }
    }

    public function index(){
        $userId = $this->userId;
        //$userId = 178;
         //echo $userId;die;
        $result = Db::name('message')->alias('a')
            ->join('message_post b','a.message_id = b.id')
            ->join('message_category_post c','c.post_id = b.id')
            ->join('message_category d','c.category_id = d.id')
            ->field('a.status,a.rec_user_id,b.post_status,b.id,b.post_title,b.is_top,b.published_time,d.name')
            ->where(['a.rec_user_id'=>$userId,'a.status'=>0,'b.post_status'=>1,'d.status'=>1])
            ->order('b.id desc')
            ->select()->toArray();
        $data =$result;
        foreach ($result as $k=>$v){
            //$data[$k]['position'] = 0;
            $data[$k]['msg'] = 'http://' . $_SERVER['HTTP_HOST'] ."/msg.html?user_id=".$v['rec_user_id'];
//            if(strpos($v['is_top'],'普通') !== false){
//                $data[$k]['position'] = 0;
//                $data[$k]['msg'] = 'http://' . $_SERVER['HTTP_HOST'] ."/general.html";
//            }
//            if(strpos($v['name'],'必看') !== false){
//                $data[$k]['position'] = 1;
//                $data[$k]['msg'] = 'http://' . $_SERVER['HTTP_HOST'] ."/see.html";
//            }
        }
        $this->success('获取成功！', $data);

    }
}
