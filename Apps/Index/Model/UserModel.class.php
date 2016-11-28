<?php

namespace Index\Model;
use Think\Model;

class UserModel extends Model {
    protected $_auto = [
        ['pwd', 'md5', 3, 'function'],
    ];
}