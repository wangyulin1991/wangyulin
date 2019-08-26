<?php
/**
 * Created by billy.
 *
 * Name:徒弟相关操作
 * Author: billy
 * Date: 2019/2/14
 * Time: 21:19
 */

namespace api\user\controller;

use api\home\model\AreaModel;
use api\task\model\OrderModel;
use api\user\model\NextIncomeLogModel;
use api\user\model\UserModel;
use app\admin\model\BrushGuestModel;
use cmf\controller\RestUserBaseController;

class ApprenticeController extends RestUserBaseController
{
    private $audit_status=['待审核','已通过','未通过','待完善'];
    private $active_status=['待激活','已激活'];
    private $user_status = ['禁用','正常','未验证'];
    //已激活徒弟列表
    public function listing()
    {
        $page = $this->request->param('page',1,'intval');
        $brush_guest_id = $this->request->param('brush_guest_id',0,'intval');

        $brushGuestModel = new BrushGuestModel();
        $user_id = $this->getUserId();
        if ($brush_guest_id > 0 ) {
            $brush = $brushGuestModel->find($brush_guest_id);
            if ($brush['p_user_id']!=$user_id) {
                $this->error('错误的请求');
            } else {
                $user_id = $brush['user_id'];
            }
        } else {
        }
        $where = 'bg.p_user_id = ' . $user_id;
        if ($user_id>0){
            $result = $brushGuestModel->alias('bg')
                ->join('user u', 'bg.user_id = u.id', 'LEFT')
                ->field('bg.id as brush_guest_id, bg.user_id,bg.p_user_id, bg.real_name, bg.cellphone, bg.qq, bg.audit_status,bg.active_status, bg.create_time as audit_time, u.create_time as active_time, u.user_status, u.avatar')
                ->where($where)
                ->order('bg.id DESC')
                ->page($page,20)
                ->select()
                ->toArray();
            $nilModel = new NextIncomeLogModel();
            $total = 0;
            foreach ($result as $key=>$value){
                $res = $nilModel->where(['from_user'=>$value['user_id'],'p_user_id'=>$value['p_user_id'],'to_user'=>$this->userId])->value('money');
                if ($res) {
                    $result[$key]['en_money'] = $res;
                    $total+=$res;
                } else {
                    $result[$key]['en_money'] = '0.00';
                }
            }
            $count = $brushGuestModel->alias('bg')
                ->join('user u', 'bg.user_id = u.id', 'LEFT')
                ->where($where)
                ->order('bg.id DESC')
                ->count();
        } else {
            $result = [];
        }

        if ($result) {
            foreach ($result as $key => $brush){
                if($brush['active_status']>0 && $brush['user_id'] > 0){
                    $result[$key]['status_text'] = $this->user_status[$brush['user_status']];
                    $result[$key]['active_time'] = date('Y年m月d日',$brush['active_time']);
                } else {
                    $result[$key]['status_text'] = $this->audit_status[$brush['audit_status']].'['.$this->active_status[$brush['active_status']].']';
                    if ($result[$key]['active_time']>0) {
                        $result[$key]['active_time'] = date('Y年m月d日',$brush['active_time']);
                    } else {
                        $result[$key]['active_time'] = '';
                    }

                }
                $result[$key]['audit_time'] = date('Y年m月d日',$brush['audit_time']);
            }
            $data = ['total'=>$total,'count'=>$count,'list'=>$result];
            $this->success('获取成功', $data);
        } else {
            $this->success('未找到数据',['total'=>0,'count'=>0,'list'=>[]]);
        }

    }

    //徒弟信息汇总
    public function total()
    {
        $this->success('已关闭',[]);
        exit;
        $user_id = $this->getUserId();
        $where = 'bg.p_user_id = '.$user_id;
        $userModel = new UserModel();
        $count = $userModel->alias('a')->join('brush_guest bg', 'a.id=bg.user_id','LEFT')->where($where)->count();
        $brushGuestModel = new BrushGuestModel();
        $total = $brushGuestModel->where('user_id='.$user_id)->value('n_team_income');
        $this->success('获取成功',['count'=>$count,'total'=>$total]);
    }

