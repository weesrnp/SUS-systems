# -*- coding: utf-8 -*-
"""
Motor SIGTAP Explorer & Diff Automático de Competências
Lê pacotes TabelaUnificada_YYYYMM_*.zip diretamente do FTP do DataSUS,
decodifica o layout posicional oficial (tb_procedimento.txt, tb_grupo.txt, tb_financiamento.txt)
e gera busca instantânea + Diff entre duas competências.
"""

import io
import re
import zipfile
from typing import Any, Dict, List, Optional
from urllib.parse import quote
import httpx

from app.ftp_engine import fetch_raw_ftp_list, stream_ftp_file

SIGTAP_FTP_URL = "ftp://ftp2.datasus.gov.br/pub/sistemas/tup/downloads/"

# Cache em memória das competências SIGTAP já descompactadas: { "202610": { "file": "...", "procs": { co_proc: {...} } } }
_SIGTAP_PARSED_CACHE: Dict[str, Dict[str, Any]] = {}

COMPLEXIDADE_MAP = {
    "0": "Não se aplica",
    "1": "Atenção Básica (APS)",
    "2": "Média Complexidade (MC)",
    "3": "Alta Complexidade (AC)",
}

# Fallback com os nomes oficiais de tb_financiamento.txt (o ZIP da competência sobrescreve)
FINANCIAMENTO_DEFAULT = {
    "01": "Atenção Básica (PAB)",
    "02": "Assistência Farmacêutica",
    "04": "Fundo de Ações Estratégicas e Compensações (FAEC)",
    "05": "Incentivo - MAC",
    "06": "Média e Alta Complexidade (MAC)",
    "07": "Vigilância em Saúde",
    "08": "Gestão do SUS",
}


def list_sigtap_competencias(force_refresh: bool = False) -> List[Dict[str, Any]]:
    """Lista todas as competências TabelaUnificada_YYYYMM disponíveis no FTP do DataSUS."""
    raw_files, _ = fetch_raw_ftp_list(SIGTAP_FTP_URL, force_refresh=force_refresh)
    by_ym: Dict[str, Dict[str, Any]] = {}
    for f in raw_files:
        name = f.get("name", "")
        m = re.match(r"^TabelaUnificada_(\d{6}).*\.zip$", name, re.IGNORECASE)
        if not m:
            continue
        ym = m.group(1)
        prev = by_ym.get(ym)
        if not prev or (f.get("modifiedAt", "") > prev.get("modifiedAt", "")):
            by_ym[ym] = {
                "competencia": ym,
                "label": f"{ym[:4]}/{ym[4:]}",
                "file": name,
                "size": f.get("size", 0),
                "modifiedAt": f.get("modifiedAt"),
            }
    return sorted(by_ym.values(), key=lambda x: x["competencia"], reverse=True)


def _format_proc_code(code10: str) -> str:
    c = code10.strip().zfill(10)
    return f"{c[0:2]}.{c[2:4]}.{c[4:6]}.{c[6:9]}-{c[9]}"


def _parse_cents(raw_num: str) -> float:
    s = raw_num.strip()
    if not s or not s.isdigit():
        return 0.0
    return round(int(s) / 100.0, 2)


def _parse_age(raw_age: str) -> str:
    s = raw_age.strip()
    if not s or not s.isdigit():
        return "–"
    val = int(s)
    if val >= 9999:
        return "Não se aplica"
    if val == 0:
        return "Livre"
    years = val // 12
    months = val % 12
    if years > 0 and months > 0:
        return f"{years}a {months}m"
    if years > 0:
        return f"{years} anos"
    return f"{months} meses"


