# 📚 ThinkScramble 文档站点使用指南

本指南介绍如何使用和维护 ThinkScramble 的 Docsify 文档站点。

## 🌐 在线文档站点

**访问地址**: [https://yangweijie.github.io/think-scramble/](https://yangweijie.github.io/think-scramble/)

## 📁 文档结构

```
docs/
├── index.html              # Docsify 主页面
├── _sidebar.md             # 侧边栏导航
├── _navbar.md              # 顶部导航栏
├── _coverpage.md           # 封面页
├── _404.md                 # 404 错误页面
├── .nojekyll               # 禁用 Jekyll 处理
├── README.md               # 主页内容
├── quickstart.md           # 快速开始指南
├── installation.md         # 安装指南
├── pie-installation.md     # PIE 安装指南
├── faq.md                  # 常见问题
├── changelog.md            # 更新日志
└── assets/                 # 资源文件
    ├── logo.svg            # Logo 图片
    └── bg.svg              # 背景图片
```

## 🚀 本地预览

### 方法 1: 使用预览脚本（推荐）

```bash
# Linux/macOS
./scripts/serve-docs.sh

# Windows
scripts\serve-docs.bat

# 自定义端口
./scripts/serve-docs.sh 8080
```

### 方法 2: 手动启动

```bash
# 安装 docsify-cli
npm install -g docsify-cli

# 启动服务器
cd docs
docsify serve . --port 3000 --open
```

### 方法 3: 使用 Python

```bash
# Python 3
cd docs
python -m http.server 3000

# Python 2
cd docs
python -m SimpleHTTPServer 3000
```

## 🔧 文档维护

### 添加新页面

1. **创建 Markdown 文件**
   ```bash
   # 在 docs/ 目录下创建新文件
   touch docs/new-page.md
   ```

2. **编写内容**
   ```markdown
   # 新页面标题
   
   页面内容...
   ```

3. **更新导航**
   ```markdown
   <!-- docs/_sidebar.md -->
   * [新页面](new-page.md)
   ```

### 修改现有页面

直接编辑 `docs/` 目录下的 Markdown 文件，保存后刷新浏览器即可看到变化。

### 更新导航结构

#### 侧边栏导航 (`docs/_sidebar.md`)

```markdown
* [🏠 首页](/)

* **📚 分类名称**
  * [页面1](page1.md)
  * [页面2](page2.md)

* **🔧 另一个分类**
  * [页面3](page3.md)
```

#### 顶部导航 (`docs/_navbar.md`)

```markdown
* [🏠 首页](/)

* 📚 文档
  * [页面1](page1.md)
  * [页面2](page2.md)

* 🔗 链接
  * [GitHub](https://github.com/yangweijie/think-scramble)
```

### 自定义样式

在 `docs/index.html` 的 `<style>` 标签中添加自定义 CSS：

```css
/* 自定义主题色 */
:root {
  --theme-color: #42b883;
  --theme-color-secondary: #369870;
}

/* 自定义样式 */
.custom-class {
  /* 样式规则 */
}
```

## 📊 文档验证

### 运行验证脚本

```bash
# 验证文档完整性
php scripts/validate-docs.php
```

验证内容包括：
- ✅ 必要文件存在性
- ✅ 文档内容完整性
- ✅ 链接有效性
- ✅ 资源文件检查
- ✅ GitHub Actions 配置

### 手动检查清单

- [ ] 所有链接都能正常访问
- [ ] 图片资源正常显示
- [ ] 导航结构清晰合理
- [ ] 内容格式正确
- [ ] 代码示例可以运行
- [ ] 搜索功能正常

## 🚀 部署到 GitHub Pages

### 自动部署

文档会通过 GitHub Actions 自动部署：

1. **触发条件**
   - 推送到 `main` 分支
   - 修改 `docs/` 目录下的文件
   - 修改 `README.md` 文件

2. **部署流程**
   - 构建文档
   - 验证文档
   - 部署到 GitHub Pages
   - 测试部署结果

3. **查看状态**
   - 访问 GitHub 仓库的 Actions 页面
   - 查看部署日志和状态

### 手动部署

如果需要手动部署：

1. **启用 GitHub Pages**
   - 进入仓库设置
   - 找到 Pages 设置
   - 选择 "GitHub Actions" 作为源

2. **触发部署**
   ```bash
   # 推送更改
   git add docs/
   git commit -m "Update documentation"
   git push origin main
   ```

## 🎨 自定义配置

### Docsify 配置

在 `docs/index.html` 中修改 `window.$docsify` 配置：

```javascript
window.$docsify = {
  // 基本配置
  name: 'ThinkScramble',
  repo: 'https://github.com/yangweijie/think-scramble',
  
  // 功能配置
  loadSidebar: true,
  loadNavbar: true,
  coverpage: true,
  
  // 搜索配置
  search: {
    placeholder: '🔍 搜索文档...',
    noData: '😞 没有找到结果',
  },
  
  // 更多配置...
}
```

### 插件配置

添加或移除 Docsify 插件：

```html
<!-- 搜索插件 -->
<script src="//cdn.jsdelivr.net/npm/docsify/lib/plugins/search.min.js"></script>

<!-- 复制代码插件 -->
<script src="//cdn.jsdelivr.net/npm/docsify-copy-code@2"></script>

<!-- 分页插件 -->
<script src="//cdn.jsdelivr.net/npm/docsify-pagination/dist/docsify-pagination.min.js"></script>
```

## 📈 SEO 优化

### 元数据配置

在 `docs/index.html` 中添加 SEO 元数据：

```html
<meta name="description" content="ThinkScramble 是一个为 ThinkPHP 框架设计的自动 API 文档生成扩展包">
<meta name="keywords" content="ThinkPHP,OpenAPI,Swagger,API文档,PHP">
<meta name="author" content="Yang Weijie">

<!-- Open Graph -->
<meta property="og:title" content="ThinkScramble - ThinkPHP OpenAPI 文档生成器">
<meta property="og:description" content="自动生成高质量的 API 文档">
<meta property="og:image" content="https://yangweijie.github.io/think-scramble/assets/logo.svg">
```

### 站点地图

Docsify 会自动生成站点地图，无需手动配置。

## 🔍 搜索优化

### 搜索配置

```javascript
search: {
  maxAge: 86400000,           // 缓存时间
  paths: 'auto',              // 自动索引所有页面
  placeholder: '🔍 搜索文档...',
  noData: '😞 没有找到结果',
  depth: 6,                   // 搜索深度
  hideOtherSidebarContent: false,
}
```

### 搜索优化技巧

1. **使用清晰的标题**
2. **添加关键词标签**
3. **保持内容结构化**
4. **使用描述性的链接文本**

## 📱 移动端优化

文档站点已经针对移动端进行了优化：

- ✅ 响应式设计
- ✅ 触摸友好的导航
- ✅ 适配小屏幕
- ✅ 快速加载

## 🚨 故障排除

### 常见问题

1. **页面显示空白**
   - 检查 `docs/index.html` 是否存在
   - 确认 JavaScript 没有错误
   - 查看浏览器控制台

2. **导航不显示**
   - 检查 `_sidebar.md` 和 `_navbar.md` 文件
   - 确认 Docsify 配置正确

3. **搜索不工作**
   - 确认搜索插件已加载
   - 检查网络连接

4. **样式异常**
   - 清除浏览器缓存
   - 检查 CSS 文件加载

### 调试技巧

1. **使用浏览器开发者工具**
2. **查看网络请求**
3. **检查控制台错误**
4. **验证 Markdown 语法**

## 📞 获取帮助

如果遇到文档相关问题：

1. 📚 查看 [Docsify 官方文档](https://docsify.js.org/)
2. 🔍 搜索 [GitHub Issues](https://github.com/yangweijie/think-scramble/issues)
3. 💬 参与 [GitHub Discussions](https://github.com/yangweijie/think-scramble/discussions)
4. 🐛 [提交新问题](https://github.com/yangweijie/think-scramble/issues/new)

---

🎉 **现在你可以轻松维护和更新 ThinkScramble 的文档站点了！**
