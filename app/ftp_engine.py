# -*- coding: utf-8 -*-
"""
Motor Híbrido FTP DataSUS + Agrupamento de Famílias, UF e Histórico
Conecta diretamente via FTP passivo (ftplib) aos 3 clusters do DataSUS
(arpoador.datasus.gov.br, ftp.datasus.gov.br, ftp2.datasus.gov.br)
com fallback HTTPS transparente e cache inteligente em memória.
"""

import ftplib
import re
import time
from datetime import datetime, timezone
from typing import Any, Dict, Generator, List, Optional, Tuple
from urllib.parse import urlparse, quote
import httpx

from app.catalog import UF_CODE_MAP

UF_SIGLAS = sorted(UF_CODE_MAP.values())
YM_REGEX = re.compile(r"(?<![0-9])20[0-3][0-9]_?[01][0-9](?![0-9])")

MONTH_NAMES = {
    "01": "janeiro", "02": "fevereiro", "03": "março", "04": "abril",
    "05": "maio", "06": "junho", "07": "julho", "08": "agosto",
    "09": "setembro", "10": "outubro", "11": "novembro", "12": "dezembro"
}

DUAL_DISPLAY_FAMILIES = {"BDSIA", "EQUIPESESFBRASIL"}

# Cache em memória: {ftp_url: (timestamp, list_of_entries)}
_FTP_CACHE: Dict[str, Tuple[float, List[Dict[str, Any]]]] = {}
CACHE_TTL_SECONDS = 600  # 10 minutos


def _parse_ftp_url(ftp_url: str) -> Tuple[str, str]:
    parsed = urlparse(ftp_url)
    host = parsed.hostname or ""
    path = parsed.path or "/"
    if not path.endswith("/"):
        path += "/"
    return host, path


def _parse_dos_or_unix_line(line: str) -> Optional[Dict[str, Any]]:
    """Analisa linhas retornadas pelo comando LIST de servidores FTP Windows IIS ou Unix do DataSUS."""
    line = line.strip()
    if not line or line.startswith("total "):
        return None

    # Formato Windows IIS do DataSUS: "10-15-26  02:30PM       14582912 BDSIA202610.exe"
    m_dos = re.match(
        r"^(\d{2})-(\d{2})-(\d{2,4})\s+(\d{1,2}):(\d{2})(AM|PM)\s+(<DIR>|\d+)\s+(.+)$",
        line,
        re.IGNORECASE,
    )
    if m_dos:
        mm, dd, yy, hh, minu, ampm, size_or_dir, name = m_dos.groups()
        if size_or_dir.upper() == "<DIR>":
            return None
        year = int(yy)
        if year < 100:
            year += 2000 if year < 70 else 1900
        hour = int(hh) % 12
        if ampm.upper() == "PM":
            hour += 12
        try:
            dt = datetime(year, int(mm), int(dd), hour, int(minu), tzinfo=timezone.utc)
            iso_dt = dt.isoformat()
        except ValueError:
            iso_dt = datetime.now(timezone.utc).isoformat()
        return {
            "name": name.strip(),
            "size": int(size_or_dir),
            "modifiedAt": iso_dt,
        }

    # Formato Unix: "-rw-r--r-- 1 ftp ftp 12345 Oct 15 14:20 filename.zip"
    parts = line.split(None, 8)
    if len(parts) >= 9 and not parts[0].startswith("d"):
        try:
            size = int(parts[4])
        except ValueError:
            return None
        name = parts[8].strip()
        return {
            "name": name,
            "size": size,
            "modifiedAt": datetime.now(timezone.utc).isoformat(),
        }
    return None


