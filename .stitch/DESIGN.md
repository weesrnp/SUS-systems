# Design System Specification — DownloadSUS Pro (`.stitch/DESIGN.md`)

## 1. Visual World & Mode
- **Surface Mode:** `Operate` (High-density public health operations, billing, surveillance, and municipal governance hub).
- **Aesthetic Philosophy:** Precision Civic Engineering — inspired by Swiss typographic grids and modern developer consoles (Linear / Vercel / Stripe Docs), customized for the Brazilian Unified Health System (SUS / DataSUS).
- **Theme Support:** Dual-theme (`Light Clinical Paper` default + `Dark NOC Slate` toggleable in top bar).

## 2. Color Tokens
| Token | Light Mode | Dark Mode | Semantic Role |
| :--- | :--- | :--- | :--- |
| `--bg-canvas` | `#F6F7F9` | `#0B0F17` | Primary app background |
| `--bg-surface` | `#FFFFFF` | `#111827` | Tables, panels, active workspace cards |
| `--bg-subtle` | `#EEF1F6` | `#1E293B` | Sidebar, filter bars, secondary headers |
| `--text-primary` | `#0F172A` | `#F8FAFC` | Headings, file names, primary data |
| `--text-secondary` | `#334155` | `#CBD5E1` | Descriptions, metadata, table cells |
| `--text-muted` | `#64748B` | `#94A3B8` | FTP URLs, timestamps, technical hints |
| `--border-hairline` | `#E2E8F0` | `#1E293B` | 1px structural borders |
| `--border-strong` | `#CBD5E1` | `#334155` | Input borders, active row dividers |
| `--accent-primary` | `#0284C7` | `#38BDF8` | Primary SUS Blue — active states, primary CTAs |
| `--accent-emerald` | `#059669` | `#34D399` | Vigilância / Fresh release (<24h) / Approved |
| `--accent-amber` | `#D97706` | `#FBBF24` | Portaria deadlines / Warning / Pending |
| `--accent-crimson` | `#DC2626` | `#F87171` | Rejected Remessa / Suspension Risk |

## 3. Typography & Hierarchy
- **UI & Prose (`--font-sans`):** `'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif` — Fixed rem scale (`0.75rem`, `0.8125rem`, `0.875rem`, `1rem`, `1.25rem`, `1.5rem`).
- **Technical Data (`--font-mono`):** `'JetBrains Mono', monospace` — Strictly for FTP paths, SIGTAP 10-digit codes (`03.01.01.007-2`), IBGE codes (`410965`), file sizes, and hashes. `font-variant-numeric: tabular-nums` enforced on all tables.

## 4. Layout Structure (Stitch Specification)
1. **Top Command Bar (`56px` sticky):**
   - Brand emblem (`SUS // DownloadPro v2.0`) + Active Municipality Selector (`Honório Serpa - PR · IBGE 410965`).
   - Segmented Top Navigation switching between the **4 Core Workspaces**:
     1. `Catálogo Geral (FTP & Portarias)`
     2. `Painel Municipal & Portaria 7.639`
     3. `SIGTAP Explorer & Diff`
     4. `Remessas SIA (LogCarga)`
   - Global Command Search (`Ctrl+K`), Cache Refresh trigger, and Theme Toggle.
2. **Categorized Left Navigation Sidebar (`280px` collapsible):**
   - Grouped by **6 Macro-Areas of the SUS**:
     - **01. Faturamento & Produção (SAES)** (`SIA`, `SIH`, `CIHA`, `AUTORIZADOR`)
     - **02. Vigilâncias em Saúde (SVSA & ANVISA)** (`Vigilância Epidemiológica`, `Vigilância Sanitária`, `Vigilância Ambiental & Zoonoses`, `Imunização & PNI`, `Estatísticas Vitais SIM/SINASC`, `Portarias & Manuais SVSA`)
     - **03. Atenção Primária (SAPS / e-SUS)** (`e-SUS PEC Instaladores`, `LEDI & Apache Thrift`, `Apps Mobile APS`, `Portarias & Notas Técnicas SAPS`)
     - **04. Cadastros & Tabelas Nacionais** (`SIGTAP`, `CNES`, `CadSUS & Cartão SUS`, `TUSS / CIAP-2 / CID-10`)
     - **05. TabWin, BI & Microdados (.DBC)** (`TabWin & Mapas`, `Microdados SIA/SIH`, `Microdados SIM/SINASC/SINAN`, `Microdados CNES/PNI`)
     - **06. Legislação, Portarias & Gestão** (`Portaria GM/MS 7.639/2025`, `Portaria GM/MS 3.493/2024 Cofinanciamento`, `Instrumentos DigiSUS / PlanejaSUS`, `Teto MAC & SISMAC`)
3. **Primary Workspace Canvas:**
   - Breadcrumb header + Direct FTP/Gov source badge + Quick Filter Pills + High-density tabular list with UF selector, version history popover (`Anteriores`), normatives/portarias cards, and direct streaming download.
4. **Institutional Footer:**
   - Live status of the 3 DataSUS FTP clusters (`arpoador`, `ftp`, `ftp2`), cache telemetry, and quick links to official MS portals.
