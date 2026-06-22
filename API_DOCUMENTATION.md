# 三石记账系统 - API 接口文档

## 基础信息

- **基础 URL**: `https://YOUR_DOMAIN/public/api.php`
- **认证方式**: `Authorization: Bearer <token>`
- **所有请求**: `Content-Type: application/json`
- **响应格式**: JSON

---

## 认证方式

### ⭐推荐：API Token 认证（QClaw / 第三方应用专用）

所有API 请求通过 **Bearer Token** 进行身份认证。Token 在系统「设置→API Token 管理」中创建和查看。

**获取 Token**：
1. 登录三石记账网页端
2. 进入「设置」→「个人信息」→「API Token 管理」
3. 点击「创建Token」，输入用途描述（如 `QClaw`）
4. 点击「查看」，通过邮箱验证码或微信扫码验证后获取完整Token
5. 复制 Token（形如 `ssj_xxxxxxxxxxxx`）

**使用方式**：
```
Authorization: Bearer ssj_xxxxxxxxxxxx
```

> ⚠️ **安全提示**：Token 等同于完整登录凭证，请妥善保管，不要泄露给他人。
> 如怀疑Token 泄露，可在设置页随时撤销。

### 备选：账号密码登录（仅用于获取 Token）

此接口用于首次获取Token，或 Token 过期时重新获取。

**请求**:
```
POST /public/api.php?route=auth/login-password
Content-Type: application/json

{
  "account": "用户名或邮箱",
  "password": "密码"
}
```

**返回成功**:
```json
{
  "success": true,
  "token": "ssj_xxxxxxxxxxxx",
  "user": {
    "id": 123,
    "username": "user1",
    "nickname": "用户昵称",
    "email": "user@example.com",
    "role": "user"
  }
}
```

**返回失败**:
```json
{
  "success": false,
  "error": "账号或密码错误"
}
```

---

## 账户管理接口

### 2. 获取账户列表

**请求**:
```
GET /public/api.php?route=accounts/list
Authorization: Bearer <token>
```

**返回**:
```json
{
  "success": true,
  "accounts": [
    {
      "id": 1,
      "name": "现金",
      "group_name": "现金账户",
      "account_no": "N/A",
      "initial_balance": 1000,
      "current_balance": 500,
      "is_default": 1
    },
    {
      "id": 2,
      "name": "支付宝",
      "group_name": "第三方支付",
      "account_no": "xxx@alipay",
      "initial_balance": 5000,
      "current_balance": 3000,
      "is_default": 0
    },
    {
      "id": 3,
      "name": "工资卡",
      "group_name": "银行卡",
      "account_no": "6214****",
      "initial_balance": 10000,
      "current_balance": 8500,
      "is_default": 0
    }
  ]
}
```

---

## 分类和项目接口

### 3. 获取分类列表

**请求**:
```
GET /public/api.php?route=categories/list
Authorization: Bearer <token>
```

**返回**:
```json
{
  "success": true,
  "categories": [
    {
      "id": 1,
      "name": "餐饮",
      "type": "expense",
      "icon_type": "emoji",
      "icon_value": "🍽"
    },
    {
      "id": 2,
      "name": "购物",
      "type": "expense",
      "icon_type": "emoji",
      "icon_value": "🛍"
    },
    {
      "id": 3,
      "name": "工资",
      "type": "income",
      "icon_type": "emoji",
      "icon_value": "💰"
    },
    {
      "id": 4,
      "name": "奖金",
      "type": "income",
      "icon_type": "emoji",
      "icon_value": "🎁"
    },
    {
      "id": 5,
      "name": "转账",
      "type": "transfer",
      "icon_type": "emoji",
      "icon_value": "→"
    }
  ]
}
```

### 4. 创建分类

**请求**:
```
POST /public/api.php?route=categories/create
Authorization: Bearer <token>
Content-Type: application/json

{
  "type": "expense",
  "name": "交通",
  "sort_order": 0,
  "icon_library_id": 0
}
```

**参数说明**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| type | string | 是| 类型：`expense`（支出）、`income`（收入）、`transfer`（转账） |
| name | string | 是| 分类名称 |
| sort_order | number | 否| 排序顺序（默认0）|
| icon_library_id | number | 否| 图标库ID，0 表示不使用图标库|

**返回成功**:
```json
{
  "success": true
}
```

**返回失败**:
```json
{
  "success": false,
  "error": "请填写分类名称并选择类型"
}
```

### 5. 更新分类

