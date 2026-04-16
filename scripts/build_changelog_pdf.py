#!/usr/bin/env python3
"""Build styled CHANGELOG.html from CHANGELOG.md and export CHANGELOG.pdf via Chrome headless."""

from __future__ import annotations

import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
MD = ROOT / "CHANGELOG.md"
HTML_OUT = ROOT / "CHANGELOG.html"
PDF_OUT = ROOT / "CHANGELOG.pdf"

CHROME = "/Applications/Google Chrome.app/Contents/MacOS/Google Chrome"

HTML_TEMPLATE = """<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Changelog — CandleCraft Academy</title>
  <style>
    :root {
      --bg: #f4f2ee;
      --paper: #fffcf7;
      --ink: #1c1917;
      --muted: #57534e;
      --accent: #b45309;
      --accent-soft: rgba(180, 83, 9, 0.12);
      --rule: #e7e5e4;
      --code-bg: #f5f5f4;
      --shadow: 0 22px 50px -12px rgba(28, 25, 23, 0.12);
    }
    * { box-sizing: border-box; }
    html { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    body {
      margin: 0;
      min-height: 100vh;
      font-family: "Iowan Old Style", "Palatino Linotype", Palatino, "Book Antiqua", Georgia, serif;
      font-size: 11.2pt;
      line-height: 1.55;
      color: var(--ink);
      background: linear-gradient(165deg, #e7e5e4 0%, var(--bg) 38%, #fafaf9 100%);
    }
    .sheet {
      max-width: 44rem;
      margin: 2.5rem auto 3rem;
      padding: 2.75rem 3rem 3.25rem;
      background: var(--paper);
      border-radius: 14px;
      border: 1px solid var(--rule);
      box-shadow: var(--shadow);
    }
    @media (max-width: 640px) {
      .sheet { margin: 1rem; padding: 1.5rem 1.35rem 2rem; border-radius: 10px; }
    }
    .mast {
      padding-bottom: 1.75rem;
      margin-bottom: 2rem;
      border-bottom: 3px solid var(--accent);
      background: linear-gradient(90deg, var(--accent-soft) 0%, transparent 55%);
    }
    .mast-kicker {
      font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
      font-size: 0.72rem;
      letter-spacing: 0.22em;
      text-transform: uppercase;
      color: var(--accent);
      margin: 0 0 0.5rem;
      font-weight: 600;
    }
    .mast h1 {
      font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
      font-size: 1.85rem;
      font-weight: 700;
      letter-spacing: -0.02em;
      margin: 0;
      line-height: 1.2;
      color: var(--ink);
    }
    .mast-meta {
      margin: 0.65rem 0 0;
      font-size: 0.92rem;
      color: var(--muted);
      font-style: italic;
    }
    .content h1 { display: none; }
    .content h2 {
      font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
      font-size: 1.15rem;
      font-weight: 700;
      margin: 2rem 0 0.85rem;
      padding: 0.35rem 0 0.35rem 0.85rem;
      border-left: 4px solid var(--accent);
      background: linear-gradient(90deg, var(--accent-soft), transparent);
      color: var(--ink);
      letter-spacing: -0.01em;
    }
    .content h2:first-of-type { margin-top: 0; }
    .content h3 {
      font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
      font-size: 0.98rem;
      font-weight: 650;
      margin: 1.35rem 0 0.55rem;
      color: #292524;
    }
    .content ul {
      margin: 0.35rem 0 0.85rem;
      padding-left: 1.35rem;
    }
    .content li {
      margin: 0.28rem 0;
      padding-left: 0.15rem;
    }
    .content li::marker { color: var(--accent); }
    .content code {
      font-family: ui-monospace, "Cascadia Code", "SF Mono", Menlo, Monaco, monospace;
      font-size: 0.88em;
      background: var(--code-bg);
      padding: 0.12em 0.38em;
      border-radius: 4px;
      border: 1px solid #e7e5e4;
      color: #44403c;
    }
    @page {
      size: A4;
      margin: 18mm 16mm 20mm;
    }
    @media print {
      body {
        background: #fff;
        font-size: 10.5pt;
      }
      .sheet {
        margin: 0;
        padding: 0;
        border: none;
        border-radius: 0;
        box-shadow: none;
        max-width: none;
      }
      .mast {
        padding-bottom: 1.25rem;
        margin-bottom: 1.5rem;
      }
    }
  </style>
</head>
<body>
  <article class="sheet">
    <header class="mast">
      <p class="mast-kicker">Release notes</p>
      <h1>Changelog</h1>
      <p class="mast-meta">CandleCraft Academy platform</p>
    </header>
    <div class="content">
{body}
    </div>
  </article>
</body>
</html>
"""


def main() -> int:
    if not MD.is_file():
        print(f"Missing {MD}", file=sys.stderr)
        return 1
    proc = subprocess.run(
        [
            "pandoc",
            str(MD),
            "-t",
            "html5",
            "--syntax-highlighting=none",
        ],
        cwd=str(ROOT),
        capture_output=True,
        text=True,
        check=False,
    )
    if proc.returncode != 0:
        print(proc.stderr or proc.stdout, file=sys.stderr)
        return proc.returncode
    body = proc.stdout.strip()
    html = HTML_TEMPLATE.replace("{body}", body)
    HTML_OUT.write_text(html, encoding="utf-8")
    print(f"Wrote {HTML_OUT}")

    chrome = Path(CHROME)
    if not chrome.is_file():
        print("Chrome not found; HTML only.", file=sys.stderr)
        return 0

    html_url = HTML_OUT.as_uri()
    pdf_proc = subprocess.run(
        [
            str(chrome),
            "--headless=new",
            "--disable-gpu",
            "--no-pdf-header-footer",
            f"--print-to-pdf={PDF_OUT}",
            html_url,
        ],
        capture_output=True,
        text=True,
    )
    if pdf_proc.returncode != 0:
        print(pdf_proc.stderr or pdf_proc.stdout, file=sys.stderr)
        return pdf_proc.returncode
    if not PDF_OUT.is_file():
        print("PDF was not created.", file=sys.stderr)
        return 1
    print(f"Wrote {PDF_OUT}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
