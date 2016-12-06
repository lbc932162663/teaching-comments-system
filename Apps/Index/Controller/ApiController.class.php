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
        402 => [
                'status' => 402,
                'info' => 'lack of authorized'
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

    // 获取课表
    protected function getStuCourse($stuid) {

        $Model = new \Think\Model();
        $kebiao_api_url = 'http://hongyan.cqupt.edu.cn/api/kebiao';

        // 查数据库, 有就直接返回
        $courseList = $Model->query("SELECT * FROM course LEFT JOIN rel_stuid_course ON id=course_id WHERE stuid=%s", $stuid);
        if($courseList) {
            return $courseList;
        }

        // 阿西吧, 要请求
        $response = \Requests::post($kebiao_api_url, [], ['stuNum' => $stuid, 'forceFetch' => true]);

        $data = json_decode($response->body);

        if($data->status != 200) {
            return false;
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
                $kbList[$i]['id'] = $c['id'];
                $rel = ['stuid'=>$stuid, 'course_id'=>$c['id']];
                
                $RelStuidCourse->create($rel);
                $RelStuidCourse->add();
                continue;
            }

            $Course->create($kb);
            $id = $Course->add();

            $kbList[$i]['id'] = $id;

            $rel = ['stuid' => $stuid, 'course_id' => $id];
            $RelStuidCourse->create($rel);
            $RelStuidCourse->add();
        }
        return $kbList;
    }

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

    // 首页
    public function index() {
        echo '<h1>Api 接口 <a href="https://github.com/GiantMing/teaching-comments-system/blob/master/README.md">文档</a></h1>';    
    }

    // 注册
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

    // 登录
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

    // 绑定学号
    public function bindStuid() {
        $name = I('post.name/s');
        $stuid = I('post.stuid/s');

        if($name == '' || stuid == '') {
            $this->ajaxReturn($this->return[412]);
            return;
        }

        $RelUserStuid = M('RelUserStuid');

        $stuidOfName = !!$RelUserStuid->where(['name' => $name])->find();

        if($stuidOfName) {

            $RelUserStuid->where(['name' => $name])->save(['name' => $name, 'stuid' => $stuid]);
            $this->ajaxReturn($this->return[200]);
            return;
        }

        $RelUserStuid->create(['name' => $name, 'stuid' => $stuid]);
        $isSuccess = $RelUserStuid->add();

        if($isSuccess) {
            $this->ajaxReturn($this->return[200]);
        } else {
            $this->ajaxReturn($this->return[500]);
        }
    }


    public function stuCourse() {
        $stuid = I('get.stuid');

        if($stuid == '') {
            return $this->ajaxReturn($this->return[412]);
        }

        // 未绑定学号的用户不能直接通过此接口查询课表
        $hasBindUser = !!M('RelUserStuid')->where(['stuid' => $stuid])->find();
        if(!$hasBindUser) {
            return $this->ajaxReturn($this->return[401]);
        }

        // 获取课表
        $kbList = $this->getStuCourse($stuid);

        if($kbList == false) {
            return $this->ajaxReturn($this->return[500]);
        }

        if($kbList == NULL) {
            $kbList = [];
        }

        return $this->ajaxReturn([
            'status' => 200,
            'info' => 'success',
            'data' => $kbList,
        ]);
    }

    public function comment() {

        $Comment = M('Comment');

        if(IS_GET) {
            $course_id = I('get.course_id/s');
            if(course_id == '') {
                return $this->ajaxReturn($this->return[412]);
            }
            try {
                
                $comments = $Comment->where(['course_id' => $course_id])->select();
                
                return $this->ajaxReturn([
                    'status' => 200,
                    'info'=> 'success',
                    'data'=> $comments,
                ]);
            } catch (Exception $e) {
                return $this->ajaxReturn($this->return[500]);
            }
        }



        $user_name = I('post.name/s');
        $course_id = I('post.course_id/s');
        $content = I('post.content/s');

        if ($user_name == '' || $course_id == '' || $content == '') {
            return $this->ajaxReturn($this->return[412]);
        }

        $RelUserStuid = M("RelUserStuid");

        $userStuid = $RelUserStuid->where(['name' => $user_name])->limit(1)->find();

        $stuid = $userStuid['stuid'];

        if(!$stuid) {
            return $this->ajaxReturn($this->return[402]);
        }

        $RelStuidCourse = M("RelStuidCourse");
        $canComment = $RelStuidCourse->where(['stuid' => $stuid, 'course_id' => $course_id])->find();

        if(!$canComment) {
            return $this->ajaxReturn($this->return[401]);
        }

        try {
            $Comment->create([
                'id' => NULL, 
                'content' => $content, 
                'comment_user' => $user_name, 
                'course_id' => $course_id
            ]);
            $Comment->add();
            return $this->ajaxReturn($this->return[200]);
        } catch (Exception $e) {
            return $this->ajaxReturn(($this->return[500]));
        }
    }

    public function getHotCourse() {
        $Model = new \Think\Model();
        $sql = <<<EOF
            SELECT course.course_name, course.id, course.teacher FROM course LEFT JOIN
            (SELECT course_id as b, COUNT(course_id) as a
            FROM `comment` 
            where course_id in
            (SELECT DISTINCT course_id AS a FROM `comment`) 
            GROUP BY course_id) as a  ON course.id = a.a LIMIT 10;
EOF;
        $hotCourseList = $Model->query($sql);
        $this->ajaxReturn($hotCourseList);
    }
    public function search() {
        $searchKey = I('GET.searchKey');

        $Model = new \Think\Model();

        $result = $Model->query("select * from course where teacher like '%${searchKey}%' or course_name like '%${searchKey}%' ");

        $this->ajaxReturn($result);
    }

    
}














