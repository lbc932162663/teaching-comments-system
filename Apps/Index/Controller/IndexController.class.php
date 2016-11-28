<?php
namespace Index\Controller;
use Think\Controller;
class IndexController extends Controller {
    public function index($id = NULL){
        $User = M('User');
        $is = $User->where('name = "0"')->save([name=>'ming']);
    }
    public function hello() {
        echo "hello";
    }
}