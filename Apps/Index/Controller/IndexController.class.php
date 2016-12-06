<?php


namespace Index\Controller;
require 'vendor/autoload.php';
use Think\Controller;

class IndexController extends Controller {
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
    protected function getHotCourse() {
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
        return $hotCourseList;
    }
    protected function searchCourse($searchKey) {

        $Model = new \Think\Model();

        $result = $Model->query("select * from course where teacher like '%${searchKey}%' or course_name like '%${searchKey}%' ");

        return $result;
    }
    protected function addRelStuidName($name, $stuid) {
        $RelUserStuid = M('RelUserStuid');
        $RelUserStuid->create(['name' => $name, 'stuid' => $stuid]);
        $isSuccess = $RelUserStuid->add();
        return $isSuccess;
    }
    public function index(){
        $hot = $this->getHotCourse();
        $this->assign('hot', $hot);

        $name = session('username');
        // $name = 'ming';
        if($name == NULL) {
            return $this->display('index');
        }

        $RelUserStuid = M('RelUserStuid');
        $stuid = $RelUserStuid->where(['name' => $name])->find();
        $stuCourse = $this->getStuCourse($stuid);

        $this->assign('stuCourse', $stuCourse);

        return $this->display('index');
    }
    public function login() {
        if(IS_GET) {
            return $this->display('login');
        }

        $userInfo = [
            'name' => I('post.name/s'),
            'pwd' => I('post.pwd/s')
        ];
        if($userInfo['name'] == '' || $userInfo['pwd'] == '') {
            return $this->success('请输入用户名和密码');
        }
        $userInfo['pwd'] = md5($userInfo['pwd']);

        $User = D('User');

        $isSuccess = $User->where($userInfo)->find();

        if($isSuccess) {
            session('username', $userInfo['name']);

            $this->success('登录成功', 'Index/index');
        } else {

            $this->success('用户名或密码错误');
        }


    }

    public function signup() {
        if(IS_GET) {
            return $this->display('signup');
        }
        $name = I('POST.name/s');
        $pwd = I('POST.pwd/s');
        $stuid = I('POST.stuid/s');

        if($name == '' || $pwd == '') {
            $this->success('请输入用户名和密码');
        }

        if(strlen($pwd) < 6) {
            return $this->success('密码不得少于6位');
        }

        $User = D('User');

        $isExist = !!$User->where(['name'=>$name])->find();
        
        if($isExist) {
            return $this->success('改用户名已经被注册了~~');
        }

        $User->create(['name' => $name, 'pwd' => $pwd]);
        $User->add();

        $isSuccess = $User->add();

        if($stuid != '') {
            $this->addRelStuidName($name, $stuid);
        }
        session('username', $name);
        session('stuid', $stuid);

        $this->success('注册成功', 'Index/index');


    }
    public function search() {
        $searchKey = I("POST.searchKey/s");
        $result = $this->searchCourse($searchKey);

        $this->assign('course', $result);
        $this->display('search');
    }












}