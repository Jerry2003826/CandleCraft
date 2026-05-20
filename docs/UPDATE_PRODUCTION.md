# 在 cPanel 服务器上更新到最新版本

> 适用对象：本项目已经部署在 cPanel + Apache + MariaDB 的生产服务器
> （比如 `https://u26s1185.iedev.org/production/`），现在想把 GitLab `main`
> 分支上的最新提交同步过去。

---

## 一行命令更新（推荐）

SSH 进 cPanel，进入 app 根目录后运行：

```bash
cd ~/public_html/production            # 改成你实际的 app 目录
bash scripts/cpanel-update.sh
```

脚本会按顺序做完下面 7 件事，每一步都是**幂等**的（重复执行结果一致，不会破坏任何东西）：

| # | 步骤 | 说明 |
|---|---|---|
| 1 | `git fetch + git pull --ff-only origin main` | 如果本地有未提交修改会自动 stash，便于事后恢复 |
| 2 | `composer install --no-dev --optimize-autoloader` | 仅当 `composer.json` / `composer.lock` 有变化才跑 |
| 3 | `mkdir -p webroot/uploads/site/` | CMS 上传图片的目录，权限 755 |
| 4 | `mysql < docs/sql/cms-bootstrap.sql` | 仅当传了 DB 凭据才跑；脚本本身可重复运行 |
| 5 | `bin/cake migrations migrate` | 跑任何还没 apply 的迁移 |
| 6 | `bin/cake cache clear_all` | 让路由、CMS bundle 缓存等失效 |
| 7 | HTTP 健康检查 | 仅当传了 `APP_URL` 才跑 |

---

## 第一次需要带 DB 凭据（只此一次）

服务器上以前没 CMS 的那 5 张表 / `messages` 表也缺了 `parent_message_id` 等列。第一次更新时把数据库凭据传进去，脚本就会顺手把 `docs/sql/cms-bootstrap.sql` 也跑了：

```bash
DB_NAME=academy_management_db \
DB_USER=academy_user \
DB_PASS='你的密码' \
APP_URL='https://u26s1185.iedev.org/production' \
bash scripts/cpanel-update.sh
```

之后日常更新就只需要：

```bash
bash scripts/cpanel-update.sh
```

---

## 常用环境变量

| 变量 | 默认 | 说明 |
|---|---|---|
| `APP_DIR` | `$(pwd)` | app 根目录路径 |
| `GIT_BRANCH` | `main` | 要拉的分支 |
| `PHP_BIN` | 自动检测（`php`、`ea-php82` 等） | 强制指定 PHP 二进制路径 |
| `COMPOSER_BIN` | 自动检测 | 强制指定 composer 二进制 |
| `MYSQL_BIN` | 自动检测 | 强制指定 mysql 客户端 |
| `DB_NAME` / `DB_USER` / `DB_PASS` | （空） | 用于 cms-bootstrap.sql；不传就跳过 |
| `APP_URL` | （空） | 用于最后一步健康检查 |
| `SKIP_GIT_PULL=true` | 否 | 上传 zip 时不需要 git pull |
| `SKIP_COMPOSER=true` | 否 | 已知没有依赖变化时跳过 |
| `SKIP_BOOTSTRAP_SQL=true` | 否 | 已经跑过一次了 |
| `SKIP_MIGRATIONS=true` | 否 | 已知没有新迁移 |
| `SKIP_CACHE_CLEAR=true` | 否 | 一般不要跳 |

---

## cPanel 上没有 SSH 怎么办

如果你的主机面板只能用 cPanel 的 File Manager + phpMyAdmin，按下面 3 步手动做：

1. **打 zip 上传**：在本地 `git archive --format=zip --output=update.zip main` 打包，cPanel File Manager 上传到 app 目录覆盖（推荐先备份原目录）。
2. **跑 SQL**：cPanel → phpMyAdmin → 选中 `academy_management_db` → SQL → 粘贴 `docs/sql/cms-bootstrap.sql` 全文 → Go。
3. **清缓存**：直接删 `tmp/cache/` 下所有内容（File Manager → 多选 → 删除）即可，等同于 `bin/cake cache clear_all`。

> 注意：上传 zip 不会自动跑 `composer install`。如果这次更新动了 `composer.json`，必须用 SSH 走第一种方式，或在控制面板里用 Terminal 应用打开 SSH。

---

## 上线后自检 checklist

```bash
# 在 SSH / cPanel Terminal 里
git log -1 --format='%h %s'                                # 当前 HEAD
bin/cake migrations status | tail -10                      # migration 全部 up
mysql -u USER -p DB -e "SELECT COUNT(*) FROM site_pages;"  # = 4
mysql -u USER -p DB -e "SELECT COUNT(*) FROM page_sections;" # = 20
mysql -u USER -p DB -e "SHOW COLUMNS FROM messages LIKE 'parent_message_id';" # 有
ls -la webroot/uploads/site/                              # 目录存在
```

浏览器里再点几个关键路径：

| 路径 | 期望 |
|---|---|
| `/` | 200，CANDLECRAFT 首页正常显示 |
| `/login` | 200，"USER LOGIN PAGE" 在手机上不再折 3 行 |
| `/admin/cms` | 登录后能看到 4 个页面 + Site Content 入口 |
| `/admin/messages/view/<id>?return_url=%2Fadmin%2Fmessages` | 进 view 后点 Back 不再 404 |
| `/consumer/payments/success/<stripe-cs-id>` | 重定向到 `/consumer/bookings`，不再 403 |

---

## 如果出错怎么回滚

脚本 stash 了你的本地改动 + 没改 `.env` / 数据，所以最坏情况下可以一键回到上个发布：

```bash
# 回到拉之前的 commit
git reset --hard ORIG_HEAD     # 或者具体的 commit sha
bin/cake cache clear_all
```

数据库部分 `cms-bootstrap.sql` 全部用 `CREATE TABLE IF NOT EXISTS` + `INSERT IGNORE`，不会污染老数据，回滚不需要动 DB；除非你想彻底删除 CMS：

```sql
DROP TABLE IF EXISTS page_section_locks;
DROP TABLE IF EXISTS page_section_revisions;
DROP TABLE IF EXISTS page_sections;
DROP TABLE IF EXISTS site_media;
DROP TABLE IF EXISTS site_pages;
DELETE FROM cake_migrations WHERE version IN (20260511020000, 20260511020100);
```