def load_sigtap_competencia(competencia: str) -> Dict[str, Any]:
    """Baixa e faz o parse de tb_procedimento.txt de uma competência (com cache em memória)."""
    comp = competencia.strip().replace("/", "")
    if comp in _SIGTAP_PARSED_CACHE:
        return _SIGTAP_PARSED_CACHE[comp]

    avail = list_sigtap_competencias()
    target = next((x for x in avail if x["competencia"] == comp), None)
    if not target:
        raise ValueError(f"Competência SIGTAP {comp} não encontrada no FTP DataSUS.")

    fname = target["file"]
    gen, _, _ = stream_ftp_file(SIGTAP_FTP_URL, fname)
    buf = io.BytesIO()
    for chunk in gen:
        buf.write(chunk)
    buf.seek(0)

    fin_map = dict(FINANCIAMENTO_DEFAULT)
    procs: Dict[str, Dict[str, Any]] = {}

    with zipfile.ZipFile(buf, "r") as zf:
        names_map = {n.lower(): n for n in zf.namelist()}

        # 1. Financiamento
        fin_file = next((v for k, v in names_map.items() if k.endswith("tb_financiamento.txt")), None)
        if fin_file:
            raw_fin = zf.read(fin_file).decode("latin-1", errors="replace")
            for ln in raw_fin.splitlines():
                if len(ln) >= 102:
                    co_fin = ln[0:2].strip()
                    no_fin = ln[2:102].strip()
                    if co_fin and no_fin:
                        fin_map[co_fin] = no_fin

        # 2. Procedimentos (layout posicional oficial de 336 colunas, conforme
        #    tb_procedimento_layout.txt: VL_SH/VL_SA/VL_SP têm 12 posições cada)
        proc_file = next((v for k, v in names_map.items() if k.endswith("tb_procedimento.txt")), None)
        if not proc_file:
            raise ValueError("Arquivo tb_procedimento.txt não localizado dentro do ZIP.")

        raw_proc = zf.read(proc_file).decode("latin-1", errors="replace")
        for ln in raw_proc.splitlines():
            if len(ln) < 326:
                continue
            code = ln[0:10].strip()
            if not code:
                continue
            name = ln[10:260].strip()
            tp_comp = ln[260:261].strip()
            tp_sexo = ln[261:262].strip()
            qt_max = ln[262:266].strip()
            vl_idade_min = ln[274:278].strip()
            vl_idade_max = ln[278:282].strip()
            vl_sh = _parse_cents(ln[282:294])
            vl_sa = _parse_cents(ln[294:306])
            vl_sp = _parse_cents(ln[306:318])
            co_fin = ln[318:320].strip()
            co_rub = ln[320:326].strip()

            procs[code] = {
                "code": code,
                "formattedCode": _format_proc_code(code),
                "name": name,
                "grupo": code[0:2],
                "subgrupo": code[0:4],
                "complexidadeCode": tp_comp,
                "complexidade": COMPLEXIDADE_MAP.get(tp_comp, tp_comp),
                "sexo": tp_sexo,
                "qtMax": int(qt_max) if qt_max.isdigit() else 0,
                "idadeMin": _parse_age(vl_idade_min),
                "idadeMax": _parse_age(vl_idade_max),
                "vlSH": vl_sh,
                "vlSA": vl_sa,
                "vlSP": vl_sp,
                "vlTotalHosp": round(vl_sh + vl_sp, 2),
                "financiamentoCode": co_fin,
                "financiamento": fin_map.get(co_fin, co_fin),
                "rubrica": co_rub,
            }

    parsed_bundle = {
        "competencia": comp,
        "file": fname,
        "totalProcedures": len(procs),
        "procs": procs,
    }
    _SIGTAP_PARSED_CACHE[comp] = parsed_bundle
    return parsed_bundle


