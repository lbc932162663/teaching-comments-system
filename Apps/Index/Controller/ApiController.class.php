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

    // 课表去重  
    protected function kbUnique($kbList) {
        $hash = [];
        $unique = [];
        for ($i = 0; $i < count($kbList); $i++) { 
            $kebiao = $kbList[$i];

            $course_num = $kebiao->course_num;
            $teacher = $kebiao->teacher;
            $course_name = $kebiao->course;

            if($hash[$course_num] == 1) {
                continue;
            }
            $hash[$kebiao->course_num] = 1;

            $unique[] = [
                'id' => NULL,
                'course_num' => $course_num,
                'course_name' => $course_name,
                'teacher' => $teacher,
            ];

        }

        return $unique;
    }

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


    public function stuCourse() {
        $stuid = I('get.stuid');
        $kebiao_api_url = 'http://hongyan.cqupt.edu.cn/api/kebiao';

        if($stuid == '') {
            return $this->ajaxReturn($this->return[412]);
        }

        // 未绑定学号的用户不能直接通过此接口查询课表
        $hasBindUser = !!M('RelUserStuid')->where(['stuid' => $stuid])->find();

        if(!$hasBindUser) {
            return $this->ajaxReturn($this->return[401]);
        }


        // 查数据库有的话就返回了
        $Model = new \Think\Model();

        $courseList = $Model->query("SELECT * FROM course LEFT JOIN rel_stuid_course ON id=course_id WHERE stuid=%s", $stuid);

        if($courseList) {
            return $this->ajaxReturn([
                'status' => 200,
                'success' => 'success',
                'data' => $courseList,
            ]);
        }

        // 阿西吧, 请求数据
        $response = \Requests::post($kebiao_api_url, [], ['stuNum' => $stuid, 'forceFetch' => true]);

        $data = json_decode($response->body);

        if($data->status != 200) {
            return $this->ajaxReturn($data);
        }
        
        $kbList = $data->data;

        $kbList = $this->kbUnique($kbList);

        $Course = M('Course');
        $RelStuidCourse = M('RelStuidCourse');
        
        for ($i=0; $i < count($kbList); $i++) { 
            $kb = $kbList[$i];
            $condition['teacher'] = $kb['teacher'];
            $condition['course_num'] = $kb['course_num'];
            
            $c = $Course->where($condition)->find();
            

            if($c) {
                $rel = ['stuid'=>$stuid, 'course_id'=>$c['id']];
                
                $RelStuidCourse->create($rel);
                $RelStuidCourse->add();
                continue;
            }

            var_dump($kb);
            $Course->create($kb);
            $id = $Course->add();

            $rel = ['stuid' => $stuid, 'course_id' => $id];
            $RelStuidCourse->create($rel);
            $RelStuidCourse->add();

        }

        return $this->ajaxReturn([
            'status' => 200,
            'success' => 'success',
            'data' => $kbList,
        ]);
    }
}