# -*- coding: utf-8 -*-
"""
Motor do Monitor de Prazos e Pacotes Essenciais — Portaria GM/MS nº 7.639/2025
"""

from datetime import datetime
from typing import Any, Dict, List
from app.ftp_engine import fetch_raw_ftp_list, process_and_group_files, fetch_remessas_sia

PORTARIA_7639_SYSTEMS: List[Dict[str, Any]] = [
    {
        "order": 1,
        "sigla": "SCNES",
        "nome": "Cadastro Nacional de Estabelecimentos de Saúde",
        "prazo": "Até o 5º dia útil de cada mês",
        "orgao": "SAES / DRAC",
        "risco": "Bloqueio de repasses MAC e descredenciamento de equipes INE/APS",
        "ftpGroup": "CNES",
        "ftpSub": "Aplicativo SCNES"
    },
    {
        "order": 2,
        "sigla": "SISAB / e-SUS APS",
        "nome": "Sistema de Informação em Saúde para a Atenção Básica (LEDI 8.7.0)",
        "prazo": "Até o dia 10 do mês subsequente",
        "orgao": "SAPS / MS",
        "risco": "Suspensão de 50% (2 meses) a 100% (3 meses) do Cofinanciamento APS (Portaria 3.493/2024)",
        "ftpGroup": "ESUS_APS",
        "ftpSub": "Equipes ESF & LEDI"
    },
    {
        "order": 3,
        "sigla": "SIASUS (BPA / APAC / RAAS)",
        "nome": "Sistema de Informações Ambulatoriais do SUS",
        "prazo": "Até o dia 15 de cada mês (conforme cronograma LogCarga)",
        "orgao": "SAES / DRAC",
        "risco": "Glosa financeira e retenção do Teto MAC Ambulatorial",
        "ftpGroup": "SIA",
        "ftpSub": "SIA"
    },
    {
        "order": 4,
        "sigla": "SIGTAP / Tabelas Nacionais",
        "nome": "Sistema de Gerenciamento da Tabela de Procedimentos, Medicamentos e OPME do SUS",
        "prazo": "Atualização mensal obrigatória de competência antes do fechamento",
        "orgao": "SAES / DRAC",
        "risco": "Rejeição de remessas BPA/APAC por incompatibilidade de código/CBO/CID",
        "ftpGroup": "SIGTAP",
        "ftpSub": "Tabelas"
    },
    {
        "order": 5,
        "sigla": "SISVAN",
        "nome": "Sistema de Vigilância Alimentar e Nutricional",
        "prazo": "Exportação mensal contínua via e-SUS PEC / e-Gestor",
        "orgao": "SAPS / CGAN",
        "risco": "Perda de incentivos de programas nutricionais e condicionalidades",
        "ftpGroup": "VIG_IMUNIZACAO_AMBIENTAL",
        "ftpSub": "SISVAN Nutricional"
    },
    {
        "order": 6,
        "sigla": "SI-PNI / RNDS",
        "nome": "Sistema de Informações do Programa Nacional de Imunizações",
        "prazo": "Transmissão contínua via RNDS (até 48h após aplicação)",
        "orgao": "SVSA / DPNI",
        "risco": "Queda no Indicador C2 da APS e bloqueio de incentivos de vacinação",
        "ftpGroup": "VIG_IMUNIZACAO_AMBIENTAL",
        "ftpSub": "SI-PNI Imunização"
    },
    {
        "order": 7,
        "sigla": "Bolsa Família na Saúde",
        "nome": "Acompanhamento de Condicionalidades de Saúde (PBF / CadÚnico)",
        "prazo": "Fechamento semestral (1ª e 2ª vigências no e-Gestor APS)",
        "orgao": "SAPS / MDS",
        "risco": "Perda do fator multiplicador de vulnerabilidade social no cofinanciamento",
        "ftpGroup": "VIG_IMUNIZACAO_AMBIENTAL",
        "ftpSub": "SISVAN Nutricional"
    }
]

PRESET_MUNICIPALITIES = [
    {"ibge": "410965", "ibge7": "4109658", "nome": "Honório Serpa", "uf": "PR", "ufCod": "41", "regional": "7ª RS Pato Branco / CONIMS"},
]