**请求**:
```
POST /public/api.php?route=categories/update
Authorization: Bearer <token>
Content-Type: application/json

{
  "id": 1,
  "type": "expense",
  "name": "餐饮",
  "sort_order": 0,
  "icon_library_id": 0
}
```

**参数说明**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| id | number | 是| 分类 ID |
| type | string | 是| 类型：`expense`（支出）、`income`（收入）、`transfer`（转账） |
| name | string | 是| 分类名称 |
| sort_order | number | 否| 排序顺序（默认0）|
| icon_library_id | number | 否| 图标库ID，0 表示不使用图标库|

**返回成功**:
```json
{
  "success": true
}
```

### 6. 删除分类

**请求**:
```
POST /public/api.php?route=categories/delete
Authorization: Bearer <token>
Content-Type: application/json

{
  "id": 1
}
```

**参数说明**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| id | number | 是| 分类 ID |

**返回成功**:
```json
{
  "success": true
}
```

### 7. 获取项目列表

**请求**:
```
GET /public/api.php?route=items/list
Authorization: Bearer <token>
```

**可选参数**:
```
GET /public/api.php?route=items/list&category_id=1
```

**返回**:
```json
{
  "success": true,
  "items": [
    {
      "id": 1,
      "name": "午餐",
      "category_id": 1,
      "category_name": "餐饮",
      "icon_type": "emoji",
      "icon_value": "🍜"
    },
    {
      "id": 2,
      "name": "晚餐",
      "category_id": 1,
      "category_name": "餐饮",
      "icon_type": "emoji",
      "icon_value": "🍲"
    },
    {
      "id": 3,
      "name": "早餐",
      "category_id": 1,
      "category_name": "餐饮",
      "icon_type": "emoji",
      "icon_value": "🥣"
    },
    {
      "id": 4,
      "name": "衣服",
      "category_id": 2,
      "category_name": "购物",
      "icon_type": "emoji",
      "icon_value": "👕"
    }
  ]
}
```

### 8. 创建项目

**请求**:
```
POST /public/api.php?route=items/create
Authorization: Bearer <token>
Content-Type: application/json

{
  "category_id": 1,
  "name": "零食",
  "sort_order": 0,
  "icon_library_id": 0
}
```

**参数说明**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| category_id | number | 是| 分类 ID |
| name | string | 是| 项目名称 |
| sort_order | number | 否| 排序顺序（默认0）|
| icon_library_id | number | 否| 图标库ID，0 表示不使用图标库|

**返回成功**:
```json
{
  "success": true
}
```

**返回失败**:
```json
{
  "success": false,
  "error": "请选择分类并填写项目名称
}
```

### 9. 更新项目

**请求**:
```
POST /public/api.php?route=items/update
Authorization: Bearer <token>
Content-Type: application/json

{
  "id": 1,
  "category_id": 1,
  "name": "午餐",
  "sort_order": 0,
  "icon_library_id": 0
}
```

**参数说明**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| id | number | 是| 项目 ID |
| category_id | number | 是| 分类 ID |
| name | string | 是| 项目名称 |
| sort_order | number | 否| 排序顺序（默认0）|
| icon_library_id | number | 否| 图标库ID，0 表示不使用图标库|

**返回成功**:
```json
{
  "success": true
}
```

### 10. 删除项目

**请求**:
```
POST /public/api.php?route=items/delete
Authorization: Bearer <token>
Content-Type: application/json

{
  "id": 1
}
```

**参数说明**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| id | number | 是| 项目 ID |

**返回成功**:
```json
{
  "success": true
}
```

---

## AI 解析接口

### 11. AI 解析记账文本 ⭐**QClaw 专用**

将自然语言文本解析为结构化记账数据。可选附带截图，截图将保存为交易附件。

**请求**:
```
POST /public/api.php?route=aai/transactions/parse
Authorization: Bearer <token>
Content-Type: application/json

{
  "text": "午饭 35入",
  "images": [
    "data:image/jpeg;base64,/9j/4AAQ...",
    "data:image/png;base64,iVBORw0KG..."
  ]
}
```

**参数说明**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| text | string | 是| 需要解析的记账指令文本 |
| images | string[] | 否| base64 编码的图片数组，最多5 张。支持`data:image/xxx;base64,...` 格式或纯 base64 字符串|

**返回成功**:
```json
{
  "success": true,
  "parsed": {
    "type": "expense",
    "amount": 35,
    "category_name": "餐饮",
    "item_name": "午餐",
    "from_account_name": null,
    "to_account_name": null,
    "trans_time": null,
    "remark": "午饭",
    "source": "ai"
  },
  "aattachment_paths": [
    "1/2026/05/13/att_6834a2b.jpg",
    "1/2026/05/13/att_6834a2c.png"
  ],
  "attachment_urls": [
    "/uploads/1/2026/05/13/att_6834a2b.jpg",
    "/uploads/1/2026/05/13/att_6834a2c.png"
  ]
}
```