def fetch_raw_ftp_list(ftp_url: str, force_refresh: bool = False) -> Tuple[List[Dict[str, Any]], str]:
    """
    Retorna (entries, source_mode) onde source_mode é 'CACHE', 'FTP_DIRECT' ou 'HTTPS_MIRROR'.
    """
    now = time.time()
    if not force_refresh and ftp_url in _FTP_CACHE:
        ts, cached_data = _FTP_CACHE[ftp_url]
        if now - ts < CACHE_TTL_SECONDS:
            return cached_data, "CACHE"

    # 1. Tentativa via FTP Passivo Direto (porta 21)
    host, path = _parse_ftp_url(ftp_url)
    if host:
        try:
            lines: List[str] = []
            with ftplib.FTP(timeout=8) as ftp:
                ftp.connect(host, 21, timeout=8)
                ftp.login("anonymous", "anonymous@datasus.gov.br")
                ftp.set_pasv(True)
                ftp.encoding = "latin-1"
                ftp.cwd(path)
                ftp.retrlines("LIST", lines.append)
            entries = []
            for ln in lines:
                parsed = _parse_dos_or_unix_line(ln)
                if parsed:
                    entries.append(parsed)
            if entries:
                _FTP_CACHE[ftp_url] = (now, entries)
                return entries, "FTP_DIRECT"
        except Exception:
            pass

    # 2. Fallback HTTPS Mirror (para redes que bloqueiam porta 21 ou diretórios espelhados)
    try:
        mirror_url = f"https://downloadsus.vercel.app/api/ftp-list?url={quote(ftp_url, safe='')}"
        with httpx.Client(timeout=12.0, follow_redirects=True) as client:
            resp = client.get(mirror_url)
            if resp.status_code == 200:
                data = resp.json()
                if isinstance(data, list):
                    _FTP_CACHE[ftp_url] = (now, data)
                    return data, "HTTPS_BRIDGE"
    except Exception:
        pass

    return [], "OFFLINE"


def detect_uf_sigla(filename: str) -> Optional[str]:
    for uf in UF_SIGLAS:
        if re.search(rf"_{uf}[_.]", filename, re.IGNORECASE) or re.search(rf"_{uf}\d{{6}}", filename, re.IGNORECASE):
            return uf
    return None


def detect_uf_terceiros(filename: str) -> Optional[str]:
    m = re.match(r"^TERCEIROSBRASIL(\d{2})_", filename, re.IGNORECASE)
    if m and m.group(1) in UF_CODE_MAP:
        return UF_CODE_MAP[m.group(1)]
    return None


def detect_uf_dsihd017(filename: str) -> Optional[str]:
    m = re.match(r"^DSIHD017_(\d{2})_", filename, re.IGNORECASE)
    if m and m.group(1) in UF_CODE_MAP:
        return UF_CODE_MAP[m.group(1)]
    return None


def _version_weight(ver_str: str) -> int:
    nums = re.findall(r"\d+", ver_str)
    if not nums:
        return 0
    total = 0
    for idx, n in enumerate(nums[:5]):
        total += int(n) * (1000 ** (4 - idx))
    return total


def detect_special_version(filename: str) -> Optional[Dict[str, Any]]:
    dot_idx = filename.rfind(".")
    stem = filename[:dot_idx] if dot_idx >= 0 else filename

    patterns = [
        (r"^AIHV(\d+)$", "AIHV", lambda m: (int(m.group(1)), f"v{m.group(1)}", None)),
        (r"^SIHD2_(.+)$", "SIHD2_", lambda m: (_version_weight(m.group(1)), m.group(1), None)),
        (r"^SIHDTS_(.+)$", "SIHDTS_", lambda m: (_version_weight(m.group(1)), m.group(1), None)),
        (r"^SISAIH01_VER(.+)$", "SISAIH01_VER", lambda m: (_version_weight(m.group(1)), m.group(1), None)),
        (r"^APACMAG_(.+)$", "APACMAG_", lambda m: (_version_weight(m.group(1)), m.group(1), None)),
        (r"^BPAMAG(.+)$", "BPAMAG", lambda m: (_version_weight(m.group(1)), m.group(1), None)),
        (r"^EQUIPESESFBRASIL(.+)$", "EQUIPESESFBRASIL", lambda m: (_version_weight(m.group(1)), m.group(1), None)),
        (r"^BDSIA(.+)$", "BDSIA", lambda m: (_version_weight(m.group(1)), m.group(1), None)),
        (r"^SIA(.+)$", "SIA", lambda m: (_version_weight(m.group(1)), m.group(1), None)),
        (r"^FPOMAG_ATUALIZA(.+)$", "FPOMAG_ATUALIZA", lambda m: (_version_weight(m.group(1)), m.group(1), None)),
        (r"^SIHD(\d{3})(?:_|$)", "SIHD_NUMERIC", lambda m: (int(m.group(1)), m.group(1), "(SIHD1)")),
    ]
    for pat, fam_key, builder in patterns:
        m = re.match(pat, stem, re.IGNORECASE)
        if m:
            order, label, suffix = builder(m)
            res: Dict[str, Any] = {"familyKey": fam_key, "order": order, "label": label}
            if suffix:
                res["displaySuffix"] = suffix
            return res

    m_scnes = re.match(r"^SCNES(\d{4})-(ATUALIZACAO|COMPLETA)$", stem, re.IGNORECASE)
    if m_scnes:
        kind = "ATUALIZACAO" if m_scnes.group(2).upper() == "ATUALIZACAO" else "COMPLETA"
        return {
            "familyKey": f"SCNES_APP_{kind}",
            "order": int(m_scnes.group(1)),
            "label": m_scnes.group(1),
        }
    return None


