# 这是一些辣鸡代码



## 课程点评系统



## 基于 thinkphp



## 下面是文档


格式: 
​    请求方法: 路由
​    字段: 字段类型



**以下接口均基于 tcs.todoit.me**

**例如注册接口 **```tcs.todoit.me/api/register```



### 注册接口

+ request

  ```js
  {
      "POST": "/api/register",
      "field": {
        "name": String, // 用户名
        "pwd": String // 密码
      }
  }
  ```



+ response

  ```javascript
  // 成功
  {
    "status": 200,
    "info": "success"
  }
  // 参数不足
  {
      "status": 412,
      "info": "lack of parameter"
  }
  // 用户名已经被使用
  {
      "status": 419,
      "info": "name used"
  }
  // 服务器错误
  {
      "status": 500,
      "info": "server error"
  }
  ```

  ​

### 注册接口

+ request

  ```JavaScript
  {
    "POST": '/api/login',
    "field": {
      "name": String, // 用户名
      "pwd": String  // 密码
    }
  }
  ```

+ response

  ```javascript
  // 成功
  {
    "status": 200,
    "info": "success"
  }
  // 验证失败
  {
      "status": 401,
      "info": "authorized failed"
  }
  // 参数不足
  {
      "status": 412,
      "info": "lack of parameter"
  }
  // 服务器错误
  {
      "status": 500,
      "info": "server error"
  }
  ```

  ​

### 绑定学号

+ request

  ```javascript
  {
    "POST": '/api/bindstuid',
    "field": {
      "stuid": String, // 学号
      "name": String // 用户名
    }
  }
  ```

+ response

  ```javascript
  // 成功
  {
    "status": 200,
    "info": "success"
  }

  // 参数不足
  {
      "status": 412,
      "info": "lack of parameter"
  }

  // 服务器错误
  {
      "status": 500,
      "info": "server error"
  }
  ```

### 查询学生可以点评的课程

+ request

  ```JavaScript
  {
    "GET": '/api/stucourse',
    "feild": {
    	"stuid": String // 学号
    }
  }
  ```

+ response

  ```javascript
  //成功
  {
    "status": 200,
    "success": "success",
    "data": [
      {
        "id": "48",
        "course_num": "011305",
        "teacher": "景小荣",
        "course_name": "通信原理A",
        "stuid": "2014210014",
        "course_id": "48"
      },
      {
        "id": "49",
        "course_num": "010603",
        "teacher": "唐宏",
        "course_name": "电信传输理论与工程",
        "stuid": "2014210014",
        "course_id": "49"
      }
    ]
  }

  // 参数不足
  {
      "status": 412,
      "info": "lack of parameter"
  }

  // 没有绑定学号
  {
  	'status': 401,
       'info': 'authorized failed'  
  }

  // 服务器错误
  {
      "status": 500,
      "info": "server error"
  }
  ```

### 发送评论接口

+ request

  ```JavaScript
  {
    "POST": "/api/comment",
    "field": {
      "name": String, // 用户名,
      "id": Integer, // 课程 id
      "content": String // 评论内容
    }
  }
  ```

+ response

  ```javascript
  // 成功
  {
    "status": 200,
    "info": 'success'
  }

  // 参数不足
  {
      "status": 412,
      "info": "lack of parameter"
  }

  // 没有绑定学号
  {
  	'status': 402,
      'info': 'lack of authorized'
  }


  // 该学生不能评论这个课程
  {
  	'status': 401,
       'info': 'authorized failed'  
  }

  // 服务器错误
  {
      "status": 500,
      "info": "server error"
  }
  ```

### 获取评论接口

+ request

  ```JavaScript
  {
    "GET": "/api/comment",
    "field": {
      "course_id": Integer // 课程 id,
    }
  }
  ```

+ response

  ```javascript
  // 成功
  {
    "status": 200,
    "info": "success",
    "data": [
      {
        "id": "1",
        "content": "景小荣老湿很强",
        "course_id": "100",
        "comment_user": "ming"
      },
      {
        "id": "2",
        "content": "景小荣老湿很强",
        "course_id": "100",
        "comment_user": "ming"
      },
      {
        "id": "3",
        "content": "200",
        "course_id": "100",
        "comment_user": "ming"
      },
      {
        "id": "4",
        "content": "200",
        "course_id": "100",
        "comment_user": "ming"
      }
    ]
  }


  // 服务器错误
  {
      "status": 500,
      "info": "server error"
  }
  ```

  ​