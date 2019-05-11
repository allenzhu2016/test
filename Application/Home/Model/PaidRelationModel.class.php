<?php

namespace Home\Model;

use Think\Model\RelationModel;

class PaidRelationModel extends RelationModel
{

    protected $tableName = 'Paid';
    protected $_link = array(
        'Readytopay' => array(
            'mapping_type' => self::HAS_MANY,
            'foreign_key' => 'pid',
            'mapping_name' => 'Readytopay',
            'mapping_order' => 'id desc',
        ));

}