def _compute_family_key(filename: str) -> str:
    sp = detect_special_version(filename)
    if sp:
        return sp["familyKey"]
    up = filename.upper()
    m = YM_REGEX.search(up)
    prefix = up[: m.end()] if m else up
    key = YM_REGEX.sub("YYYYMM", prefix)
    for uf in UF_SIGLAS:
        key = re.sub(rf"_{uf}(?=_|\d)", "_UF", key, flags=re.IGNORECASE)
    key = re.sub(r"^(TERCEIROSBRASIL)\d{2}", r"\1UF", key)
    key = re.sub(r"^(DSIHD017_)\d{2}", r"\1UF", key, flags=re.IGNORECASE)
    return key


def _sort_family_files(files: List[Dict[str, Any]]) -> List[Dict[str, Any]]:
    def sort_key(f: Dict[str, Any]):
        sp = f.get("specialVersion")
        sp_order = sp["order"] if sp else -1
        ym = f.get("ym") or ""
        mod = f.get("modifiedAt") or ""
        return (sp_order, ym, mod)

    return sorted(files, key=sort_key, reverse=True)


def _match_description(filename: str, rules: Optional[List[Dict[str, str]]]) -> str:
    if not rules:
        return "–"
    for r in rules:
        sw = r.get("startsWith")
        rx = r.get("regex")
        if sw and filename.upper().startswith(sw.upper()):
            return r.get("description", "–")
        if rx and re.search(rx, filename, re.IGNORECASE):
            return r.get("description", "–")
    return "–"