**说明**:
- `parsed` 中的字段不是AI 解析结果，`category_name` 和`item_name` 是名称而非 ID，需要通过分类/项目列表接口匹配对应 ID
- `from_account_name` / `to_account_name` 同理，需要匹配账户ID
- `aattachment_paths` 仅在传了 `images` 参数且有图片保存成功时返回
- 拿到 `aattachment_paths` 后，调用 `transactions/create` 时原样传入`aattachment_paths` 字段即可将截图关联到交易

**返回失败**:
```json
{
  "success": false,
  "error": "AI 解析功能尚未启用"
}
```

**常见错误**:

| HTTP 状态码 | 错误 | 含义 |
|-------------|------|------|
| 400 | `请提供需要解析的记账指令文本` | text 参数为空 |
| 503 | `AI 解析功能尚未启用` | 系统未开启AI 功能 |
| 500 | `AI 解析失败，..` | QClaw 调用异常 |

---

## 记账接口

### 12. 创建记账 - 支出 ⭐**最常用**

**请求**:
```
POST /public/api.php?route=transactions/create
Authorization: Bearer <token>
Content-Type: application/json

{
  "type": "expense",
  "amount": 35.50,
  "category_id": 1,
  "item_id": 1,
  "from_account_id": 1,
  "trans_time": "2026-05-02 12:30:00",
  "remark": "午饭",
  "source": "qclaw",
  "aattachment_paths": ["1/2026/05/13/att_6834a2b.jpg"]
}
```

**参数说明**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| type | string | 是| 类型：`expense`（支出）、`income`（收入）、`transfer`（转账） |
| amount | number | 是| 金额（正数，单位：元）|
| category_id | number | 是| 分类 ID |
| item_id | number | 否| 项目 ID（0 或空表示不选项目） |
| from_account_id | number | 是（支出）| 支出账户 ID（支出时必填）|
| to_account_id | number | 是（收入）| 收入账户 ID（收入时必填）|
| trans_time | string | 否| 记账时间，格式`YYYY-MM-DD HH:MM:SS`（不填默认当前时间） |
| remark | string | 否| 备注（可选） |
| source | string | 否| 记账来源：`manual`（手动）、`ai`（AI）、`qclaw`（QClaw），默认 `manual` |
| aattachment_paths | string[] | 否| 附件图片路径数组，最多5 张。路径来自AI 解析接口或上传接口的返回值|

**返回成功**:
```json
{
  "success": true,
  "id": 12345
}
```

**返回失败**:
```json
{
  "success": false,
  "error": "金额必须大于0"
}
```

---

### 13. 创建记账 - 收入

**请求**:
```
POST /public/api.php?route=transactions/create
Authorization: Bearer <token>
Content-Type: application/json

{
  "type": "income",
  "amount": 15000,
  "category_id": 3,
  "item_id": 0,
  "to_account_id": 3,
  "trans_time": "2026-05-02 09:00:00",
  "remark": "五月工资"
}
```

---

### 14. 创建记账 - 转账

**请求**:
```
POST /public/api.php?route=transactions/create
Authorization: Bearer <token>
Content-Type: application/json

{
  "type": "transfer",
  "amount": 1000,
  "category_id": 5,
  "item_id": 0,
  "from_account_id": 1,
  "to_account_id": 2,
  "trans_time": "2026-05-02 14:00:00",
  "remark": "现金转支付宝"
}
```

---

### 15. 上传交易附件

**请求**:
```
POST /public/api.php?route=transactions/upload-attachment
Authorization: Bearer <token>
Content-Type: multipart/form-data

file: <图片文件>
```

**返回成功**:
```json
{
  "success": true,
  "path": "1/2026/05/13/att_6834a2b.jpg",
  "url": "/uploads/1/2026/05/13/att_6834a2b.jpg"
}
```

**说明**:
- 用于上传单张图片附件，返回的 `path` 可传入`transactions/create` 的`aattachment_paths` 字段
- 文件大小限制 10MB
- 如果使用 AI 解析接口的`images` 参数，无需单独调用此接口

---

### 16. 更新记账

