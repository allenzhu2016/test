<?php

namespace Home\Model;

use Think\Model;

class ReadytopayModel extends Model
{
    protected $patchValidate = true;
    protected $_validate = array(
        array('uid', 'require', '请填写客户编号'),
        array('amount', 'require', '请填写支付金额'),
        array('applytime', 'require', '请填写申请时间'),
        array('remark', 'require', '请填写备注'),
    );

    //总的钱
    public function totalmoney($bill)
    {
        if ($bill == null) {
            return false;
        } else {
            $sum = 0;
            foreach ($bill as $val) {
                $sum += $val['amount'];
            }
            return $sum;
        }
    }

    //自定义支付
    public function myform()
    {
        $this->display();
    }

}