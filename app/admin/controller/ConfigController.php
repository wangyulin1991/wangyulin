<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/16
 * Time: 9:43
 */

namespace app\admin\controller;


use cmf\controller\AdminBaseController;
use think\Db;

class ConfigController extends AdminBaseController
{
    //流程配置列表
    public function index() {
        $list = Db::name('process a')
            ->join('process_config b', 'a.id=b.process_id', 'left')
            ->field('a.id as process_id, a.name, b.parent_commission, b.ancestry_commission, b.id')
            ->select();
        $this->assign('process', $list);
        return $this->fetch();
    }

    //编辑配置
    public function edit() {
        $id = intval(input('id'),0);
        $process_id = input('process_id');
        $is_new = 0;
        if (!$id) {
            $is_new = 1;
        }
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $rs = $this->validate($data, 'config.commission');
            if ($rs !== true) {
                $this->error($rs);
            } else {
                $res = $this->getParam(1, $data, $id);
                if (is_array($res)) {
                    $config = array('type'=>$data['type'], 'commission'=>$data['bg_total_commission'], 'parent_commission'=>$data['parent_commission'], 'ancestry_commission'=>$data['ancestry_commission']);
                    if ($is_new) {
                        $config['process_id']=$process_id;
                        $config['create_time']=time();
                        $id = Db::name('process_config')->insertGetId($config);
                    } else {
                        Db::name('process_config')->where(['id'=>$id])->update($config);
                    }
                    Db::name('range_config')->where(['process_config_id'=>$id, 'type'=>0])->delete();
                    $rs = Db::name('range_config')->insertAll($res);
                    if ($rs) {
                        $res = $this->getParam(2, $data, $id);
                        if (is_array($res)) {
                            Db::name('process_config')->where(['id'=>$id])->update(['bg_type'=>$data['bg_type']]);
                            Db::name('range_config')->where(['process_config_id'=>$id, 'type'=>1])->delete();
                            $rs = Db::name('range_config')->insertAll($res);
                            if ($rs) {
                                $this->success('更改成功');
                            } else {
                                $this->error('更改失败');
                            }
                        } else {
                            $this->error($rs);
                        }
                    } else {
                        $this->error('更改失败');
                    }
                } else {
                    $this->error($rs);
                }
            }
        }
        $info = Db::name('process a')
            ->join('process_config b', 'a.id=b.process_id', 'left')
            ->field('b.*,a.id as process_id, a.name')
            ->where(['a.id'=>$process_id])
            ->find();
        if ($info && !$is_new) {
            if ($info['type'] == 0) {
                $info['total_commission'] = Db::name('range_config')->where(['process_config_id'=>$id, 'type'=>0])->value('commission');
                $info['ranges'] = array();
            } else {
                $info['ranges'] = Db::name('range_config')->where(['process_config_id'=>$id, 'type'=>0])->order('id', 'asc')->select();
            }
            if ($info['bg_type'] == 0) {
                $info['bg_total_commission'] = Db::name('range_config')->where(['process_config_id'=>$id, 'type'=>1])->value('commission');
                $info['bg_ranges'] = array();
            } else {
                $info['bg_ranges'] = Db::name('range_config')->where(['process_config_id'=>$id, 'type'=>1])->order('id', 'asc')->select();
            }
        }
        $this->assign('config', $info);
        return $this->fetch();
    }


    private function getParam($type=1, $data=array(), $pc_id=0) {
        $comm_type = 'type';
        if ($type == 2) $comm_type = 'bg_type';
        if ($data[$comm_type] == 1) {
            $ranges = array();
            $ids = $data['range_id'];
            if ($type==2) {
                $ids = $data['bg_range_id'];
            }
            if (empty($ids)) {
                return '请添加区间配置';
            } else {
                foreach ($ids as $key => $val) {
                    if (!(is_numeric($data['start_price'.$val]) && is_numeric($data['end_price'.$val]) && is_numeric($data['range_type'.$val]) && is_numeric($data['range_num'.$val]))) {
                        return '区间配置内容不能留空';
                    } elseif($data['range_type'.$val]==2 && !is_numeric($data['step_num'.$val])) {
                        return '区间配置内容不能留空';
                    }
                    $ranges[$key]['start_price'] = $data['start_price'.$val];
                    $ranges[$key]['end_price'] = $data['end_price'.$val];
                    $ranges[$key]['range_type'] = $data['range_type'.$val];
                    $ranges[$key]['type'] = $type-1;
                    if ($data['range_type'.$val] == 2) {
                        $ranges[$key]['step_num'] = $data['step_num'.$val];
                    } else {
                        $ranges[$key]['step_num'] = 0;
                    }
                    $ranges[$key]['range_num'] = $data['range_num'.$val];
                    $ranges[$key]['process_config_id'] = $pc_id;
                    $ranges[$key]['create_time'] = time();
                }

            }
            return $ranges;
        } else{
            if ($type == 1) {
                if (empty($data['total_commission'])) {
                    return '总佣金不能为空';
                }
                return [['process_config_id'=>$pc_id, 'type'=>0, 'commission'=>$data['total_commission'], 'create_time'=>time()]];
            } else {
                if (empty($data['bg_total_commission'])) {
                    return '买手总佣金不能为空';
                }
                return [['process_config_id'=>$pc_id, 'type'=>1, 'commission'=>$data['bg_total_commission'], 'create_time'=>time()]];
            }
        }
    }

    //买手配置信息
    public function brush_config() {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $rs = $this->validate($data, 'config.brush');
            if ($rs !== true) {
                $this->error($rs);
            } else {
                Db::name('config')->where(['id'=>1])->update($this->request->param());
                $this->success('保存成功');
            }
        }
        $this->assign('config', Db::name('config')->find());
        return $this->fetch();
    }
}