<?php

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;
use app\admin\model\MessageModel;

class MessageController extends AdminBaseController
{
    /* 文章列表 */
    public function index()
    {
        $messageModel = new MessageModel();
        $result = $messageModel->alias('a')
            ->join('message_text b','b.id=a.message_id')
            ->join('user c','a.send_id= c.id')
            ->field('b.*,count(a.id) as count_all,count(if(a.status=1,true,null)) as count_see,b.status,c.user_login')
            ->group('b.id')
            ->order('b.id desc')
            ->paginate(5);
        $message_texts = $result->items();
        foreach ($message_texts as $k=>$v){
            $message_texts[$k]['message_text'] = cmf_replace_content_file_url(htmlspecialchars_decode($v['message_text']));
        }

        $this->assign('message_info', $message_texts);
        return $this->fetch();
    }
    public function add()
    {
        if ($this->request->isPost()) {
            $parm = $this->request->param();
            $parm['send_id'] = cmf_get_current_admin_id();
            $parm['send_time'] = time();
            if(empty($parm['message_text'])){
                return json(['msg'=>'请填写内容！','code'=>400]);
                exit;
            }else{
                $parm['message_text']=htmlspecialchars(cmf_replace_content_file_url(htmlspecialchars_decode($parm['message_text']), true));
            }
            $id = Db::name('message_text')->insertGetId($parm);
            if($id){
                $brush_ids = Db::name('brush_guest')->where(['audit_status'=>1,'active_status'=>1,'is_black'=>['neq',2]])->field('id,user_id')->select();
                $data = array();
                $data['send_id'] = cmf_get_current_admin_id();
                $data['message_id'] = $id;
                $data['status'] = 0;//未发布状态
                foreach ($brush_ids as $k=>$v){
                    $data['rec_id'] = $v['id'];
                    $data['rec_user_id'] = $v['user_id'];
                    $rs = Db::name('message')->insert($data);
                }
                if($rs){
                    return json(['msg'=>'添加成功！','code'=>200]);
                }
            }
        }
        return $this->fetch();
    }
    public function edit()
    {
        if ($this->request->isPost()) {
            $parm = $this->request->param();
            if($parm['id']){
                if(empty($parm['message_text'])){
                    return json(['msg'=>'请填写内容！','code'=>400]);
                    exit;
                }else{
                    $rs = Db::name('message_text')->where('id',$parm['id'])->update(['message_text'=>$parm['message_text']]);
                    if($rs){
                        return json(['msg'=>'修改成功！','code'=>200]);
                    }
                }
            }

        }else {
            $id = $this->request->param("id", 0, 'intval');
            $info = Db::name('message_text')->find($id);
            $info['message_text']=cmf_replace_content_file_url(htmlspecialchars_decode($info['message_text']));
            $this->assign('message_info',$info);
            return $this->fetch();
        }
    }
    //发布消息
    public function publish(){
        $id = input('mid');
        if($id){
            $rs = Db::name('message_text')->where('id',$id)->update(['status'=>1]);
            if($rs){
                return json(['msg'=>'发布成功！','code'=>200]);
            }else{
                return json(['msg'=>'发布失败！','code'=>400]);
            }
        }
    }
}