**请求**:
```
POST /public/api.php?route=transactions/update
Authorization: Bearer <token>
Content-Type: application/json

{
  "id": 12345,
  "type": "expense",
  "amount": 40.00,
  "category_id": 1,
  "item_id": 1,
  "from_account_id": 1,
  "trans_time": "2026-05-02 13:00:00",
  "remark": "午饭（已修正）
}
```

**参数说明**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| id | number | 是| 记账 ID |
| type | string | 否| 类型（不填则保持原值） |
| amount | number | 否| 金额（不填则保持原值） |
| category_id | number | 否| 分类 ID（不填则保持原值） |
| item_id | number | 否| 项目 ID（不填则保持原值） |
| from_account_id | number | 否| 支出账户 ID（不填则保持原值） |
| to_account_id | number | 否| 收入账户 ID（不填则保持原值） |
| trans_time | string | 否| 记账时间（不填则保持原值） |
| remark | string | 否| 备注（不填则保持原值） |
| aattachment_paths | string[] | 否| 替换附件（传入则替换，不传则不变（|

**返回成功**:
```json
{
  "success": true
}
```

---

### 17. 删除记账

**请求**:
```
POST /public/api.php?route=transactions/delete
Authorization: Bearer <token>
Content-Type: application/json

{
  "ids": [12345, 12346]
}
```

**参数说明**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| ids | array | 是| 要删除的记账 ID 列表 |

**返回成功**:
```json
{
  "success": true,
  "deleted": 2
}
```

---

## 查询接口

### 18. 获取记账列表

**请求**:
```
GET /public/api.php?route=transactions/list?page=1&page_size=50
Authorization: Bearer <token>
```

**可选参数**:
```
?page=1                    # 页码（默认1）
&page_size=50              # 每页条数（默认50）
&date=2026-05-02           # 特定日期（格式YYYY-MM-DD（
&start_date=2026-05-01     # 开始日期
&end_date=2026-05-31       # 结束日期
&type=expense              # 类型过滤（expense/income/transfer）
&category_id=1             # 分类 ID
&account_id=1              # 账户 ID
```

**返回**:
```json
{
  "success": true,
  "transactions": [
    {
      "id": 12345,
      "type": "expense",
      "source": "qclaw",
      "amount": 35.50,
      "category_id": 1,
      "category_name": "餐饮",
      "item_id": 1,
      "item_name": "午餐",
      "from_account_id": 1,
      "from_account_name": "现金",
      "to_account_id": null,
      "to_account_name": null,
      "trans_time": "2026-05-02 12:30:00",
      "remark": "午饭",
      "attachments": ["1/2026/05/13/att_6834a2b.jpg"],
      "attachment_urls": ["/uploads/1/2026/05/13/att_6834a2b.jpg"],
      "created_at": "2026-05-02 12:32:00"
    }
  ],
  "summary": {
    "income": 0,
    "expense": 35.50
  },
  "pagination": {
    "page": 1,
    "page_size": 50,
    "total": 1
  }
}
```

### 19. 获取用户统计

**请求**:
```
GET /public/api.php?route=user/stats
Authorization: Bearer <token>
```

**返回**:
```json
{
  "success": true,
  "stats": {
    "register_days": 120,
    "book_days": 127,
    "streak_days": 5,
    "transaction_count": 245
  }
}
```

### 20. 获取当前用户信息

**请求**:
```
GET /public/api.php?route=aauth/profile
Authorization: Bearer <token>
```

**返回**:
```json
{
  "success": true,
  "user": {
    "id": 123,
    "username": "user1",
    "nickname": "用户昵称",
    "email": "user@example.com",
    "role": "user",
    "theme_mode": "light",
    "avatar_url": null
  }
}
```

---

## 使用流程示例

### 场景 1：用户发截图记账（QClaw 最常用流程）

> 前置条件：已持有有效的API Token（在设置页创建并复制）

1. 用户微信发送消费截图给 QClaw
2. QClaw 识别图片内容，理解为一笔支出
3. 将截图base64 编码，调用`aai/transactions/parse`（
   ```json
   {
     "text": "午饭 35入",
     "images": ["data:image/jpeg;base64,/9j/4AAQ..."]
   }
   ```
4. 获取解析结果和附件路径：
   ```json
   {
     "parsed": { "type": "expense", "amount": 35, "category_name": "餐饮", ... },
     "aattachment_paths": ["1/2026/05/13/att_6834a2b.jpg"]
   }
   ```
