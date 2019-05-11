<?php

namespace Home\Model;

use Think\Model\ViewModel;

class ReadytopayViewModel extends ViewModel
{
    public $viewFields = array(
        'readytopay' => array('id', 'amount', 'time', 'applytime', 'remark'),
        'client' => array('name', 'referencenum', '_on' => 'readytopay.uid=client.id', '_type' => 'LEFT'),
    );
    //验证是否为空

    //根据id获取数据
    public function prepay($ids)
    {
        if ($ids == null) {
            return false;
        } else {
            session('id', $ids);
            $map['id'] = array('in', $ids);
            if ($result = $this->where($map)->select()) {
                return $result;
            } else {
                return false;
            }
        }
    }
}