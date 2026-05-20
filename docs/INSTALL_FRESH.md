# 在空 cPanel 服务器上从零部署

> 适用对象：cPanel 账户全新，**还没有**部署过这个项目（数据库是空的，
> 没有 `config/.env`，`webroot/uploads/site/` 不存在），想一次把代码 +
> 数据 + 配置都搞起来。

---

## 一行命令完成（推荐）

SSH 进 cPanel，把代码上传到 app 目录（`git clone` 或 zip 解压都行），然后跑：

```bash
cd ~/public_html/production            # 改成你实际路径

APP_URL='https://u26s1185.iedev.org/production' \
DB_NAME=academy_management_db \
DB_USER=academy_user \
DB_PASS='你的数据库密码' \
SECURITY_SALT="$(openssl rand -hex 32)" \
bash scripts/cpanel-fresh-install.sh
```

脚本会按以下顺序做完，每一步都是**幂等**的（重复跑结果一致）：

| # | 步骤 | 说明 |
|---|---|---|
| 0 | Pre-flight | 检测 PHP / composer / mysql 路径 |
| 1 | `composer install --no-dev --optimize-autoloader` | 已存在 vendor/ 时自动跳过 |
| 2 | 生成 `config/.env` | 用你传入的环境变量；已存在时**不覆盖**（除非 `FORCE_OVERWRITE_ENV=true`） |
| 3 | 导入 `docs/sql/cms-fresh-install.sql` | 30 张表 + 2 个 view + 4 CMS 页面 + 20 区块 + 3 个 demo 账号 + 32 条 migration 记录 |
| 4 | `bin/cake migrations migrate` | 兜底，万一你的 checkout 比 SQL 快照新 |
| 5 | 创建 `webroot/uploads/site/` 目录 | CMS 图片上传位置 |
| 6 | `bin/cake cache clear_all` | 清缓存 |
| 7 | HTTP 健康检查 | 仅当传了 `APP_URL` |

---

## Demo 账号（首次登录后**必须改密**）

| 角色 | 邮箱 | 默认密码 |
|---|---|---|
| Admin | `admin@candlecraft.com` | `admin123` |
| Teacher | `emma.clay@candlecraft.com` | `alice123` |
| Student / Customer | `alice.wong@candlecraft.com` | `alice123` |

> 这些是 dev fixtures 默认密码，**绝对不能在生产留着**。第一次登录后立刻去 admin 面板把每个账号密码都重置。如果你完全不需要 demo 数据，编辑 `docs/sql/cms-fresh-install.sql`，把所有 `INSERT IGNORE INTO` 行删除，只留 schema。

---

## 数据库内容速览

执行完 fresh-install SQL 后，数据库里会有：

- **30 张表**：从 `users` / `students` / `teachers` / `courses` / `classes` 这套核心表，到 CMS 的 5 张表（`site_pages` / `site_media` / `page_sections` / `page_section_revisions` / `page_section_locks`），再到支付相关的 `payments` / `payment_refunds` / `payment_disputes` / `stripe_webhook_events`，以及 `cake_migrations` / `cake_seeds` 框架表。
- **2 个视图**：`vw_booking_details`、`vw_payment_summary`（只读，admin 报表用）。
- **CMS 默认数据**：4 个页面（`global` / `home` / `contact` / `courses`）+ 20 个 section（站名、Logo 占位、首页 Hero、联系页文案、课程分类描述等），admin 一登录就能开始改。
- **示范业务数据**：1 个 pottery 课程、1 个排课、1 笔 Alice 的 booking、1 笔 demo enquiry，让 admin 后台不是空白。
- **32 条 migration 记录**：后续 `bin/cake migrations migrate` 不会再重跑这些。

---

## 关键环境变量