5. 调用 `categories/list` 匹配 "餐饮" 的category_id
6. 调用 `items/list?category_id=1` 匹配 "午餐" 的item_id
7. 调用 `accounts/list` 获取默认账户 ID
8. 调用 `transactions/create`，把解析结果和aattachment_paths 一起传入：
   ```json
   {
     "type": "expense",
     "amount": 35,
     "category_id": 1,
     "item_id": 1,
     "from_account_id": 1,
     "remark": "午饭",
     "source": "qclaw",
     "aattachment_paths": ["1/2026/05/13/att_6834a2b.jpg"]
   }
   ```
9. 返回用户（✅已记录：支出 ¥35（餐饮午餐，现金）📎已附截图"

### 场景 2：用户文字记账午饭 35"

1. QClaw 理解为支出35 入
2. 调用 `aai/transactions/parse`：`{ "text": "午饭 35入 }`
3. 获取解析结果，匹配category_id / item_id / account_id
4. 调用 `transactions/create` 创建记录
5. 返回用户（✅已记录：支出 ¥35（餐饮午餐，现金）"

### 场景 3：用户说"工资到账 15000"

1. QClaw 理解为收入15000 入
2. 调用 `aai/transactions/parse`：`{ "text": "工资到账 15000" }`
3. 匹配 category_id / account_id
4. 调用 `transactions/create`（
   ```json
   {
     "type": "income",
     "amount": 15000,
     "category_id": 3,
     "to_account_id": 3,
     "remark": "工资",
     "source": "qclaw"
   }
   ```
5. 返回用户（✅已记录：收入 ¥15000（工资，工资卡）"

### 场景 4：用户说"今天花了多少"

1. 调用 `transactions/list?date=2026-05-13&type=expense`
2. 计算总支出：`summary.expense`
3. 返回用户（今天支出 ¥83.50，包括：午餐 ¥35、晚餐¥48.50"

---

## 错误处理

所有错误响应都遵循这个格式）

```json
{
  "success": false,
  "error": "具体错误信息"
}
```

**常见错误**:

| HTTP 状态码 | 错误 | 含义 |
|-------------|------|------|
| 400 | `金额必须大于0` | 金额参数无效 |
| 400 | `请选择分类` | 缺少分类 ID |
| 400 | `支出需要选择支出账户` | 支出类型缺少账户 |
| 401 | `未登录或缺少 Token` | 没有提供有效的认证令片|
| 401 | `登录已过期，请重新登录` | Token 已过期或被撤销 |
| 403 | `无权限访问该账本` | 没有访问权限 |
| 403 | `账号已被禁用` | 用户状态异常|
| 404 | `记录不存在` | 记账不存在|
| 500 | `保存记账失败，请稍后重试` | 服务器错误|

---

## 快速参考

### 最常用的接口（按调用频率排序）

1. **AI 解析记账文本**
   ```
   POST ?route=aai/transactions/parse
   Header: Authorization: Bearer <token>
   Body: {"text": "午饭35", "images": ["data:image/jpeg;base64,..."]}
   ```

2. **创建记账**
   ```
   POST ?route=transactions/create
   Header: Authorization: Bearer <token>
   Body: {"type": "expense", "amount": 35, "category_id": 1, "from_account_id": 1, "aattachment_paths": [...]}
   ```

3. **获取基础数据**
   ```
   GET ?route=accounts/list        Header: Authorization: Bearer <token>
   GET ?route=categories/list       Header: Authorization: Bearer <token>
   GET ?route=items/list            Header: Authorization: Bearer <token>
   ```

4. **查询记账**
   ```
   GET ?route=transactions/list     Header: Authorization: Bearer <token>
   GET ?route=user/stats            Header: Authorization: Bearer <token>
   ```

### 重要事项

- ✅**推荐方式**：所有请求使用`Authorization: Bearer <token>` 认证，Token 在设置页创建和管理
- ⚠️ Token 等同于登录凭证，请妥善保管，不要泄露
- 🔁 Token 可随时在设置页撤销并重新生户
- 💡 账号密码登录仅用于首次获取Token 或Token 过期时重新获取
- 金额字段使用数字，单位为入
- 时间戳格式为 `YYYY-MM-DD HH:MM:SS`
- 如果不指定时间，系统自动使用当前时间
- 分类 ID 和账户ID 需要从相应的列表接口获取
- AI 解析返回的`category_name` / `item_name` / `from_account_name` 是名称，需要匹配为 ID 后再创建交易
- 截图通过 AI 解析接口的`images` 参数上传，或通过 `transactions/upload-attachment` 单独上传
- `source` 字段建议 QClaw 记账时使用`qclaw`

---

## 更新日期

- 文档版本，*2.0**
- 最后更新：2026-05-13

## 版本变更说明

