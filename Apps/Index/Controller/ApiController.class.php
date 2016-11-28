<?php

namespace Index\Controller;

require 'vendor/autoload.php';

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
        echo '<h1>Api 接口 <a href="https://github.com/GiantMing/teaching-comments-system/blob/master/README.md">文档</a></h1>';    
    }

    public function register() {
        $name = I('post.name/s');
        $pwd = I('post.pwd/s');

        // 参数验证
        if($name == '' || $pwd == '') {
            return $this->ajaxReturn($this->return[412]);
        }


        $User = D('User');

        $isExist = !!$User->where(['name'=>$name])->find();
        
        if($isExist) {
            return $this->ajaxReturn($this->return[419]);
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


    }

    public function bindStuid() {
        $name = I('post.name/s');
        $stuid = I('post.stuid/s');

        if($name == '' || stuid == '') {
            $this.ajaxReturn($this->return[412]);
            return;
        }

        $RelUserStuid = M('RelUserStuid');

        $stuidOfName = !!$RelUserStuid->where(['name' => $name])->find();

        if($stuidOfName) {

            $RelUserStuid->where(['name' => $name])->save(['name' => $name, 'stuid' => $stuid]);
            $this->ajaxReturn($this->return[200]);
            return;
        }

        $RelUserStuid->create(['name' => $name, 'stuid' => stuid]);
        $isSuccess = $RelUserStuid->add();

        if($isSuccess) {
            $this->ajaxReturn($this->return[200]);
        } else {
            $this->ajaxReturn($this->return[500]);
        }
    }


    public function stuClass() {
        $stuid = I('get.stuid');
        $kebiao_api_url = 'http://hongyan.cqupt.edu.cn/api/kebiao';

        if($stuid == '') {
            return $this->ajaxReturn($this->return[412]);
        }


        


        $response = \Requests::post($kebiao_api_url, [], ['stuNum' => $stuid]);
        
        $data = json_decode($response->body);

        if($data->status != 200) {
            return $this->ajaxReturn($data);
        }
        
        $kebiao = $data->data;




    }





}