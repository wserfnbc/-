# 宠物网站项目

基于 ThinkAdmin 框架开发的宠物管理网站系统。

## 📋 项目简介

这是一个完整的宠物网站管理系统，提供宠物信息管理、用户管理、订单处理等功能。适合大学生毕设以及二次开发

## 🛠️ 技术栈

- **框架**: ThinkAdmin (基于 ThinkPHP)
- **语言**: PHP 7.4+
- **数据库**: MySQL 5.7+
- **前端**: HTML5 + CSS3 + JavaScript
- **依赖管理**: Composer

## 📦 项目结构

```
├── app/              # 应用代码
├── config/           # 配置文件
│   ├── app.php       # 应用配置
│   ├── database.php  # 数据库配置
│   └── ...
├── public/           # 静态资源和入口
│   ├── static/       # CSS/JS/图片
│   └── index.php     # 应用入口
├── runtime/          # 运行时文件（日志、缓存）
├── vendor/           # Composer 依赖
├── .gitignore        # Git 忽略规则
├── .htaccess         # Apache 重写规则
└── index.php         # 主入口文件
```

## 🚀 快速开始

### 环境要求

- PHP >= 7.4
- MySQL >= 5.7
- Apache/Nginx (支持 URL 重写)
- Composer

### 安装步骤

1. **克隆项目**
```bash
git clone https://github.com/wserfnbc/-1.git
cd -1
```

2. **安装依赖**
```bash
composer install
```

3. **配置数据库**

编辑 `config/database.php` 文件：
```php
'hostname' => 'localhost',      // 数据库地址
'database' => '你的数据库名',
'username' => '你的用户名',
'password' => '你的密码',
```

4. **导入数据库**

导入项目提供的 SQL 文件到你的数据库。

5. **设置目录权限**
```bash
chmod -R 755 runtime
chmod -R 755 public/uploads
```

6. **配置 Web 服务器**

**Apache**: 确保启用 `mod_rewrite` 模块

**Nginx**: 添加以下配置
```nginx
location / {
    if (!-e $request_filename){
        rewrite ^(.*)$ /index.php?s=$1 last;
    }
}
```

7. **访问网站**

浏览器访问你配置的域名或 `http://localhost`

### 默认账号

- 超级管理员: `admin`
- 密码: 请查看系统配置或联系管理员

## 📝 主要功能

- 🐕 宠物信息管理
- 👥 用户管理
- 📦 订单管理
- 💰 支付集成（微信支付、支付宝）
- 📊 数据统计
- 🔐 权限管理

## ⚙️ 配置说明

### 应用配置

编辑 `config/app.php`:
- `super_user`: 超级管理员账号
- `default_timezone`: 时区设置（默认 Asia/Shanghai）

### 数据库配置

编辑 `config/database.php` 修改数据库连接信息。

## 📄 部署说明

### 生产环境部署

1. 将代码上传到服务器
2. 安装 PHP 依赖：`composer install --no-dev`
3. 配置数据库连接
4. 设置正确的目录权限
5. 配置 Web 服务器（Apache/Nginx）
6. 清理 runtime 缓存：`php think clear`

### 注意事项

⚠️ **安全提示**:
- 生产环境请修改 `config/database.php` 中的数据库密码
- 确保 `runtime/` 目录有写入权限
- 不要将 `.env` 或包含敏感信息的配置文件提交到公共仓库

## 🔧 常见问题

### 1. 页面 404 错误
检查 Web 服务器是否正确配置了 URL 重写。

### 2. 权限错误
确保 `runtime/` 和 `public/uploads/` 目录对 Web 服务器用户有写权限。

### 3. Composer 依赖安装失败
尝试使用国内镜像：
```bash
composer config repo.packagist composer https://mirrors.aliyun.com/composer/
```

## 📞 联系方式

- GitHub: [@wserfnbc](https://github.com/wserfnbc)


## 📜 开源协议

本项目基于 ThinkAdmin 开源框架开发，遵循 [MIT License](https://mit-license.org)。

---

**⭐ 如果这个项目对你有帮助，请给个 Star！**
