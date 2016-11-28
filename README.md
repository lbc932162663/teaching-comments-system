# 这是一些辣鸡代码



## 课程点评系统



## 基于 thinkphp



## 下面是文档


格式: 
​    请求方法: 路由
​    字段: 字段类型



### 注册接口

+ request

  ```js
  {
      "POST": "/api/register",
      "field": {
        "name": String,
        "pwd": String
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

  ```
  {
    "POST": '/api/login',
    "field": {
      "name": String,
      "pwd": String
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