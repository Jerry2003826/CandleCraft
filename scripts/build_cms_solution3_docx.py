"""Generate the CMS Solution 3 design document (Chinese) as a .docx file.

Run once from the repository root:
    python3 scripts/build_cms_solution3_docx.py

Output:
    docs/CMS-方案三-设计思路.docx
"""

from __future__ import annotations

from pathlib import Path
from typing import Iterable

from docx import Document
from docx.enum.table import WD_ALIGN_VERTICAL, WD_TABLE_ALIGNMENT
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.oxml.ns import qn
from docx.shared import Cm, Pt, RGBColor

OUTPUT_PATH = Path(__file__).resolve().parent.parent / "docs" / "CMS-方案三-设计思路.docx"

CN_FONT = "Microsoft YaHei"
EN_FONT = "Calibri"
MONO_FONT = "Consolas"


def _apply_cn_font(run, *, font_name: str = CN_FONT) -> None:
    """Force the given run to render CJK characters with `font_name`.

    python-docx defaults eastAsia to the theme font, which often falls back to
    a serif face on Windows preview. Setting `w:eastAsia` explicitly keeps the
    document looking consistent across viewers.
    """
    run.font.name = EN_FONT
    rpr = run._element.get_or_add_rPr()
    rfonts = rpr.find(qn("w:rFonts"))
    if rfonts is None:
        rfonts = rpr.makeelement(qn("w:rFonts"), {})
        rpr.append(rfonts)
    rfonts.set(qn("w:ascii"), EN_FONT)
    rfonts.set(qn("w:hAnsi"), EN_FONT)
    rfonts.set(qn("w:eastAsia"), font_name)


def _add_paragraph(
    doc: Document,
    text: str,
    *,
    style: str | None = None,
    bold: bool = False,
    size_pt: float | None = None,
    color: tuple[int, int, int] | None = None,
    align: int | None = None,
    space_after_pt: float | None = None,
) -> None:
    para = doc.add_paragraph(style=style) if style else doc.add_paragraph()
    if align is not None:
        para.alignment = align
    if space_after_pt is not None:
        para.paragraph_format.space_after = Pt(space_after_pt)
    run = para.add_run(text)
    run.bold = bold
    if size_pt is not None:
        run.font.size = Pt(size_pt)
    if color is not None:
        run.font.color.rgb = RGBColor(*color)
    _apply_cn_font(run)


def _add_bullets(doc: Document, items: Iterable[str]) -> None:
    for item in items:
        para = doc.add_paragraph(style="List Bullet")
        run = para.add_run(item)
        _apply_cn_font(run)


def _add_numbered(doc: Document, items: Iterable[str]) -> None:
    for item in items:
        para = doc.add_paragraph(style="List Number")
        run = para.add_run(item)
        _apply_cn_font(run)


def _add_heading(doc: Document, text: str, level: int) -> None:
    heading = doc.add_heading(level=level)
    run = heading.add_run(text)
    run.bold = True
    _apply_cn_font(run)


def _set_table_cell_borders(cell) -> None:
    tc_pr = cell._tc.get_or_add_tcPr()
    tc_borders = tc_pr.find(qn("w:tcBorders"))
    if tc_borders is None:
        tc_borders = tc_pr.makeelement(qn("w:tcBorders"), {})
        tc_pr.append(tc_borders)
    for edge in ("top", "left", "bottom", "right", "insideH", "insideV"):
        border = tc_borders.find(qn(f"w:{edge}"))
        if border is None:
            border = tc_borders.makeelement(qn(f"w:{edge}"), {})
            tc_borders.append(border)
        border.set(qn("w:val"), "single")
        border.set(qn("w:sz"), "6")
        border.set(qn("w:color"), "999999")


