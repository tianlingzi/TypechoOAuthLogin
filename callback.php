<?php if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}?>
<!DOCTYPE HTML>
<html lang="zh-CN">
<head>
    <meta charset="<?php $this->options->charset(); ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge, chrome=1">
    <meta name="renderer" content="webkit">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title><?php _e('完善帐号信息 - '); $this->options->title(); ?></title>

    <link rel="shortcut icon" href="/favicon.ico" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- 使用CDN引入Bootstrap CSS -->
    <link rel="stylesheet" href="./usr/plugins/TypechoOAuthLogin/else/bootstrap.min.css">
    <style type="text/css">
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background-color: #f0f4f8;
            background-image: url('https://bing.img.run/1920x1080.php'); /* Bing每日壁纸 */
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            backdrop-filter: blur(5px);
        }
        
        .container {
            width: 100%;
            max-width: 500px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }
        
        .container:hover {
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.2);
        }
        
        .login-section h1 {
            text-align: center;
            color: #2d3748;
            margin-bottom: 12px;
            font-size: 28px;
            font-weight: 700;
        }
        
        .login-section p {
            text-align: center;
            color: #718096;
            margin-bottom: 35px;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .tabs {
            display: flex;
            background: #f7fafc;
            border-radius: 12px;
            padding: 4px;
            margin-bottom: 30px;
            gap: 4px;
        }
        
        .tabs .tablinks {
            flex: 1;
            padding: 14px 20px;
            text-align: center;
            background: transparent;
            color: #4a5568;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
            font-weight: 500;
        }
        
        .tabs .tablinks:hover {
            background: #edf2f7;
            color: #2d3748;
        }
        
        .tabs .tablinks.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        
        .tabcontent {
            display: none;
            animation: fadeIn 0.3s ease-in;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .form-group {
            margin-bottom: 24px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #4a5568;
            font-size: 13px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s ease;
            background: #fff;
            color: #2d3748;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-group input:hover {
            border-color: #cbd5e0;
        }
        
        button {
            width: 100%;
            padding: 16px;
            border: none;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 15px;
            font-weight: 600;
            font-family: inherit;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        @media (max-width: 768px) {
            body {
                padding: 15px;
            }
            
            .container {
                padding: 30px 24px;
                margin: 0;
            }
            
            .login-section h1 {
                font-size: 24px;
            }
            
            .form-group {
                margin-bottom: 20px;
            }
            
            .tabs .tablinks {
                padding: 12px 16px;
                font-size: 13px;
            }
        }
        
        /* 错误信息样式 */
        .error-message {
            color: #ef4444;
            font-size: 12px;
            margin-top: 6px;
            min-height: 16px;
            transition: opacity 0.3s ease;
        }
        
        /* 输入错误状态 */
        .form-group input.error {
            border-color: #ef4444;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
        }
        
        /* 添加一些额外的视觉效果 */
        .container::before {
            content: '';
            position: absolute;
            top: -1px;
            left: -1px;
            right: -1px;
            bottom: -1px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            z-index: -1;
            opacity: 0.2;
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
                <form action="" method="POST" onsubmit="return validateRegForm()">
                    <?php $security = $this->widget('Widget_Security'); ?>
                    <input type="hidden" name="_security" value="<?php echo $security->getToken($this->request->getRequestUrl()); ?>">
                    <div class="form-group">
                        <label for="screenName" class="required">用户名</label>
                        <input type="text" id="screenName" name="screenName" value="<?php if (isset($this->auth['nickname'])){ echo $this->auth['nickname'];}?>" required>
                        <div class="error-message" id="screenNameError"></div>
                    </div>
                    <div class="form-group">
                        <label for="mail" class="required">邮箱</label>
                        <input type="email" id="mail" name="mail" required>
                        <div class="error-message" id="mailError"></div>
                    </div>
                    <div class="form-group">
                        <label for="password" class="required">密码</label>
                        <input type="password" id="password" name="password" required oninput="validatePassword()">
                        <div class="error-message" id="passwordError"></div>
                    </div>
                    <div class="form-group">
                        <label for="confirmPassword" class="required">确认密码</label>
                        <input type="password" id="confirmPassword" name="confirmPassword" required oninput="validatePassword()">
                        <div class="error-message" id="confirmPasswordError"></div>
                    </div>
                    <button type="submit" name="do" value="reg">确定</button>
                </form>
            </div>
            
            <div id="tab2" class="tabcontent">
                <form action="" method="POST">
                    <?php $security = $this->widget('Widget_Security'); ?>
                    <input type="hidden" name="_security" value="<?php echo $security->getToken($this->request->getRequestUrl()); ?>">
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

// 密码验证函数
function validatePassword() {
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirmPassword');
    const passwordError = document.getElementById('passwordError');
    const confirmPasswordError = document.getElementById('confirmPasswordError');
    
    let isValid = true;
    
    // 重置错误状态
    password.classList.remove('error');
    confirmPassword.classList.remove('error');
    passwordError.textContent = '';
    confirmPasswordError.textContent = '';
    
    // 验证密码长度
    if (password.value.length > 0) {
        if (password.value.length < 6) {
            password.classList.add('error');
            passwordError.textContent = '密码长度不能少于6个字符';
            isValid = false;
        }
    }
    
    // 验证两次密码是否一致
    if (confirmPassword.value.length > 0) {
        if (password.value !== confirmPassword.value) {
            confirmPassword.classList.add('error');
            confirmPasswordError.textContent = '两次输入的密码不一致';
            isValid = false;
        }
    }
    
    return isValid;
}

// 表单提交验证
function validateRegForm() {
    const isValid = validatePassword();
    
    // 如果验证失败，阻止表单提交
    if (!isValid) {
        // 滚动到第一个错误位置
        const firstError = document.querySelector('.error');
        if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        return false;
    }
    
    return true;
}

// 默认打开第一个tab
document.getElementsByClassName('tablinks')[0].click();
</script>
</body>
</html>