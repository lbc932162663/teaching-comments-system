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

        var_dump($data);

        if($data->status != 200) {
            return [];
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
SELECT course_id , COUNT(course_id) as comment_num
FROM `comment` 
where course_id in
(SELECT DISTINCT course_id  FROM `comment` AS comment_course_id) 
GROUP BY course_id limit 10;
EOF;
        $ids = $Model->query($sql);
        $hotCourseList = [];
        foreach($ids as $id) {
            $hotCourseList[] = M('Course')->where('id=%d', $id)->find();
        }
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

        $name = session('name');
        // $name = 'ming';
        if($name == NULL) {
            return $this->display('index');
        }

        $RelUserStuid = M('RelUserStuid');
        $stuid = $RelUserStuid->where(['name' => $name])->find();
        $stuCourse = $this->getStuCourse($stuid['stuid']);

        var_dump($stuid);

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
            session('name', $userInfo['name']);

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
        session('name', $name);
        session('stuid', $stuid);

        $this->success('注册成功', 'Index/index');


    }
    public function search() {
        $searchKey = I("POST.searchKey/s");
        $result = $this->searchCourse($searchKey);

        $this->assign('course', $result);
        $this->display('search');
    }



    public function comment($course_id) {
        $course_id = I('get.course_id/i');
        $Comment = M('Comment');

        if(IS_GET) {
            $Course = M('Course');
            $course = $Course->where('id = %s', $course_id)->find();
            $this->assign('course',$course);
            $comments = $Comment->where(['course_id' => $course_id])->select();
            $this->assign('comments', $comments);
            return $this->display('comment');
        }

        $name = session('name');
        $content = I('POST.content/s');
        $course_id = I('POST.course_id/s');
        
        if($content == '') {
            return $this->success('不要交空的评论啊~~');
        }
        if($name == NULL) {
            return $this->success('请先登录', 'Index/login');
        }
        $Comment->create([
            'id' => NULL, 
            'content' => $content, 
            'comment_user' => $name, 
            'course_id' => $course_id
        ]);
        $Comment->add();

        $this->success('评论成功');
        // $this->redirect(U('Index/comment?course_id='.$course_id),3,'评论成功');
    }



    public function bindStuid() {
        $name = session('name');
        $stuid = I("post.stuid/s");

        if($name == '' || stuid == '') {
            $this->success('请先登录');
            return;
        }

        $RelUserStuid = M('RelUserStuid');

        $stuidOfName = !!$RelUserStuid->where(['name' => $name])->find();

        if($stuidOfName) {

            $RelUserStuid->where(['name' => $name])->save(['name' => $name, 'stuid' => $stuid]);
            $this->success('成功');
            return;
        }

        $RelUserStuid->create(['name' => $name, 'stuid' => $stuid]);
        $isSuccess = $RelUserStuid->add();

        if($isSuccess) {
            $this->success('成功');
        } else {
            $this->success('服务器错误');
        }
    }




}