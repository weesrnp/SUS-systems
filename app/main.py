# -*- coding: utf-8 -*-
"""
DownloadSUS Pro v2.0 — FastAPI Backend & BFF
"""

from pathlib import Path
from typing import Any, Dict, List, Optional
from fastapi import APIRouter, FastAPI, HTTPException, Query
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import FileResponse, JSONResponse, StreamingResponse
from fastapi.staticfiles import StaticFiles

from app.catalog import CATALOG_AREAS, UF_CODE_MAP
from app.ftp_engine import (
    fetch_raw_ftp_list,
    fetch_remessas_sia,
    process_and_group_files,
    stream_ftp_file,
)
from app.sigtap_engine import (
    diff_sigtap_competencias,
    list_sigtap_competencias,
    search_sigtap_procedures,
)

app = FastAPI(
    title="SUS-systems",
    description="SUS-systems — Central de Sistemas, Vigilâncias, APS (e-SUS/LEDI), SIGTAP & CNES",
    version="2.1.0",
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)

STATIC_DIR = Path(__file__).parent / "static"
STATIC_DIR.mkdir(parents=True, exist_ok=True)
app.mount("/static", StaticFiles(directory=str(STATIC_DIR)), name="static")


def _find_subgroup(group_id: str, sub_name: str) -> Optional[Dict[str, Any]]:
    for area in CATALOG_AREAS:
        for grp in area.get("groups", []):
            if grp["id"] == group_id:
                for sub in grp.get("subGroups", []):
                    if sub["name"] == sub_name:
                        return sub
    return None


@app.get("/")
def serve_spa():
    return FileResponse(str(STATIC_DIR / "index.html"))


api_router = APIRouter()


@api_router.get("/catalog")
def api_get_catalog():
    return {
        "areas": CATALOG_AREAS,
        "ufs": sorted(UF_CODE_MAP.values()),
    }


@api_router.get("/subgroup-data")
def api_get_subgroup_data(
    group_id: str = Query(..., description="ID do grupo (ex: SIA, SIH, CNES, VIG_EPIDEMIOLOGICA)"),
    sub_name: str = Query(..., description="Nome do subgrupo"),
    force_refresh: bool = Query(False, description="Ignorar cache de 10 minutos"),
):
    sub = _find_subgroup(group_id, sub_name)
    if not sub:
        raise HTTPException(status_code=404, detail=f"Subgrupo {group_id} / {sub_name} não encontrado.")

    sections_out: List[Dict[str, Any]] = []
    source_modes: List[str] = []

    for sec in sub.get("sections", []):
        ftp_url = sec["ftpUrl"]
        raw_files, mode = fetch_raw_ftp_list(ftp_url, force_refresh=force_refresh)
        source_modes.append(mode)
        desc_rules = sec.get("descriptionRules")
        grouped = process_and_group_files(
            raw_files=raw_files,
            include_prefixes=sec.get("includePrefixes"),
            include_regexes=sec.get("includeRegexes"),
            exclude_prefixes=sec.get("excludePrefixes"),
            exclude_regexes=sec.get("excludeRegexes"),
            description_rules=desc_rules,
        )
        sections_out.append({
            "title": sec.get("title", sub["name"]),
            "ftpUrl": ftp_url,
            "showNameFilter": sec.get("showNameFilter", True),
            "showDescriptionColumn": bool(desc_rules),
            "sourceMode": mode,
            "files": grouped,
        })

    return {
        "groupId": group_id,
        "subName": sub_name,
        "title": sub.get("title", sub_name),
        "primaryFtpUrl": sub.get("ftpUrl", ""),
        "specialView": sub.get("specialView"),
        "normatives": sub.get("normatives", []),
        "sections": sections_out,
        "sourceSummary": source_modes[0] if source_modes else "READY",
    }


@api_router.get("/ftp-list")
def api_raw_ftp_list(
    url: str = Query(..., description="URL FTP completa"),
    force_refresh: bool = Query(False),
):
    entries, mode = fetch_raw_ftp_list(url, force_refresh=force_refresh)
    return JSONResponse(content=entries, headers={"X-DataSUS-Source": mode})


@api_router.get("/ftp-download")
def api_ftp_download(
    base: str = Query(..., description="URL base do diretório FTP"),
    file: str = Query(..., description="Nome do arquivo no FTP"),
):
    safe_name = Path(file).name
    try:
        stream_gen, size, mode = stream_ftp_file(base, safe_name)
    except Exception as exc:
        raise HTTPException(status_code=502, detail=f"Erro ao iniciar download do FTP: {exc}")

    headers = {
        "Content-Disposition": f'attachment; filename="{safe_name}"',
        "X-Download-Mode": mode,
    }
    if size > 0:
        headers["Content-Length"] = str(size)
    return StreamingResponse(stream_gen, media_type="application/octet-stream", headers=headers)


@api_router.get("/remessa")
def api_get_remessa(
    ibge: str = Query("410965", description="Código IBGE do município (6 ou 7 dígitos)"),
    ano: str = Query("2026", description="Ano da competência"),
):
    return fetch_remessas_sia(ibge=ibge, ano=ano)


@api_router.get("/sigtap/competencias")
def api_sigtap_competencias(force_refresh: bool = Query(False)):
    return {"competencias": list_sigtap_competencias(force_refresh=force_refresh)}


@api_router.get("/sigtap/explore")
def api_sigtap_explore(
    comp: str = Query(..., description="Competência YYYYMM"),
    q: str = Query("", description="Termo de busca (código ou nome)"),
    grupo: str = Query("", description="Filtro de grupo (2 dígitos, ex: 03)"),
    complexidade: str = Query("", description="Filtro de complexidade (0,1,2,3)"),
    financiamento: str = Query("", description="Filtro de financiamento (01,04,06,07)"),
    limit: int = Query(120, ge=1, le=500),
):
    try:
        return search_sigtap_procedures(
            competencia=comp,
            query=q,
            grupo=grupo,
            complexidade=complexidade,
            financiamento=financiamento,
            limit=limit,
        )
    except Exception as exc:
        raise HTTPException(status_code=400, detail=str(exc))


@api_router.get("/health")
def api_health():
    return {
        "status": "healthy",
        "app": "SUS-systems",
        "version": "2.2.0",
        "ftp_clusters": ["arpoador.datasus.gov.br", "ftp.datasus.gov.br", "ftp2.datasus.gov.br"],
    }


@api_router.get("/sigtap/diff")
def api_sigtap_diff(
    comp_new: str = Query(..., description="Competência mais recente YYYYMM"),
    comp_old: str = Query(..., description="Competência anterior YYYYMM"),
):
    try:
        return diff_sigtap_competencias(comp_new=comp_new, comp_old=comp_old)
    except Exception as exc:
        raise HTTPException(status_code=400, detail=str(exc))


app.include_router(api_router, prefix="/api")
app.include_router(api_router, prefix="")


# Na Vercel a pasta public/ já fica na raiz; aqui os ícones saem de app/static.
# Registrada por último para não capturar as rotas da API montadas sem prefixo.
ROOT_ICONS = {"favicon.svg", "favicon.ico", "apple-touch-icon.png"}


@app.get("/{icon_name}", include_in_schema=False)
def serve_root_icon(icon_name: str):
    if icon_name not in ROOT_ICONS:
        raise HTTPException(status_code=404)
    return FileResponse(str(STATIC_DIR / icon_name))