    //徒弟详情
    public function detail()
    {
        $brush_guest_id = $this->request->param('brush_guest_id', '0', 'intval');
        if ($brush_guest_id > 0 ) {
            $brushGuestModel = new BrushGuestModel();
            $brush_guest = $brushGuestModel->alias('bg')
                ->join('user u', 'bg.user_id = u.id', 'LEFT')
                ->where(['bg.id'=>$brush_guest_id])
                ->field('bg.id as brush_guest_id, bg.*, bg.create_time as audit_time, u.create_time as active_time, u.user_status, u.avatar')
                ->find();
            if ($brush_guest) {
                unset($brush_guest['id']);
                unset($brush_guest['create_time']);
                unset($brush_guest['auditor']);
                if($brush_guest['audit_status']>2 && $brush_guest['user_id'] > 0){
                    $brush_guest['status_text'] = $this->user_status[$brush_guest['user_status']];
                    $brush_guest['active_time'] = date('Y年m月d日',$brush_guest['active_time']);
                } else {
                    $brush_guest['status_text'] = $this->audit_status[$brush_guest['audit_status']].'['.$this->active_status[$brush_guest['active_status']].']';
                    $brush_guest['active_time'] = '';
                }
                if (is_null($brush_guest['avatar'])) {
                    $brush_guest['avatar'] = '';
                }
                $brush_guest['audit_time'] = date('Y年m月d日',$brush_guest['audit_time']);
                $brush_guest['taobao_tq_link'] = cmf_get_image_preview_url($brush_guest['taobao_tq']);
                $brush_guest['taobao_photo_link'] = cmf_get_image_preview_url($brush_guest['taobao_photo']);
                $brush_guest['alipay_photo_link'] = cmf_get_image_preview_url($brush_guest['alipay_photo']);
                $brush_guest['jd_photo_link'] = cmf_get_image_preview_url($brush_guest['jd_photo']);
                $brush_guest['id_card_first_link'] = cmf_get_image_preview_url($brush_guest['id_card_first']);
                $brush_guest['id_card_second_link'] = cmf_get_image_preview_url($brush_guest['id_card_second']);
                $areaModel = new AreaModel();
                $brush_guest['address'] = '';
                if ($brush_guest['province']>0) {
                    $result = $areaModel->find($brush_guest['province']);
                    $brush_guest['address'] .= $result['areaName'];
                }
                if ($brush_guest['city']>0) {
                    $result = $areaModel->find($brush_guest['city']);
                    $brush_guest['address'] .= $result['areaName'];
                }
                if ($brush_guest['region']>0) {
                    $result = $areaModel->find($brush_guest['region']);
                    $brush_guest['address'] .= $result['areaName'];
                }

                $this->success('获取成功', $brush_guest);
            } else {
                $this->error('未找到数据');
            }
        } else {
            $this->error('参数错误');
        }
    }

    //添加徒弟的条件
    public function condition()
    {
        $orderModel = new OrderModel();
        $count = $orderModel->where(['brush_guest_id'=>$this->userId,'status'=>4])->count();
        if ($count<10)
        {
            $this->error('完成10单后可收徒');
        }else{
            $this->success('成功');
        }
    }

    //添加徒弟
    public function add()
    {
        if ($this->request->isPost()) {
            $result = $this->validate($this->request->param(), 'BrushGuest');
            if ($result !== true) {
                $this->error($result);
            } else {
                $userId = $this->getUserId();
                $data = $this->request->param();
                $userModel = new UserModel();
                $user = $userModel->where(['mobile'=>$data['cellphone']])->find();
                if (isset($user['id'])&&$user['id']>0) {
                    $this->error('手机号码已被使用');
                }
                $user = $userModel->find($userId);
                if ($user) {
                    $data['f_cellphone'] = $user['mobile'];
                    $data['p_user_id'] = $userId;
                    if((int)$data['province']<0){
                        $data['province']=0;
                    }
                    if((int)$data['city']<0){
                        $data['city']=0;
                    }
                    if((int)$data['region']<0){
                        $data['region']=0;
                    }
                    $brushGuestModel = new BrushGuestModel;
                    $brushGuestModel->save($data);
                    $this->success("添加成功！");
                } else {
                    $this->error("未找到推荐人！");
                }
            }
        }
    }

    //编辑徒弟信息
    public function edit()
    {
        if ($this->request->isPost()) {
            $id = $this->request->param('id',0,'intval');
            if ($id>0) {
                $result = $this->validate($this->request->param(), 'BrushGuest');
                if ($result !== true) {
                    $this->error($result);
                } else {
                    $brushGuestModel = new BrushGuestModel;
                    $brush = $brushGuestModel->find($id);
                    $userId = $this->getUserId();
                    if (!$brush || $brush['p_user_id']!=$userId) {
                        $this->error("错误的操作！");
                        exit;
                    }
                    $data = $this->request->param();
                    $userModel = new UserModel();
                    $user = $userModel->find($userId);
                    if ($user) {
                        $data['f_cellphone'] = $user['mobile'];
                        $data['p_user_id'] = $userId;
                        $data['audit_status'] = 0;
                        if((int)$data['province']<0){
                            $data['province']=0;
                        }
                        if((int)$data['city']<0){
                            $data['city']=0;
                        }
                        if((int)$data['region']<0){
                            $data['region']=0;
                        }

                        $brushGuestModel->save($data,['id',$id]);
                        $this->success("修改成功！");
                    } else {
                        $this->error("未找到推荐人！");
                    }
                }
            } else {
                $this->error("参数错误！");
            }
        }
    }
}