| 变量 | 默认 | 必填？ | 说明 |
|---|---|---|---|
| `APP_DIR` | `$(pwd)` | 否 | app 根目录 |
| `APP_URL` | 空 | 推荐 | 用于健康检查 + 写入 `.env`（影响绝对 URL 生成） |
| `APP_BASE` | 空 | 否 | 子目录部署时填 `/production` |
| `DB_NAME` | 空 | **必填** | 数据库名 |
| `DB_USER` | 空 | **必填** | 数据库用户 |
| `DB_PASS` | 空 | 推荐 | 数据库密码 |
| `DB_HOST` / `DB_PORT` | `localhost` / `3306` | 否 | DB 主机和端口 |
| `SECURITY_SALT` | 自动生成 | 推荐 | Session/CSRF 加密 salt（长度 ≥ 32 字符） |
| `STRIPE_*` / `EMAIL_SMTP_*` / `RECAPTCHA_*` | 空 | 否 | 支付/邮件/验证码集成；可以留空，事后再编辑 .env |
| `FORCE_OVERWRITE_ENV=true` | false | 否 | 已存在 .env 时强制重写 |
| `SKIP_COMPOSER=true` | false | 否 | vendor 已经手动上传时 |
| `SKIP_DB_IMPORT=true` | false | 否 | 你想自己用 phpMyAdmin 导入 SQL |
| `SKIP_MIGRATIONS=true` | false | 否 | SQL 已经包含全部 migrations |

---

## 没 SSH 时的 cPanel 手工流程

当主机只给你 cPanel 控制台 + phpMyAdmin，按下面 4 步：

1. **上传代码**：本地打包 `git archive --format=zip --output=release.zip main`，cPanel File Manager 上传到 app 目录解压。
2. **建数据库**：cPanel → MySQL Databases → 新建 `academy_management_db` + 用户 + 给 ALL PRIVILEGES。
3. **导入 SQL**：phpMyAdmin → 选中 db → SQL → 粘贴 `docs/sql/cms-fresh-install.sql` 全文 → Go。导入完后跑下面这条验证：
   ```sql
   SELECT (SELECT COUNT(*) FROM users)        AS users,
          (SELECT COUNT(*) FROM site_pages)   AS pages,
          (SELECT COUNT(*) FROM page_sections) AS sections,
          (SELECT COUNT(*) FROM cake_migrations) AS migrations;
   ```
   预期：`users >= 3`、`pages = 4`、`sections >= 20`、`migrations >= 32`。
4. **手工写 .env**：用 File Manager 在 `config/` 下新建 `.env`，从 `config/.env.example` 复制内容并填好 DB_* / SECURITY_SALT 等。

> 不能跑 `composer install` 时，必须本地 `composer install --no-dev` 后把 `vendor/` 也一并打包上传。

---

## 上线后自检 checklist

```bash
# SSH / cPanel Terminal
git log -1 --format='%h %s'
bin/cake migrations status | tail -10
mysql -u USER -p DB -e "SELECT COUNT(*) AS pages FROM site_pages; SELECT COUNT(*) AS sections FROM page_sections;"
ls -la webroot/uploads/site/
```

浏览器再过一遍：

| 路径 | 期望 |
|---|---|
| `/` | 200，CANDLECRAFT 首页正常 |
| `/login` | 200，标题不再折 3 行 |
| 用 admin@candlecraft.com / admin123 登录 | 跳到 admin dashboard |
| `/admin/cms` | 列出 4 个页面（global / home / contact / courses） |
| `/admin/messages` | 列表显示 1 条 demo enquiry |
| `/admin/cms/pages/global` 改 Site Name → 保存 | 首页 brand mark 立即同步 |

完成上面 6 步代表「空服务器 → 完整可用站点」全流程跑通。

---

## 出错或想重来

```bash
# 想重置数据库 → 先 drop 再重导入
mysql -u USER -p -e "DROP DATABASE IF EXISTS academy_management_db; CREATE DATABASE academy_management_db DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
mysql -u USER -p academy_management_db < docs/sql/cms-fresh-install.sql

# 想重写 .env
FORCE_OVERWRITE_ENV=true bash scripts/cpanel-fresh-install.sh

# 想清空上传目录
rm -rf webroot/uploads/site/* && touch webroot/uploads/site/.gitkeep
```

---

## 之后日常更新（GitLab 有新提交时）

用另一个脚本：

```bash
bash scripts/cpanel-update.sh
```

详见 `docs/UPDATE_PRODUCTION.md`。