def search_sigtap_procedures(
    competencia: str,
    query: str = "",
    grupo: str = "",
    complexidade: str = "",
    financiamento: str = "",
    limit: int = 120,
) -> Dict[str, Any]:
    bundle = load_sigtap_competencia(competencia)
    procs = bundle["procs"]
    q_clean = query.strip().lower()
    q_digits = re.sub(r"\D", "", q_clean)

    results = []
    for p in procs.values():
        if grupo and p["grupo"] != grupo:
            continue
        if complexidade and p["complexidadeCode"] != complexidade:
            continue
        if financiamento and p["financiamentoCode"] != financiamento:
            continue
        if q_clean:
            match_name = q_clean in p["name"].lower()
            match_code = (q_digits and q_digits in p["code"]) or (q_clean in p["formattedCode"].lower())
            if not (match_name or match_code):
                continue
        results.append(p)
        if len(results) >= limit:
            break

    return {
        "competencia": bundle["competencia"],
        "file": bundle["file"],
        "totalInTable": bundle["totalProcedures"],
        "returnedCount": len(results),
        "items": results,
    }


def diff_sigtap_competencias(comp_new: str, comp_old: str) -> Dict[str, Any]:
    """Compara duas competências do SIGTAP e retorna incluídos, revogados, reajustes e mudanças de regra."""
    b_new = load_sigtap_competencia(comp_new)
    b_old = load_sigtap_competencia(comp_old)

    p_new = b_new["procs"]
    p_old = b_old["procs"]

    added = []
    removed = []
    value_changed = []
    rule_changed = []

    for code, cur in p_new.items():
        if code not in p_old:
            added.append(cur)
        else:
            old = p_old[code]
            d_sa = round(cur["vlSA"] - old["vlSA"], 2)
            d_sh = round(cur["vlSH"] - old["vlSH"], 2)
            d_sp = round(cur["vlSP"] - old["vlSP"], 2)
            if d_sa != 0 or d_sh != 0 or d_sp != 0:
                value_changed.append({
                    "code": code,
                    "formattedCode": cur["formattedCode"],
                    "name": cur["name"],
                    "oldSA": old["vlSA"],
                    "newSA": cur["vlSA"],
                    "deltaSA": d_sa,
                    "oldSH": old["vlSH"],
                    "newSH": cur["vlSH"],
                    "deltaSH": d_sh,
                    "oldSP": old["vlSP"],
                    "newSP": cur["vlSP"],
                    "deltaSP": d_sp,
                    "financiamento": cur["financiamento"],
                })
            elif (
                cur["name"] != old["name"]
                or cur["complexidadeCode"] != old["complexidadeCode"]
                or cur["financiamentoCode"] != old["financiamentoCode"]
                or cur["idadeMin"] != old["idadeMin"]
                or cur["idadeMax"] != old["idadeMax"]
            ):
                changes = []
                if cur["name"] != old["name"]:
                    changes.append(f"Nome alterado")
                if cur["complexidadeCode"] != old["complexidadeCode"]:
                    changes.append(f"Complexidade: {old['complexidade']} → {cur['complexidade']}")
                if cur["financiamentoCode"] != old["financiamentoCode"]:
                    changes.append(f"Financiamento: {old['financiamentoCode']} → {cur['financiamentoCode']}")
                if cur["idadeMin"] != old["idadeMin"] or cur["idadeMax"] != old["idadeMax"]:
                    changes.append(f"Idade: ({old['idadeMin']} a {old['idadeMax']}) → ({cur['idadeMin']} a {cur['idadeMax']})")
                rule_changed.append({
                    "code": code,
                    "formattedCode": cur["formattedCode"],
                    "name": cur["name"],
                    "changes": " · ".join(changes),
                })

    for code, old in p_old.items():
        if code not in p_new:
            removed.append(old)

    return {
        "compNew": b_new["competencia"],
        "compOld": b_old["competencia"],
        "fileNew": b_new["file"],
        "fileOld": b_old["file"],
        "summary": {
            "totalNew": b_new["totalProcedures"],
            "totalOld": b_old["totalProcedures"],
            "addedCount": len(added),
            "removedCount": len(removed),
            "valueChangedCount": len(value_changed),
            "ruleChangedCount": len(rule_changed),
        },
        "added": added[:200],
        "removed": removed[:200],
        "valueChanged": value_changed[:200],
        "ruleChanged": rule_changed[:200],
    }
