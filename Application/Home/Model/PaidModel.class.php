<?php

namespace Home\Model;

use Think\Model;

class PaidModel extends Model
{
    protected $_validate = array(
        array('amount', 'require', '请填写支付金额'),
        array('receiver', 'require', '请填写收款人'),
        array('receipt', 'require', '请填写支票号'),
        array('amount', 'currency', '请填写数字')
    );

}