### v2.0 (2026-05-13) —🔄 认证体系重构
- ✅**认证方式改为 API Token 优先**：所有接口统一使用 `Authorization: Bearer <token>` 认证
- ✅新增「API Token 获取指南」：详细说明如何在设置页创建、查看、管理Token
- ✅账号密码登录降级为备选方案（仅用于获取Token）
- ✅新增 `aauth/profile` 接口文档 —获取当前 Token 对应的用户信息
- ✅所有接口示例统一加上 Bearer Token 认证失
- ✅快速参考部分重排，按实际调用频率排序
- ✅移除 user stats 中不准确的total_income / total_expense 字段（该接口实际不返回这些）

### v1.2 (2026-05-13)
- ✅新增 `aai/transactions/parse` 接口文档 —AI 解析记账文本，支持附带截图
- ✅新增 `transactions/upload-attachment` 接口文档 —上传交易附件
- ✅`transactions/create` 新增 `aattachment_paths` 参数 —支持关联多张附件图片（最多张）
- ✅`transactions/create` 新增 `source: "qclaw"` 选项
- ✅`transactions/update` 新增 `aattachment_paths` 参数
- ✅新增场景1：截图记账完整流程示例
- ✅移除旧版百度千帆 AI 相关内容

### v1.1 (2026-05-04)
- ✅新增 `transactions/update` 接口 - 更新已有的记账记录
- ✅新增 `transactions/delete` 接口 - 删除一条或多条记账记录
- ✅修复移动端编辑表单时间格式转换（兼容 `/` 格式日期）
- ✅修复桌面端详情弹窗时间值规范化处理
- ✅完整的CRUD 操作支持

### v1.0 (2026-05-02)
- ✅初版文档发布
- ✅基础认证、账户、分类、项目、记账管理接口

## 负债管理接口

### 21. 获取当月应还数据 ? **QClaw 月度推送专用**

**请求**:
```
GET /public/api.php?route=debt/current-month
Authorization: Bearer <token>
```

**返回**:
```json
{
  "success": true,
  "payments": [
    {
      "id": 1,
      "debt_config_id": 1,
      "debt_name": "房贷",
      "period_number": 12,
      "total_amount": 5000.00,
      "due_date": "2026-05-15",
      "status": "pending",
      "paid_amount": null,
      "paid_date": null,
      "remaining_periods": 24,
      "remaining_amount": 120000.00
    },
    {
      "id": 2,
      "debt_config_id": 2,
      "debt_name": "信用卡",
      "period_number": 1,
      "total_amount": 2000.00,
      "due_date": "2026-05-20",
      "status": "paid",
      "paid_amount": 2000.00,
      "paid_date": "2026-05-18",
      "remaining_periods": 0,
      "remaining_amount": 0.00
    }
  ],
  "total_amount": 7000.00,
  "total_count": 2,
  "paid_amount": 2000.00,
  "paid_count": 1
}
```

**说明**:
- 返回当前月份所有应还的负债记录（包括已还和未还）
- 	otal_amount 为当月应还总额
- paid_amount 为当月已还总额
- 
emaining_periods 和 
emaining_amount 为该负债的剩余期数和金额

### 22. 获取负债汇总统计

**请求**:
```
GET /public/api.php?route=debt/summary
Authorization: Bearer <token>
```

**返回**:
```json
{
  "success": true,
  "summary": [
    {
      "debt_id": 1,
      "debt_name": "房贷",
      "total_principal": 1000000.00,
      "total_interest": 200000.00,
      "installment_count": 360,
      "per_period_total": 3333.33,
      "paid_periods": 120,
      "remaining_periods": 240,
      "total_paid": 400000.00,
      "remaining_amount": 800000.00,
      "progress_percent": 33
    }
  ],
  "grand_total_principal": 1000000.00,
  "grand_total_interest": 200000.00,
  "grand_total_paid": 400000.00,
  "grand_total_remaining": 800000.00,
  "grand_total_periods": 360,
  "grand_paid_periods": 120,
  "grand_progress_percent": 33
}
```

---

## 报销管理接口

### 23. 获取待报销数据 ? **QClaw 月度推送专用**

**请求**:
```
GET /public/api.php?route=rreimbursement/pending
Authorization: Bearer <token>
```