def get_municipal_summary(ibge: str = "410965", uf: str = "PR", ano: str = "") -> Dict[str, Any]:
    uf_up = uf.strip().upper() or "PR"
    cur_year = ano.strip() or str(datetime.now().year)
    muni_meta = PRESET_MUNICIPALITIES[0]

    essential_packages: List[Dict[str, Any]] = []

    # 1. SIGTAP Tabela Unificada mais recente
    sigtap_raw, _ = fetch_raw_ftp_list("ftp://ftp2.datasus.gov.br/pub/sistemas/tup/downloads/")
    sigtap_grouped = process_and_group_files(sigtap_raw, include_prefixes=["TabelaUnificada_"])
    if sigtap_grouped:
        top_sig = sigtap_grouped[0]
        essential_packages.append({
            "category": "SIGTAP — Tabela Unificada",
            "name": top_sig["name"],
            "ym": top_sig.get("ym"),
            "size": top_sig.get("size", 0),
            "modifiedAt": top_sig.get("modifiedAt"),
            "ftpUrl": "ftp://ftp2.datasus.gov.br/pub/sistemas/tup/downloads/",
        })

    # 2. BDSIA (Base de Dados SIA)
    sia_raw, _ = fetch_raw_ftp_list("ftp://arpoador.datasus.gov.br/siasus/SIA/")
    bdsia_grouped = process_and_group_files(sia_raw, include_prefixes=["BDSIA"])
    if bdsia_grouped:
        top_bdsia = bdsia_grouped[0]
        essential_packages.append({
            "category": "SIA — Base BDSIA Mensal",
            "name": top_bdsia["name"],
            "ym": top_bdsia.get("ym"),
            "size": top_bdsia.get("size", 0),
            "modifiedAt": top_bdsia.get("modifiedAt"),
            "ftpUrl": "ftp://arpoador.datasus.gov.br/siasus/SIA/",
        })

    # 3. EQUIPES ESF BRASIL
    arp_cnes_raw, _ = fetch_raw_ftp_list("ftp://arpoador.datasus.gov.br/cnes/")
    esf_grouped = process_and_group_files(arp_cnes_raw, include_prefixes=["EQUIPESESFBRASIL"])
    if esf_grouped:
        top_esf = esf_grouped[0]
        essential_packages.append({
            "category": "APS / SIA — Equipes ESF Brasil",
            "name": top_esf["name"],
            "ym": top_esf.get("ym"),
            "size": top_esf.get("size", 0),
            "modifiedAt": top_esf.get("modifiedAt"),
            "ftpUrl": "ftp://arpoador.datasus.gov.br/cnes/",
        })

    # 4. Arquivos Estaduais CNES (SCNES_ARQUIVOS_UF e TERCEIROSBRASIL_UF)
    cnes_raw, _ = fetch_raw_ftp_list("ftp://ftp.datasus.gov.br/cnes/")
    cnes_uf_grouped = process_and_group_files(
        cnes_raw,
        include_prefixes=["SCNES_ARQUIVOS_"],
        include_regexes=[r"^TERCEIROSBRASIL\d{2}"],
    )
    for item in cnes_uf_grouped:
        uf_map = item.get("ufBySigla") or {}
        if uf_up in uf_map:
            uf_entry = uf_map[uf_up]
            label = f"CNES Arquivo Único ({uf_up})" if item["name"].upper().startswith("SCNES_") else f"CNES Terceiros ({uf_up})"
            essential_packages.append({
                "category": label,
                "name": uf_entry["name"],
                "ym": uf_entry.get("ym"),
                "size": uf_entry.get("size", 0),
                "modifiedAt": uf_entry.get("modifiedAt"),
                "ftpUrl": "ftp://ftp.datasus.gov.br/cnes/",
            })

    remessa_data = fetch_remessas_sia(muni_meta["ibge"], cur_year)

    return {
        "municipality": muni_meta,
        "presets": PRESET_MUNICIPALITIES,
        "year": cur_year,
        "essentialPackages": essential_packages,
        "remessas": remessa_data.get("rows", []),
        "remessaFtpUrl": remessa_data.get("ftpUrl", "ftp://arpoador.datasus.gov.br/siasus/LogCarga/"),
        "portaria7639": PORTARIA_7639_SYSTEMS,
    }
