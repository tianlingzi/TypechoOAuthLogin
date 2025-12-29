<?php if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}?>
<!DOCTYPE HTML>
<html class="no-js">
<head>
    <meta charset="<?php $this->options->charset(); ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge, chrome=1">
    <meta name="renderer" content="webkit">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title><?php _e('完善帐号信息 - '); $this->options->title(); ?></title>

    <link rel="shortcut icon" href="/favicon.ico" />

    <!-- 使用CDN引入Bootstrap CSS -->
    <link rel="stylesheet" href="./usr/plugins/TypechoOAuthLogin/else/bootstrap.min.css">
    <link rel="stylesheet" href="./usr/plugins/TypechoOAuthLogin/else/all.css">
    <style type="text/css">
        body {
            font-family: "Open Sans", "Helvetica Neue", Helvetica, Arial, sans-serif;
            background-color: #f7f9fc;
            background-image: url('./usr/plugins/TypechoOAuthLogin/else/background.png'); /* 替换为你的星空背景图片路径 */
            background-size: cover; /* 确保背景图片覆盖整个屏幕 */
            background-repeat: no-repeat; /* 防止背景图片重复 */
            background-attachment: fixed; /* 固定背景图片，使其不随页面滚动 */
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            border-radius: 8px;
            overflow: hidden;
            width: 100%;
            max-width: 500px;
            background-color: rgba(169, 199, 255, 0.7); /* 半透明的白色背景，提高可读性 */
            padding: 30px;
            border-radius: 15px; /* 增加圆角边框的半径 */
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.3); /* 添加阴影效果 */
        }
        .header {
            text-align: center;
            padding: 40px 20px 20px; /* Adjusted padding */
            color: #333;
        }
        .header p {
            font-size: 16px;
            color: #666;
            margin-bottom: 30px; /* Increased margin for balance */
        }
        .main-content {
            padding: 20px;
        }
        .login-section h1 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }
        .login-section p {
            text-align: center; /* 新增：使 <p> 标签中的文本居中对齐 */
        }
        .tabs {
            margin-bottom: 20px;
            list-style: none;
            padding: 0;
            display: flex;
            justify-content: space-around;
        }
        .tabs .tablinks {
            display: block;
            padding: 15px;
            text-align: center;
            background: #ddd;
            color: #333;
            border-radius: 10px;
            text-decoration: none;
            cursor: pointer;
            transition: background-color 0.3s;
            flex: 1;
        }
        .tabs .tablinks.active {
            background: #007bff;
            color: #fff;
        }
        .tabcontent {
            display: none;
            padding: 20px;
            border-top: 1px solid #e9ecef;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #e9ecef;
            border-radius: 5px;
        }
        button {
            width: 100%;
            padding: 10px;
            border: none;
            background-color: #007bff;
            color: white;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #0056b3;
        }
        @media (max-width: 768px) {
            .container {
                width: 90%;
            }
            .header h1 {
                font-size: 24px; /* Responsive font size */
            }
            .header p {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
<div class="container">

    <main class="main-content">
        <section class="login-section">
        <h1><a href="<?php $this->options->siteUrl(); ?>" style="text-decoration: none; color: #007bff;"><?php echo htmlspecialchars($this->options->title, ENT_QUOTES, 'UTF-8'); ?></a></h1>
        <p><?php $this->options->description();?></p>
            <div class="tabs">
                <a href="javascript:void(0)" class="tablinks active" onclick="openTab(event, 'tab1')">绑定新账号</a>
                <a href="javascript:void(0)" class="tablinks" onclick="openTab(event, 'tab2')">绑定已有账号</a>
            </div>
            
            <div id="tab1" class="tabcontent">
                <form action="" method="POST">
                    <div class="form-group">
                        <label for="screenName" class="required">昵称</label>
                        <input type="text" id="screenName" name="screenName" value="<?php if (isset($this->auth['nickname'])){ echo $this->auth['nickname'];}?>" required>
                    </div>
                    <div class="form-group">
                        <label for="mail" class="required">邮箱</label>
                        <input type="email" id="mail" name="mail" required>
                    </div>
                    <button type="submit" name="do" value="reg">确定</button>
                </form>
            </div>
            
            <div id="tab2" class="tabcontent">
                <form action="" method="POST">
                    <div class="form-group">
                        <label for="name" class="required">用户名</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="password" class="required">密码</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <button type="submit" name="do" value="bind">确定</button>
                </form>
            </div>
        </section>
    </main>
</div>

<script>
function openTab(evt, tabName) {
    var i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tabcontent");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }
    tablinks = document.getElementsByClassName("tablinks");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }
    document.getElementById(tabName).style.display = "block";
    evt.currentTarget.className += " active";
}
// 默认打开第一个tab
document.getElementsByClassName('tablinks')[0].click();
</script>
</body>
</html>