def _add_table(
    doc: Document,
    header: list[str],
    rows: list[list[str]],
    *,
    col_widths_cm: list[float] | None = None,
) -> None:
    table = doc.add_table(rows=1 + len(rows), cols=len(header))
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    table.autofit = False

    if col_widths_cm:
        for col_idx, width in enumerate(col_widths_cm):
            for row in table.rows:
                row.cells[col_idx].width = Cm(width)

    for col_idx, label in enumerate(header):
        cell = table.rows[0].cells[col_idx]
        cell.vertical_alignment = WD_ALIGN_VERTICAL.CENTER
        cell.paragraphs[0].alignment = WD_ALIGN_PARAGRAPH.CENTER
        run = cell.paragraphs[0].add_run(label)
        run.bold = True
        run.font.size = Pt(10.5)
        run.font.color.rgb = RGBColor(0xFF, 0xFF, 0xFF)
        _apply_cn_font(run)
        # 蓝色表头
        tc_pr = cell._tc.get_or_add_tcPr()
        shd = tc_pr.find(qn("w:shd"))
        if shd is None:
            shd = tc_pr.makeelement(qn("w:shd"), {})
            tc_pr.append(shd)
        shd.set(qn("w:val"), "clear")
        shd.set(qn("w:color"), "auto")
        shd.set(qn("w:fill"), "2F5597")
        _set_table_cell_borders(cell)

    for row_idx, row in enumerate(rows, start=1):
        for col_idx, value in enumerate(row):
            cell = table.rows[row_idx].cells[col_idx]
            cell.vertical_alignment = WD_ALIGN_VERTICAL.CENTER
            run = cell.paragraphs[0].add_run(value)
            run.font.size = Pt(10)
            _apply_cn_font(run)
            _set_table_cell_borders(cell)