**返回**:
```json
{
  "success": true,
  "items": [
    {
      "id": 1,
      "title": "差旅费",
      "amount": 1500.00,
      "category_name": "交通",
      "description": "北京出差打车费用",
      "status": "pending",
      "created_at": "2026-05-10 14:30:00",
      "transaction_amount": 1500.00,
      "transaction_date": "2026-05-08 09:00:00"
    }
  ],
  "overview": {
    "pending_count": 3,
    "pending_amount": 4500.00,
    "reimbursed_count": 10,
    "reimbursed_amount": 15000.00,
    "this_month_count": 2,
    "this_month_amount": 3000.00
  }
}
```

**说明**:
- items 为待报销和已批准但未报销的记录列表
- overview 包含报销概览数据：待报销数量和金额、已报销数量和金额、本月已报销数量和金额

### 24. 获取报销概览数据

**请求**:
```
GET /public/api.php?route=rreimbursement/overview
Authorization: Bearer <token>
```

**返回**:
```json
{
  "success": true,
  "overview": {
    "pending_count": 3,
    "pending_amount": 4500.00,
    "reimbursed_count": 10,
    "reimbursed_amount": 15000.00,
    "this_month_count": 2,
    "this_month_amount": 3000.00
  },
  "monthly": [
    {
      "month": "2026-05",
      "count": 2,
      "total_amount": 3000.00
    },
    {
      "month": "2026-04",
      "count": 5,
      "total_amount": 7500.00
    }
  ],
  "category": [
    {
      "category_name": "交通",
      "count": 8,
      "total_amount": 12000.00
    },
    {
      "category_name": "餐饮",
      "count": 2,
      "total_amount": 3000.00
    }
  ]
}
```

---

## 订阅管理接口

### 25. 获取订阅列表

**请求**:
```
GET /public/api.php?route=subscriptions/list
Authorization: Bearer <token>
```

**可选参数**:
```
?q=关键词           # 按平台名称搜索
```

**返回**:
```json
{
  "success": true,
  "subscriptions": [
    {
      "id": 1,
      "platform": "Netflix",
      "type": "subscription",
      "price": 98.00,
      "expire_date": "2026-06-15",
      "auto_renew": true,
      "period": "monthly",
      "status": "active",
      "remark": null,
      "days_left": 28,
      "icon_url": "https://example.com/uploads/icon.png"
    }
  ]
}
```

**说明**:
- days_left 为距离到期的天数（负数表示已过期）
- icon_url 为订阅图标URL

### 26. 创建/更新订阅记录

**请求**:
```
POST /public/api.php?route=subscriptions/save
Authorization: Bearer <token>
Content-Type: application/json

{
  "platform": "Netflix",
  "type": "subscription",
  "price": 98.00,
  "expire_date": "2026-06-15",
  "auto_renew": true,
  "period": "monthly",
  "remark": "家庭套餐"
}
```

**参数说明**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| id | number | 否 | 订阅ID（编辑时必填） |
| platform | string | 是 | 平台名称 |
| type | string | 是 | 类型：subscription（订阅）、lifetime（买断） |
| price | number | 是 | 价格（元） |
| expire_date | string | 是（订阅） | 到期日期（YYYY-MM-DD） |
| auto_renew | boolean | 否 | 是否自动续费 |
| period | string | 否 | 周期：monthly（月）、yearly（年） |
| remark | string | 否 | 备注 |

**返回成功**:
```json
{
  "success": true
}
```

### 27. 续费订阅

**请求**:
```
POST /public/api.php?route=subscriptions/renew
Authorization: Bearer <token>
Content-Type: application/json

{
  "id": 1,
  "type": "subscription",
  "price": 98.00,
  "expire_date": "2026-07-15",
  "auto_renew": true,
  "period": "monthly"
}
```

**返回成功**:
```json
{
  "success": true
}
```

### 28. 删除订阅记录

**请求**:
```
POST /public/api.php?route=subscriptions/delete
Authorization: Bearer <token>
Content-Type: application/json

{
  "id": 1
}
```

**返回成功**:
```json
{
  "success": true
}
```

**说明**:
- 删除操作为逻辑删除，系统会在到期30天后自动清理数据

---

## 更新日期

- 文档版本：**3.1**
- 最后更新：2026-05-18

## 版本变更说明

### v3.1 (2026-05-18)
- ? 新增财务报告 API 文档 — 月度/季度/年度 HTML 报告，支持 QClaw 定时推送
- ? 报告支持 token 参数免 Cookie 访问