def process_and_group_files(
    raw_files: List[Dict[str, Any]],
    include_prefixes: Optional[List[str]] = None,
    include_regexes: Optional[List[str]] = None,
    exclude_prefixes: Optional[List[str]] = None,
    exclude_regexes: Optional[List[str]] = None,
    description_rules: Optional[List[Dict[str, str]]] = None,
) -> List[Dict[str, Any]]:
    """Filtra, classifica por família/UF e constrói o histórico de versões ('Anteriores')."""
    filtered = []
    has_inc = bool(include_prefixes or include_regexes)
    for item in raw_files:
        name = item.get("name", "")
        if not name:
            continue
        if has_inc:
            matched_inc = False
            if include_prefixes and any(name.upper().startswith(p.upper()) for p in include_prefixes):
                matched_inc = True
            if include_regexes and any(re.search(rx, name, re.IGNORECASE) for rx in include_regexes):
                matched_inc = True
            if not matched_inc:
                continue
        if exclude_prefixes and any(name.upper().startswith(p.upper()) for p in exclude_prefixes):
            continue
        if exclude_regexes and any(re.search(rx, name, re.IGNORECASE) for rx in exclude_regexes):
            continue
        filtered.append(item)

    families: Dict[str, List[Dict[str, Any]]] = {}
    for item in filtered:
        name = item["name"]
        m_ym = YM_REGEX.search(name)
        ym = m_ym.group(0).replace("_", "") if m_ym else None

        if detect_uf_dsihd017(name):
            kind = "dsihd017"
        elif detect_uf_terceiros(name):
            kind = "uf_cod"
        elif detect_uf_sigla(name):
            kind = "uf_sigla"
        elif ym:
            kind = "versioned"
        else:
            kind = "normal"

        sp = detect_special_version(name)
        fam_key = _compute_family_key(name)
        families.setdefault(fam_key, []).append({
            **item,
            "ym": ym,
            "kind": kind,
            "specialVersion": sp,
        })

    output: List[Dict[str, Any]] = []
    for fam_key, fam_files in families.items():
        if not fam_files:
            continue
        sorted_files = _sort_family_files(fam_files)
        top = sorted_files[0]
        sp_key = top["specialVersion"]["familyKey"] if top.get("specialVersion") else None
        is_dual = sp_key in DUAL_DISPLAY_FAMILIES
        display_count = min(2, len(sorted_files)) if is_dual else 1

        history = [
            {
                "name": f["name"],
                "ym": f.get("ym"),
                "label": (f["specialVersion"]["label"] if f.get("specialVersion") else None)
                or (f"{f['ym'][:4]}/{f['ym'][4:]}" if f.get("ym") else f["name"]),
                "size": f.get("size", 0),
                "modifiedAt": f.get("modifiedAt"),
            }
            for f in sorted_files[display_count:]
            if f.get("ym") or f.get("specialVersion")
        ]

        uf_kind = top["kind"] if top["kind"] in ("uf_sigla", "uf_cod", "dsihd017") else None
        uf_by_sigla: Dict[str, Any] = {}
        uf_history_by_sigla: Dict[str, List[Any]] = {}

        if uf_kind:
            by_uf_list: Dict[str, List[Dict[str, Any]]] = {}
            for f in fam_files:
                fname = f["name"]
                uf = (
                    detect_uf_dsihd017(fname)
                    if uf_kind == "dsihd017"
                    else detect_uf_terceiros(fname)
                    if uf_kind == "uf_cod"
                    else detect_uf_sigla(fname)
                )
                if not uf:
                    continue
                by_uf_list.setdefault(uf, []).append(f)

            for uf, uf_items in by_uf_list.items():
                uf_sorted = _sort_family_files(uf_items)
                best = uf_sorted[0]
                uf_by_sigla[uf] = {
                    "name": best["name"],
                    "size": best.get("size", 0),
                    "modifiedAt": best.get("modifiedAt"),
                    "ym": best.get("ym"),
                }
                uf_hist = [
                    {
                        "name": x["name"],
                        "ym": x.get("ym"),
                        "label": f"{x['ym'][:4]}/{x['ym'][4:]}" if x.get("ym") else x["name"],
                    }
                    for x in uf_sorted[1:]
                    if x.get("ym")
                ]
                if uf_hist:
                    uf_history_by_sigla[uf] = uf_hist

        for idx in range(display_count):
            cur = sorted_files[idx]
            cname = cur["name"]
            if uf_kind:
                dot = cname.rfind(".")
                stem = cname[:dot] if dot >= 0 else cname
                ext = cname[dot:] if dot >= 0 else ""
                m1 = re.match(r"^(TERCEIROSBRASIL)(\d{2})(_\d{6})$", stem, re.IGNORECASE)
                m2 = re.match(r"^(DSIHD017_)(\d{2})(_\d{6})$", stem, re.IGNORECASE)
                if m1:
                    disp_name = f"{m1.group(1)}UF{m1.group(3)}{ext} (selecionar UF)"
                elif m2:
                    disp_name = f"{m2.group(1)}UF{m2.group(3)}{ext} (selecionar UF)"
                else:
                    replaced = stem
                    for uf in UF_SIGLAS:
                        replaced = re.sub(rf"_{uf}(?=_|\d{{6}})", "_UF", replaced, flags=re.IGNORECASE)
                    disp_name = f"{replaced}{ext} (selecionar UF)" if replaced != stem else cname
            elif is_dual:
                m_month = re.search(r"\d{4}(\d{2})", cname)
                m_name = MONTH_NAMES.get(m_month.group(1)) if m_month else None
                disp_name = f"{cname} ({m_name})" if m_name else cname
            else:
                suffix = cur["specialVersion"].get("displaySuffix") if cur.get("specialVersion") else None
                disp_name = f"{cname} {suffix}" if suffix else cname

            output.append({
                "name": cname,
                "displayName": disp_name,
                "description": _match_description(cname, description_rules),
                "size": cur.get("size", 0),
                "modifiedAt": cur.get("modifiedAt"),
                "ym": cur.get("ym"),
                "kind": cur["kind"],
                "history": [] if uf_kind else (history if idx == 0 else []),
                "ufBySigla": uf_by_sigla if (uf_kind and idx == 0) else None,
                "ufHistoryBySigla": uf_history_by_sigla if (uf_kind and idx == 0) else None,
            })

    output.sort(key=lambda x: x["name"].lower())
    return output