def build() -> None:
    OUTPUT_PATH.parent.mkdir(parents=True, exist_ok=True)
    doc = Document()

    section = doc.sections[0]
    section.top_margin = Cm(2.2)
    section.bottom_margin = Cm(2.2)
    section.left_margin = Cm(2.4)
    section.right_margin = Cm(2.4)

    style = doc.styles["Normal"]
    style.font.name = EN_FONT
    style.font.size = Pt(11)
    rpr = style.element.get_or_add_rPr()
    rfonts = rpr.find(qn("w:rFonts"))
    if rfonts is None:
        rfonts = rpr.makeelement(qn("w:rFonts"), {})
        rpr.append(rfonts)
    rfonts.set(qn("w:eastAsia"), CN_FONT)

    # ----------------- 封面 -----------------
    _add_paragraph(
        doc,
        "CakePHP 自建 CMS",
        bold=True,
        size_pt=26,
        color=(0x1F, 0x3A, 0x5F),
        align=WD_ALIGN_PARAGRAPH.CENTER,
        space_after_pt=4,
    )
    _add_paragraph(
        doc,
        "方案三：分页 + 类型化区块 + 媒体库 + 修订 + 协同锁",
        bold=True,
        size_pt=14,
        color=(0x2F, 0x55, 0x97),
        align=WD_ALIGN_PARAGRAPH.CENTER,
        space_after_pt=24,
    )
    _add_paragraph(
        doc,
        "面向第二版迭代 · 站点管理员可零代码维护页面文案、Logo、图片",
        size_pt=11,
        color=(0x55, 0x55, 0x55),
        align=WD_ALIGN_PARAGRAPH.CENTER,
        space_after_pt=2,
    )
    _add_paragraph(
        doc,
        "项目仓库：cakephp-app · 文档版本 v1.0 · 编制日期 2026-05-11",
        size_pt=10,
        color=(0x88, 0x88, 0x88),
        align=WD_ALIGN_PARAGRAPH.CENTER,
        space_after_pt=18,
    )

    # ----------------- 一句话定位 -----------------
    _add_heading(doc, "一、一句话定位", level=1)
    _add_paragraph(
        doc,
        "方案三是把网站上的可变内容（标题、段落、图片、Logo 等）从模板代码里"
        "剥离出来，存进一组结构化的数据库表，再用一层服务和模板帮助器把它"
        "无侵入地接回页面，让管理员在后台“点改即生效”，开发者在视图里只写"
        "一行 $this->Cms->text(...)。",
    )

    # ----------------- 选型对比 -----------------
    _add_heading(doc, "二、为什么选方案三（vs 方案一 / 方案二）", level=1)
    _add_paragraph(
        doc,
        "在设计阶段我们对比了三类做法，最终方案三胜出，理由如下：",
    )

    _add_table(
        doc,
        header=["方案", "做法", "优势", "为什么放弃 / 不够"],
        rows=[
            [
                "方案一：键值表",
                "一张 site_settings(key, value)，所有内容堆在一起",
                "上手最快，迁移成本最低",
                "无类型、无版本、无媒体管理；HTML 与纯文本混存难以校验；"
                "页面增多后命名空间混乱；不能审计回滚",
            ],
            [
                "方案二：单页 JSON",
                "每个页面一行 JSON，前端反序列化",
                "结构灵活，单页改动便捷",
                "字段无强约束，多人编辑必冲突；图片仍需另一套上传流程；"
                "回滚粒度只能整页",
            ],
            [
                "方案三：分层结构化",
                "site_pages → page_sections（带类型）→ site_media，"
                "另配 page_section_revisions / page_section_locks",
                "类型清晰、可校验、可缓存、可回滚、可协同；和 CakePHP ORM 完美贴合",
                "—（最终采用）",
            ],
        ],
        col_widths_cm=[2.4, 4.8, 4.6, 4.6],
    )

    _add_paragraph(
        doc,
        "结论：本项目页面数量已成长到 6+ 个公共模板（home、contact、courses 等），"
        "且未来还要做课程详情页、活动页等新增内容，方案一/二在“多人协作 + 富文本 + "
        "图片资产 + 历史回滚”这四件事上都会很快撞墙。方案三虽然多 5 张表，但每张"
        "都有非常明确的职责边界，长远成本最低。",
    )

    # ----------------- 5 表数据模型 -----------------
    _add_heading(doc, "三、五表数据模型", level=1)
    _add_paragraph(doc, "整体由“两条主线 + 三个支撑表”构成：")
    _add_bullets(
        doc,
        [
            "主线 1（内容）：site_pages → page_sections → site_media（区块可指向一张图片）",
            "主线 2（治理）：page_sections → page_section_revisions（每次修改的快照）",
            "支撑表：page_section_locks（谁正在编辑哪个区块）",
        ],
    )

    _add_heading(doc, "3.1 site_pages — 页面注册表", level=2)
    _add_paragraph(doc, "把“页面”当一等公民登记下来，方便后台分组、权限、缓存键。")
    _add_table(
        doc,
        header=["字段", "类型", "说明"],
        rows=[
            ["id", "BIGINT UNSIGNED PK", "主键"],
            ["page_key", "VARCHAR(64) UNIQUE", "稳定标识，如 home / contact / global"],
            ["title", "VARCHAR(255)", "后台展示名（“首页”“联系我们”）"],
            ["description", "TEXT", "后台说明，给运营看，不上前台"],
            ["is_active", "TINYINT(1)", "便于将来灰度上下线"],
            ["created / modified", "DATETIME", "审计时间戳"],
        ],
        col_widths_cm=[3.0, 4.5, 8.5],
    )

    _add_heading(doc, "3.2 page_sections — 类型化内容区块", level=2)
    _add_paragraph(
        doc,
        "整套 CMS 的核心。每一段“可被改”的文案都对应一行 page_sections，"
        "通过 content_type 区分纯文本 / 富文本 / 图片，前端按类型分别处理。",
    )
    _add_table(
        doc,
        header=["字段", "类型", "说明"],
        rows=[
            ["id", "BIGINT UNSIGNED PK", "主键"],
            ["site_page_id", "BIGINT UNSIGNED FK", "属于哪个页面"],
            ["section_key", "VARCHAR(64)", "页面内的稳定标识，如 hero_title"],
            ["label", "VARCHAR(255)", "后台显示名（“首屏大标题”）"],
            ["content_type", "ENUM('text','html','image')", "渲染类型，决定输入控件与净化策略"],
            ["content_value", "LONGTEXT", "text/html 时存内容；image 时为空"],
            ["media_id", "BIGINT UNSIGNED FK NULL", "image 类型时引用 site_media"],
            ["display_order", "INT", "区块在后台列表里的展示顺序"],
            ["is_active", "TINYINT(1)", "下线某区块（前台走 fallback）"],
            ["UNIQUE(site_page_id, section_key)", "—", "保证一个页面内 key 不重复"],
        ],
        col_widths_cm=[4.6, 3.4, 8.0],
    )

    _add_heading(doc, "3.3 site_media — 媒体库", level=2)
    _add_paragraph(
        doc,
        "图片单独建表的核心好处：一次上传、多处引用、能审计、可替换。"
        "Logo 这种全站资源换一次，所有页面立刻同步。",
    )
    _add_table(
        doc,
        header=["字段", "说明"],
        rows=[
            ["id / file_name / file_size / mime_type / width / height", "基本元数据"],
            ["storage_path", "落盘相对路径（webroot/uploads/site/<hash>.<ext>）"],
            ["alt_text", "无障碍可访问性，前台 <img alt=...> 直接用"],
            ["uploaded_by_id", "审计：谁上传的"],
        ],
        col_widths_cm=[6.0, 10.0],
    )

    _add_heading(doc, "3.4 page_section_revisions — 修订历史", level=2)
    _add_paragraph(
        doc,
        "Append-only（只追加，不修改）。每次保存写一行快照，含 content_value_snapshot 与 media_id_snapshot。"
        "有了它就能：看变更、对比版本、一键回滚、追责审计。",
    )

    _add_heading(doc, "3.5 page_section_locks — 协同锁", level=2)
    _add_paragraph(
        doc,
        "软锁（不靠数据库行锁）。一个用户开始编辑就 INSERT 一行，含 expires_at；"
        "心跳每 60 秒续期；放弃编辑就 DELETE。其它用户进入页面时如果锁未过期，"
        "看到“XXX 正在编辑”，可选择“强制接管”，强占会写一条审计记录。",
    )

    # ----------------- 服务层 -----------------
    _add_heading(doc, "四、服务层（src/Service/Cms/）", level=1)
    _add_paragraph(
        doc,
        "把所有“业务规则”从控制器与模板里抽出来，放进 5 个职责单一的 PHP 服务类。"
        "控制器只负责协议（HTTP / 表单 / 跳转），模板只负责显示，业务逻辑都在 Service。",
    )
    _add_table(
        doc,
        header=["服务类", "职责"],
        rows=[
            [
                "HtmlSanitizer",
                "白名单 HTML 净化。只在“渲染时”过滤，存原文，避免反复二次编辑后内容被吃掉。"
                "先 preg_replace 干掉 script/style/iframe/object/embed，再 strip_tags + DOMDocument 走白名单",
            ],
            [
                "ContentResolver",
                "按 page_key 一次性取出整页所需的 sections + media，结果以页面级 bundle 形式缓存到"
                "FileEngine（cms cache config）。任何 section 写入后定向失效该页缓存",
            ],
            [
                "SectionLockService",
                "acquire / heartbeat / release / forceTake；TTL 由配置驱动；"
                "返回 LockOutcome 值对象，告诉调用方是“拿到了 / 被别人占着 / 强占成功”",
            ],
            [
                "RevisionRecorder",
                "在保存前 snapshot 当前内容，写一条 revision；提供 restore(revisionId) 把旧值"
                "重新落到 page_sections，并再写一条新的 revision（保留链路）",
            ],
            [
                "MediaUploader",
                "上传校验：MIME、扩展名、文件大小、magic bytes 三重核对；hash 命名落盘到"
                "webroot/uploads/site/；写 site_media 行；失败抛 MediaUploadException",
            ],
        ],
        col_widths_cm=[3.6, 12.4],
    )

    # ----------------- View Helper -----------------
    _add_heading(doc, "五、CmsHelper — 模板侧的“一行接入”", level=1)
    _add_paragraph(
        doc,
        "面向开发者的全部门面只有 4 个方法，记忆成本几乎为零：",
    )
    _add_bullets(
        doc,
        [
            "$this->Cms->text('home', 'hero_title', '默认值') — 纯文本",
            "$this->Cms->html('home', 'hero_body',  '<p>默认</p>') — 富文本（自动净化）",
            "$this->Cms->image('global', 'logo', '/img/logo.png') — 返回图片 URL",
            "$this->Cms->imageAlt('global', 'logo', 'Logo') — 配套的 alt 文本",
        ],
    )
    _add_paragraph(doc, "三条关键设计原则：")
    _add_numbered(
        doc,
        [
            "fallback 永远存在：CMS 没数据 / 缓存 miss / DB 异常时回落到模板里写的默认值，"
            "永不开天窗。"
            "这让“第一次接 CMS”也只是“顺手把硬编码替换成方法调用”，不存在“接错就白屏”。",
            "{year} 等动态占位符：模板里写“© {year} CandleCraft”，渲染时自动替换为当前年份，"
            "管理员不必每年元旦手动改。",
            "请求级 memoize：一次请求内同一个 page_key 只查一次 ContentResolver，"
            "整页内多个区块共享同一份 bundle，零额外 SQL。",
        ],
    )

    # ----------------- Admin UI/UX -----------------
    _add_heading(doc, "六、Admin 界面与协同体验", level=1)
    _add_paragraph(doc, "后台访问入口：/admin/cms（仅 admin 角色可达，受 Authentication + Authorization 双闸门保护）。")

    _add_heading(doc, "6.1 三层导航结构", level=2)
    _add_bullets(
        doc,
        [
            "页面列表 /admin/cms — 列出所有 site_pages",
            "页面详情 /admin/cms/pages/{page_key} — 展示该页面的所有 sections（按 display_order）",
            "区块编辑 /admin/cms/pages/{page_key}/sections/{section_key}/edit — 真正的编辑表单",
        ],
    )

    _add_heading(doc, "6.2 类型感知的输入控件", level=2)
    _add_bullets(
        doc,
        [
            "text 类型 → 单行 input 或多行 textarea（按内容长度提示）",
            "html 类型 → textarea + 白名单说明（保存时不净化、渲染时净化，避免反复打开后内容退化）",
            "image 类型 → 当前图预览 + “从媒体库选择” + “直接上传新图”",
        ],
    )

    _add_heading(doc, "6.3 协同锁的 UX 闭环", level=2)
    _add_numbered(
        doc,
        [
            "进入编辑页：尝试 acquire；成功则正常编辑，失败则展示“XXX 在编辑（剩余 X 分钟）”，并提供“强制接管”按钮。",
            "编辑过程中：前端每 60 秒发一次心跳请求，续期到 5 分钟。",
            "保存或离开：调用 release，立即让出。",
            "强制接管：调用 forceTake，原持锁人下次心跳/保存会收到“锁已被接管”的提示，避免覆盖丢失。",
            "所有 acquire / forceTake 都进 page_section_locks 与 audit log，能追溯。",
        ],
    )

    _add_heading(doc, "6.4 修订历史与回滚", level=2)
    _add_bullets(
        doc,
        [
            "每个 section 有“History”入口，列出所有 revision（时间 + 操作人 + 类型）",
            "点击某条 revision 可预览快照，也可一键 restore 回滚",
            "回滚不是删除新版本，而是“以旧版本内容再写一条新 revision”，链路完整",
        ],
    )

    _add_heading(doc, "6.5 媒体库", level=2)
    _add_bullets(
        doc,
        [
            "/admin/cms/media — 网格视图列出所有 site_media，可按文件名搜索",
            "上传时自动生成 hash 文件名落盘，避免重名覆盖",
            "支持替换：image 类型 section 可在编辑页直接“换一张图”，前台立刻同步",
        ],
    )

    # ----------------- 公共模板迁移 -----------------
    _add_heading(doc, "七、公共模板的迁移策略", level=1)
    _add_paragraph(
        doc,
        "迁移原则：渐进式、向后兼容。每个硬编码字符串的替换都遵循“原文作为 fallback”的"
        "模式，做到“接 CMS 不破坏现有页面，未接 CMS 的字段维持现状”。",
    )
    _add_paragraph(doc, "迁移示例（templates/Pages/home.php）：")
    _add_paragraph(
        doc,
        "原始硬编码：",
        bold=True,
        size_pt=10,
        color=(0x55, 0x55, 0x55),
    )
    code = doc.add_paragraph()
    code.paragraph_format.left_indent = Cm(0.6)
    run = code.add_run("<h1>Welcome to CandleCraft</h1>")
    run.font.name = MONO_FONT
    run.font.size = Pt(10)
    run.font.color.rgb = RGBColor(0x33, 0x33, 0x33)

    _add_paragraph(
        doc,
        "迁移后：",
        bold=True,
        size_pt=10,
        color=(0x55, 0x55, 0x55),
    )
    code = doc.add_paragraph()
    code.paragraph_format.left_indent = Cm(0.6)
    run = code.add_run(
        "<h1><?= h($this->Cms->text('home', 'hero_title', 'Welcome to CandleCraft')) ?></h1>"
    )
    run.font.name = MONO_FONT
    run.font.size = Pt(10)
    run.font.color.rgb = RGBColor(0x33, 0x33, 0x33)

    _add_paragraph(
        doc,
        "已完成迁移的模板：home.php、contact.php、courses/index.php 以及"
        "element/public_nav.php（Logo + 品牌名 + 导航菜单文案），覆盖了现网"
        "管理员高频改动的所有触点。",
    )

    # ----------------- 安全 / 缓存 / 性能 -----------------
    _add_heading(doc, "八、安全、缓存与性能", level=1)
    _add_table(
        doc,
        header=["维度", "措施"],
        rows=[
            ["权限", "/admin/cms 全部走 admin 中间件 + Authorization 策略，普通用户访问 403"],
            ["CSRF / Form 安全令牌", "复用 CakePHP 默认中间件，所有写操作必须带 token"],
            [
                "HTML 注入",
                "存储原文 + 渲染时白名单净化（HtmlSanitizer），先 preg_replace 干净 script/style/iframe，再 DOM 走白名单",
            ],
            [
                "上传安全",
                "MediaUploader 三重校验：扩展名 + MIME + magic bytes；超大文件直接拒绝；hash 命名防覆盖",
            ],
            [
                "缓存",
                "FileEngine cache config 名为 cms；按 page_key 缓存整页 bundle；任何 section 写入后定向 invalidate 该页",
            ],
            [
                "性能",
                "请求级 memoize + 页面级 cache，常态下渲染零数据库查询；多 section 共享同一次 IO",
            ],
            [
                "可观测",
                "锁的 acquire / forceTake 全量审计；revision 记录每次内容变化；上传记录 uploaded_by_id",
            ],
        ],
        col_widths_cm=[3.4, 12.6],
    )

    # ----------------- 测试 -----------------
    _add_heading(doc, "九、TDD 与测试覆盖", level=1)
    _add_paragraph(doc, "整套 CMS 严格按 TDD 推进，22 个任务每一个都先写测试再写实现。覆盖：")
    _add_bullets(
        doc,
        [
            "单元：5 个 Service 各自的成功 / 失败 / 边界用例",
            "View Helper：fallback、净化、{year} 占位、缓存命中三态",
            "控制器集成：登录、CSRF、权限、表单提交、上传、回滚、强占",
            "Fixture：5 张表均有种子数据，跨用例隔离（必要处显式 Cache::drop('cms')）",
        ],
    )
    _add_paragraph(
        doc,
        "全量回归：327 tests / 0 failures / 0 errors（除 6 个跨模块预先标注 incomplete 的用例外）。"
        "这套测试既是质量护栏，也作为文档让后来者知道每个服务“应该长什么样”。",
    )

    # ----------------- 扩展性 -----------------
    _add_heading(doc, "十、未来可扩展点", level=1)
    _add_bullets(
        doc,
        [
            "草稿 / 发布工作流：page_sections 增加 status 列，再加 publish 动作即可（数据模型本身已留位）",
            "多语言：page_sections 加 locale 列，配合 (site_page_id, section_key, locale) 唯一索引",
            "更多类型：在 ENUM 中加 link / video / json，CmsHelper 再开一个对应方法",
            "前台所见即所得：未来叠加可视化编辑器，只需把现有 admin 表单换成 inline 控件，后端 API 完全复用",
            "导入导出：page_sections + revisions + media 是结构化的，整页打包/迁移到其它环境很容易",
        ],
    )

    # ----------------- 收尾 -----------------
    _add_heading(doc, "十一、为什么这套方案最适合本项目", level=1)
    _add_numbered(
        doc,
        [
            "贴合 CakePHP：5 张表 ↔ 5 个 Table/Entity，ORM 关系自然，开发者不需要学新框架",
            "渐进式接入：模板迁移走 fallback 模式，可以一段一段接，永远不破坏现有页面",
            "对管理员“无门槛”：后台分页面、分区块、按类型给控件，操作链路与他们日常使用 WordPress / 飞书文档同构",
            "对开发者“一行调用”：$this->Cms->text(...) 就是全部门面，加新区块的边际成本接近 0",
            "对运维“可治理”：缓存可清、版本可回、上传可审、锁可强占，所有“出问题怎么办”都有内置答案",
            "测试完备：所有服务 / 帮助器 / 关键路径都有测试，未来重构有底气",
        ],
    )

    doc.save(OUTPUT_PATH)
    print(f"Wrote {OUTPUT_PATH}")


if __name__ == "__main__":
    build()
