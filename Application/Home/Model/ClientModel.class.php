<?php

namespace Home\Model;

use Think\Model;

class ClientModel extends Model
{
    // 在新增的时候验证name字段是否唯一
    protected $_validate = array(
        array('name', 'require', '请填写客户姓名'),
    );

    //根据客户id来获取资料
    public function clientid($id)
    {
        $map['id'] = (int)$id;
        if (($info = $this->where($map)->find())) {
            return $info;
        } else {
            return false;
        }
    }

    //根据客户id获取其notes 过户日期
    public function clientnote($id)
    {
        $map['id'] = (int)$id;
        if (($notes = $this->where($map)->field('notes')->find())) {
            return $notes;
        } else {
            return false;
        }
    }

    //根据id查看是否存在该客户
    public function idexists($id)
    {
        $map['id'] = (int)$id;
        if ($this->where($map)->find()) {
            //存在该id
            return true;
        } else {
            return false;
        }
    }

    //根据客户名字来获取信息
    public function clientname($name)
    {
        $like = '%' . trim($name) . '%';
        $map['name'] = array('like', $like);
        if ($info = ($this->where($map)->select())) {
            return $info;
        } else {
            return false;
        }
    }

//    //添加新客户
//    public function addclient($name,$lot,$sale,$saletype,$balance,$referencenum){
//        if ($name=='' or $lot==''or $sale=='' or $saletype=='' or $balance==''){
//             return false;
//        }
//        $data['name']=$name;
//        $data['property']=$lot;
//        $data['sale']=$sale;
//        $data['saletype']=$saletype;
//        $data['balance']=number_format($balance,2);
//        if ($referencenum!==''){
//            $data['referencenum']=$referencenum;
//        }
//        $dir="D://客户档案/".$name.'/';
//        if (!(is_dir($dir))){
//            mkdir(iconv('utf-8', 'gbk', $dir));
//        };
//        $data['profile']=$dir;
//        if ($this->add($data)){
//             return true;//新增成功
//        }else{
//             return false;//新增失败
//        }
//
//    }
//

}