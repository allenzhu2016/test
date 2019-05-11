<?php

namespace Home\Model;

use Think\Model\ViewModel;

class TransferViewModel extends ViewModel
{
    public $viewFields = array(
        'transfer' => array('id', 'transfertime', 'amount', 'account', 'type', 'receipt', 'remark', 'regtime'),
        'client' => array('id' => 'cid', 'name', '_on' => 'transfer.uid=client.id', '_type' => 'LEFT'),
    );
}