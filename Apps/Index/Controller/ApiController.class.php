<?php

namespace Index\Controller;

use Think\Controller;

class ApiController extends Controller {
    protected $return = [
        200 => [
                'status' => 200,
                'info' => 'success'
            ],
        401 => [
                'status' => 401,
                'info' => 'authorized failed'
            ],
        412 => [
                'status' => 412,
                'info' => 'lack of parameter'
            ],
        419 => [
                'status' => 419,
                'info' => 'name used'
            ],
        500 => [
                'status' => 500,
                'info' => 'server error'
            ],
    ];


    public function index() {
        echo '<h1>Api 接口 <a href="">文档</a></h1>';    
    }

    public function register() {
        $name = I('post.name/s');
        $pwd = I('post.pwd/s');

        // 参数验证
        if($name == '' || $pwd == '') {
            $this->ajaxReturn($this->return[412]);
            return;
        }


        $User = D('User');

        $isExist = !!$User->where(['name'=>$name])->find();
        
        if($isExist) {
            $this->ajaxReturn($this->return[419]);
            return;
        }

        $User->create(['name' => $name, 'pwd' => $pwd]);

        $isSuccess = $User->add();

        if($isSuccess) {
            $this->ajaxReturn($this->return[200]);
        } else {
            $this->ajaxReturn($this->return[500]);
        }

    }

    public function login() {
        $userInfo = [
            'name' => I('post.name/s'),
            'pwd' => I('post.pwd/s')
        ];

        if($userInfo['name'] == '' || $userInfo['pwd'] == '') {
            $this->ajaxReturn($this->return[412]);
            return;
        }
        $userInfo['pwd'] = md5($userInfo['pwd']);

        $User = D('User');

        $isSuccess = $User->where($userInfo)->find();

        if($isSuccess) {
            $this->ajaxReturn($this->return[200]);
        } else {
            $this->ajaxReturn($this->return[401]);
        }
        $this->ajaxReturn($this->return);


    }






}