def stream_ftp_file(base_ftp_url: str, filename: str) -> Tuple[Generator[bytes, None, None], int, str]:
    """
    Faz streaming binário direto do FTP do DataSUS (ou via proxy HTTP fallback).
    Retorna (generator, content_length, source_mode).
    """
    host, path = _parse_ftp_url(base_ftp_url)
    safe_file = filename.strip().lstrip("/")

    # Tenta stream direto via ftplib
    try:
        ftp = ftplib.FTP(timeout=15)
        ftp.connect(host, 21, timeout=15)
        ftp.login("anonymous", "anonymous@datasus.gov.br")
        ftp.set_pasv(True)
        ftp.encoding = "latin-1"
        ftp.cwd(path)
        try:
            ftp.voidcmd("TYPE I")
            size = ftp.size(safe_file) or 0
        except Exception:
            size = 0

        conn = ftp.transfercmd(f"RETR {safe_file}")

        def _ftp_gen() -> Generator[bytes, None, None]:
            try:
                while True:
                    chunk = conn.recv(65536)
                    if not chunk:
                        break
                    yield chunk
            finally:
                try:
                    conn.close()
                except Exception:
                    pass
                try:
                    ftp.close()
                except Exception:
                    pass

        return _ftp_gen(), size, "FTP_DIRECT_STREAM"
    except Exception:
        pass

    # Fallback para proxy HTTP caso a porta 21 esteja bloqueada
    proxy_url = f"https://api.laundos.com.br/api/ftp-download?base={quote(base_ftp_url, safe='')}&file={quote(safe_file, safe='')}"
    client = httpx.Client(timeout=60.0, follow_redirects=True)
    req = client.build_request("GET", proxy_url)
    resp = client.send(req, stream=True)
    size = int(resp.headers.get("content-length", "0") or 0)

    def _http_gen() -> Generator[bytes, None, None]:
        try:
            for chunk in resp.iter_bytes(chunk_size=65536):
                yield chunk
        finally:
            resp.close()
            client.close()

    return _http_gen(), size, "HTTPS_STREAM_BRIDGE"


def fetch_remessas_sia(ibge: str, ano: str) -> Dict[str, Any]:
    """Consulta as remessas SIA (LogCarga) por município (6 dígitos IBGE) e ano."""
    ibge6 = ibge.strip()[:6]
    try:
        url = f"https://downloadsus.vercel.app/api/remessa?ibge={quote(ibge6)}&ano={quote(str(ano))}"
        with httpx.Client(timeout=15.0, follow_redirects=True) as client:
            r = client.get(url)
            if r.status_code == 200:
                return r.json()
    except Exception as exc:
        return {"error": f"Falha ao consultar LogCarga: {exc}", "rows": [], "ftpUrl": "ftp://arpoador.datasus.gov.br/siasus/LogCarga/"}
    return {"rows": [], "ftpUrl": "ftp://arpoador.datasus.gov.br/siasus/LogCarga/"}