### v3.0 (2026-05-18) — ?? 新增负债、报销、订阅接口
- ? 新增 debt/current-month 接口 — 获取当月应还数据（QClaw月度推送专用）
- ? 新增 debt/summary 接口 — 获取负债汇总统计
- ? 新增 
reimbursement/pending 接口 — 获取待报销数据（QClaw月度推送专用）
- ? 新增 
reimbursement/overview 接口 — 获取报销概览数据
- ? 新增 subscriptions/list 接口文档 — 获取订阅列表
- ? 新增 subscriptions/save 接口文档 — 创建/更新订阅记录
- ? 新增 subscriptions/renew 接口文档 — 续费订阅
- ? 新增 subscriptions/delete 接口文档 — 删除订阅记录

### v2.0 (2026-05-13) — ?? 认证体系重构
- ? **认证方式改为 API Token 优先**：所有接口统一使用 Authorization: Bearer <token> 认证
- ? 新增「API Token 获取指南」：详细说明如何在设置页创建、查看、管理 Token
- ? 账号密码登录降级为备选方案（仅用于获取 Token）
- ? 新增 auth/profile 接口文档 — 获取当前 Token 对应的用户信息
- ? 所有接口示例统一加上 Bearer Token 认证头
- ? 快速参考部分重排，按实际调用频率排序
- ? 移除 user stats 中不准确的 total_income / total_expense 字段（该接口实际不返回这些）

### v1.2 (2026-05-13)
- ? 新增 ai/transactions/parse 接口文档 — AI 解析记账文本，支持附带截图
- ? 新增 	ransactions/upload-attachment 接口文档 — 上传交易附件
- ? 	ransactions/create 新增 attachment_paths 参数 — 支持关联多张附件图片（最多5张）
- ? 	ransactions/create 新增 source: "qclaw" 选项
- ? 	ransactions/update 新增 attachment_paths 参数
- ? 新增场景1：截图记账完整流程示例
- ? 移除旧版百度千帆 AI 相关内容

### v1.1 (2026-05-04)
- ? 新增 	ransactions/update 接口 - 更新已有的记账记录
- ? 新增 	ransactions/delete 接口 - 删除一条或多条记账记录
- ? 修复移动端编辑表单时间格式转换（兼容 / 格式日期）
- ? 修复桌面端详情弹窗时间值规范化处理
- ? 完整的 CRUD 操作支持

### v1.0 (2026-05-02)
- ? 初版文档发布
- ? 基础认证、账户、分类、项目、记账管理接口



---

## ?? 财务报告（QClaw 定时推送专用）

财务报告是独立 HTML 页面，QClaw 可直接定时抓取 HTML 内容推送，无需二次渲染。

### 获取月度报告

**URL**: `GET /public/report-card.php?mode=monthly&year=2026&month=4`

**认证**: 需要 Cookie 登录态（浏览器访问），或通过 `?token=ssj_xxxxxxxxxxxx` 参数传递 API Token

**参数**:
| 参数 | 必填 | 说明 |
|------|------|------|
| mode | ? | `monthly` |
| year | ? | 年份，如 2026 |
| month | ? | 月份 1-12 |
| theme | ? | `dark`（默认）/ `light` |
| token | ? | API Token，用于无 Cookie 访问 |

**返回**: 完整 HTML 页面（Content-Type: text/html）

**QClaw 推送示例**: 每月1日自动推送上月报告
```
1. 访问 /public/report-card.php?mode=monthly&year=当前年&month=上月&token=ssj_xxx
2. 获取 HTML 内容
3. 直接推送给用户
```

### 获取季度报告

**URL**: `GET /public/report-card.php?mode=quarterly&year=2026&quarter=2`

**参数**:
| 参数 | 必填 | 说明 |
|------|------|------|
| mode | ? | `quarterly` |
| year | ? | 年份 |
| quarter | ? | 季度 1-4 |
| theme | ? | `dark` / `light` |
| token | ? | API Token |

### 获取年度报告

**URL**: `GET /public/report-card.php?mode=yearly&year=2026`

**参数**:
| 参数 | 必填 | 说明 |
|------|------|------|
| mode | ? | `yearly` |
| year | ? | 年份 |
| theme | ? | `dark` / `light` |
| token | ? | API Token |

### 推送方案建议

| 推送类型 | Cron 表达式 | URL 示例 |
|---------|------------|---------|
| 月度报告 | `0 9 1 * *` | `?mode=monthly&year=YYYY&month=MM&token=ssj_xxx` |
| 季度报告 | `0 9 1 1,4,7,10 *` | `?mode=quarterly&year=YYYY&quarter=Q&token=ssj_xxx` |
| 年度报告 | `0 10 1 1 *` | `?mode=yearly&year=YYYY&token=ssj_xxx` |

> ?? 月度/季度报告中金额为项目（item）维度排名，含环比变化百分比；年度报告含月度趋势图。