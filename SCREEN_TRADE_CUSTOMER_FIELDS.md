# Screen Trade CRM：客户字段（第一阶段）

本阶段沿用 Krayin 的联系人、公司、自定义属性、销售负责人和权限系统，不创建新的客户表，也不集成 WhatsApp 消息发送。

## 字段与存储

| 业务字段 | 实现 | 说明 |
| --- | --- | --- |
| 国家和地区 | `country_id`、`region` 自定义属性 | 国家引用原有 `countries`；地区/城市为可选文本。表示联系人服务市场，不复制公司的完整地址。 |
| 公司名称 | 原有 `organization_id` / `organization_name` | 使用原有公司选择和新建流程。 |
| 客户类型 | `customer_type` 自定义属性 | 批发商、维修店、进口商、分销商。 |
| WhatsApp | 原有 `contact_numbers` | 号码标签选择 `whatsapp`，不额外复制号码。 |
| 邮箱 | 原有 `emails` | 沿用原有多邮箱与唯一性检查。 |
| 主要采购产品 | `primary_products` 自定义属性 | 最多 1000 字符的产品需求描述；本阶段没有 SKU 多对多关联。 |
| 客户来源 | `customer_source_id` 自定义属性 | 引用原有 `lead_sources`，可在设置中的线索来源管理选项；客户来源与单个采购机会来源可不同。 |
| 客户等级 | `customer_grade` 自定义属性 | A、B、C；未填写表示未评级，不自动评分。 |
| 最近跟进时间 | `last_followed_up_at` 自定义属性 | 手动维护，格式 `YYYY-MM-DD HH:mm:ss`，不可晚于现在，使用应用 `config('app.timezone')` 时区。 |
| 客户备注 | `customer_notes` 自定义属性 | 可选纯文本，最多 5000 字符。 |

新属性均可选，存入现有 `attributes`、`attribute_options`、`attribute_values`。没有新增表、核心表列或依赖版本。

新建、编辑和详情使用原有动态属性组件。WhatsApp 号码要求国际格式，例如 `+2348012345678`（8–15 位数字，含国家码）；原有 work/home 标签保留。部分字段更新保留原负责人、邮箱、公司、电话号码和去重标识，未提交的扩展字段不被清空。空号码通过扩展 Repository 安全清除，同时清除其 EAV 值。

新增请求验证只作用于联系人保存路由，其余 AttributeForm 使用原验证规则。继续使用原有创建/编辑权限，并检查联系人负责人数据范围及负责人分配范围。其他 CRM 模块、导入和 WebForm 不在本阶段新增验证范围内。

## 迁移、重复安装和回滚

数据迁移：

`packages/Webkul/ScreenTrade/src/Database/Migrations/2026_10_09_000001_add_screen_trade_customer_attributes.php`

迁移只添加缺失的属性和选项，不更新已有定义或客户值。同名属性如类型或 lookup 不兼容，整个操作回滚并提示人工审核，不静默覆盖。

`php artisan screen-trade:install-customer-attributes` 可重复运行，补齐缺失定义。它不会重建数据库或重置客户数据。

新安装时，扩展 AttributeSeeder 先执行原 seeder，再添加业务属性，避免 Krayin 原 seeder 删除属性后丢失新字段。不要在已有客户库上重新运行原安装器或原 AttributeSeeder；原有初始化流程具有破坏性。

迁移的 `down()` 故意保留定义和业务值，不自动删除可能已被使用或预先存在的自定义字段。代码回退不等于数据删除。若需要移除字段，应另行评估引用与数据备份。

字段名称和选项按安装时的应用语言写入，与原自定义属性机制一致。中文和英文文案已提供；其他现有语言使用英文回退。切换界面语言不会自动重命名已有数据库属性。

## Windows 本地安装

建议使用 PHP 8.3（或兼容的 8.4）、Composer 2、Node.js 22，以及 MySQL >= 8.0.32 或 MariaDB >= 11.4。可使用 Laravel Herd 或满足版本要求的本地 PHP 环境。

至少准备 PHP 扩展：calendar、curl、dom、fileinfo、gd、intl、mbstring、openssl、pdo_mysql、phar、simplexml、tokenizer、xml、xmlreader、xmlwriter、zip。以 `composer check-platform-reqs` 的结果为准。

在 PowerShell 中进入项目根目录：

```powershell
git switch feature/screen-trade-customer-fields
composer install --no-interaction --prefer-dist
composer check-platform-reqs
```

**已有本地 Krayin 安装：**保留 `.env` 和 `APP_KEY`，确认数据库仅为本地开发库。备份数据库、检查同名自定义字段后，执行本次迁移：

```powershell
php artisan migrate --path=packages/Webkul/ScreenTrade/src/Database/Migrations/2026_10_09_000001_add_screen_trade_customer_attributes.php
php artisan optimize:clear
php artisan screen-trade:install-customer-attributes
```

**全新本地安装：**仅在新建空数据库上使用 Krayin 安装器。没有 `.env` 时复制 `.env.example`，设置本地 DB 参数、`APP_URL` 和 `APP_LOCALE`，然后执行：

```powershell
Copy-Item .env.example .env
php artisan krayin-crm:install
```

安装器会执行 `migrate:fresh`，清空目标库；绝不能用于已有客户库。完成后运行 `php artisan screen-trade:install-customer-attributes` 验证定义可重复安装。不要把默认测试管理员密码用于真实部署。

后台资源需要重建时，在单独 PowerShell 窗口执行：

```powershell
Set-Location packages/Webkul/Admin
npm install --package-lock=false
npm run build
Set-Location ../../..
php artisan serve --host=127.0.0.1 --port=8000
```

仓库未跟踪 npm 锁文件，因此此处不使用 `npm ci`。本次没有更改前端 JS/CSS；现有已构建资源可复用。

## 自动化测试

创建独立空数据库，例如 `screen_trade_test`。准备 `.env.testing`，配置 `APP_ENV=testing`、专用测试数据库、有效应用密钥，并使用 array 邮件、缓存、会话驱动。禁止连接业务库。

测试沿用项目的 DatabaseTransactions 模式，依赖已经初始化的测试数据库。本文件的 seeder 命令仅可对独立测试库执行：

```powershell
php artisan migrate --env=testing
php artisan db:seed --env=testing --class="Webkul\Installer\Database\Seeders\DatabaseSeeder"
php artisan test --compact tests/Feature/ScreenTrade/CustomerAttributesTest.php
php vendor/bin/pint --test packages/Webkul/ScreenTrade tests/Feature/ScreenTrade bootstrap/providers.php packages/Webkul/Admin/src/Resources/lang
bash bin/validate-skills.sh
```

最后一项可在 Git Bash 中执行。

覆盖：完整字段保存、旧表单兼容、邮箱验证、号码唯一性及国际格式、空号码、字段长度、国家/来源引用、选项归属、跟进时间、重复安装、原安装 seeder 兼容、部分更新、字段清空、销售员权限、原产品表单、迁移保留数据以及不兼容字段冲突。

## 人工验收

1. 登录后台，进入联系人新建页面，使用原有公司和邮箱控件。
2. 选择国家、地区、客户类型、采购产品、客户来源和等级，填写最近跟进时间与备注。
3. 在原联系电话控件选择 WhatsApp 标签，输入国际号码，保存后重新打开编辑和详情页面核对。
4. 输入无国家码的 WhatsApp 号码、超长备注、未来跟进时间，确认保存被拒绝。
5. 使用只有查看权限的账号，以及个人数据范围的其他销售员账号，确认无法修改该客户。
6. 创建不填写新业务字段的普通联系人，并验证原有客户、产品、报价和销售机会仍能使用。

## 范围与限制

最近跟进时间为手动录入；主要采购产品为文本；客户等级没有 AI 评分；没有 WhatsApp 发送/同步。现有联系人导入、公开 WebForm 的扩展字段校验及前端浏览器 E2E 不在本阶段实现范围内。

## 本次云环境验证记录

- PHP 8.4.26、Composer 2.9.2、MariaDB 11.8.6；测试数据库位于本机临时目录，仅通过 Unix socket 访问。未连接生产数据库。
- 新增客户字段测试：33 项通过，102 个断言。
- 全量测试：120 项通过、6 项失败；禁用新模块后，这 6 项在原安全测试中仍可复现（对应对照运行 16 项通过、6 项失败）。失败涉及登录/找回密码限流响应、邮件对象权限响应和报价产品权限响应；本次未修改这些模块。
- Composer 平台依赖检查、Pint 和技能一致性检查通过。
- 验证了原创建/编辑页面的 HTTP 响应及字段渲染，未执行完整 Playwright 浏览器 E2E，也未在 Windows 上实际运行。
