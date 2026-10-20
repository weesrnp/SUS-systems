# -*- coding: utf-8 -*-
"""
Catálogo Mestre — SUS-systems v2.2
Organizado e alinhado com os sistemas ativos:
1. SIA (APAC, BPA, FPO, RAAS, SIA, Remessa)
2. SIGTAP (Aplicativo, Tabelas, Nota Técnica, Outros, Consulta & Comparativo)
3. CNES (Aplicativo SCNES, Arquivos da aplicação, Arquivos para outros sistemas, Base de dados, Outros)
4. e-SUS APS (Equipes ESF & LEDI)
5. PNI & SISVAN (Imunização PNI, SISVAN)
6. TabWin & DBC (TabWin, Microdados SIA, Microdados CNES)
"""

from typing import Dict, List, Any

UF_CODE_MAP: Dict[str, str] = {
    "11": "RO", "12": "AC", "13": "AM", "14": "RR", "15": "PA", "16": "AP", "17": "TO",
    "21": "MA", "22": "PI", "23": "CE", "24": "RN", "25": "PB", "26": "PE", "27": "AL",
    "28": "SE", "29": "BA", "31": "MG", "32": "ES", "33": "RJ", "35": "SP", "41": "PR",
    "42": "SC", "43": "RS", "50": "MS", "51": "MT", "52": "GO", "53": "DF"
}

CNES_APP_DESCRIPTIONS = [
    {"description": "EQUIPES BRASIL", "startsWith": "EQUIPESBRASIL_"},
    {"description": "PROFISSIONAIS BRASIL", "startsWith": "PROFISSIONAIS_BRASIL_"},
    {"description": "CEP BRASIL", "startsWith": "CEP_BRASIL"},
    {"description": "COOPERATIVAS BRASIL", "startsWith": "COOPERCBO"},
    {"description": "GERÊNCIA/ADMINISTRAÇÃO (TERCEIRO)", "startsWith": "GERENTEADMINCONTRATOS"},
    {"description": "HOSPITAL FILANTRÓPICO BRASIL", "startsWith": "HOSPFILANTROPICOSBRASIL"},
    {"description": "NATUREZA JURÍDICA", "startsWith": "ESTNATJURBRASIL"},
    {"description": "GESTOR FEDERAL", "startsWith": "GESTORFEDERAL_"},
    {"description": "SAMU BRASIL", "startsWith": "SAMUBRASIL_"},
    {"description": "CNES VÁLIDOS/EXPIRADOS (3 MESES)", "startsWith": "S_UPSVAL_3"},
    {"description": "ESTABELECIMENTOS NOTIFICANTES", "startsWith": "CNES_NOTIF_"},
    {"description": "HOSPITAL DE ENSINO BRASIL", "startsWith": "CERTHOSPITALENSINOBRASIL"},
    {"description": "MUNICÍPIOS BRASIL", "startsWith": "MUNICIPIO_BRASIL"},
    {"description": "TERCEIROS BRASIL", "startsWith": "TERCEIROSBRASIL_"},
    {"description": "TERCEIROS BRASIL (UF)", "regex": r"^TERCEIROSBRASIL\d{2}"},
    {"description": "SAÚDE INDÍGENA", "startsWith": "SAUDE_INDIGENA_"},
]

CNES_OTHER_SYSTEMS_DESCRIPTIONS = [
    {"description": "FORNECEDORES ANVISA", "startsWith": "FORNEC_"},
    {"description": "NUMERAÇÃO CNES BRASIL", "startsWith": "CNESBRASIL"},
    {"description": "EQUIPES CAPTAÇÃO (ESF)", "startsWith": "EQUIPESESFBRASIL"},
    {"description": "CNES VÁLIDOS/EXPIRADOS (7 MESES)", "startsWith": "S_UPSVAL_7"},
    {"description": "IMPORTADORES", "startsWith": "IMPORT_"},
    {"description": "MÓDULO SISGERF", "startsWith": "SISGERF"},
    {"description": "LEITOS 65", "startsWith": "ESTABELECIMENTO_LEITO_65"},
]

CATALOG_AREAS: List[Dict[str, Any]] = [
    {
        "area_id": "FATURAMENTO_SIA",
        "area_title": "Faturamento Ambulatorial",
        "area_badge": "SIA/SUS",
        "groups": [
            {
                "id": "SIA",
                "name": "SIA",
                "icon": "activity",
                "subGroups": [
                    {
                        "name": "APAC",
                        "title": "APAC — Alta Complexidade / Magnético",
                        "ftpUrl": "ftp://arpoador.datasus.gov.br/siasus/APAC/",
                        "sections": [
                            {"title": "Instaladores e Versões APAC", "ftpUrl": "ftp://arpoador.datasus.gov.br/siasus/APAC/", "showNameFilter": True},
                            {"title": "BDSIA — Base de Dados SIA", "ftpUrl": "ftp://arpoador.datasus.gov.br/siasus/SIA/", "includePrefixes": ["BDSIA"], "showNameFilter": False},
                            {"title": "EQUIPES ESF BRASIL (CNES)", "ftpUrl": "ftp://arpoador.datasus.gov.br/cnes/", "includePrefixes": ["EQUIPESESFBRASIL"], "showNameFilter": False},
                        ],
                        "normatives": [
                            {"code": "Manual Operacional APAC", "title": "Manual Técnico de Faturamento APAC/SIA e Regras de Autorização", "tag": "SAES/MS", "url": "https://wiki.saude.gov.br/siasus/"},
                            {"code": "Portaria GM/MS 7.639/2025", "title": "Prazo obrigatório de envio SIASUS: até o dia 15 de cada mês", "tag": "Prazo Legal", "url": "#workspace-municipal"}
                        ]
                    },
                    {
                        "name": "BPA",
                        "title": "BPA — Boletim de Produção Ambulatorial",
                        "ftpUrl": "ftp://arpoador.datasus.gov.br/siasus/BPA/",
                        "sections": [
                            {"title": "Instaladores e Versões BPAMAG", "ftpUrl": "ftp://arpoador.datasus.gov.br/siasus/BPA/", "showNameFilter": True},
                            {"title": "BDSIA — Base de Dados SIA", "ftpUrl": "ftp://arpoador.datasus.gov.br/siasus/SIA/", "includePrefixes": ["BDSIA"], "showNameFilter": False},
                            {"title": "EQUIPES ESF BRASIL (CNES)", "ftpUrl": "ftp://arpoador.datasus.gov.br/cnes/", "includePrefixes": ["EQUIPESESFBRASIL"], "showNameFilter": False},
                        ],
                        "normatives": [
                            {"code": "BPA Consolidado (BPA-C) & Individualizado (BPA-I)", "title": "Exportação magnética com validação obrigatória de CNS/CPF e CBO", "tag": "Faturamento", "url": "https://wiki.saude.gov.br/siasus/"}
                        ]
                    },
                    {
                        "name": "FPO",
                        "title": "FPO — Ficha de Programação Física Orçamentária",
                        "ftpUrl": "ftp://arpoador.datasus.gov.br/siasus/fpo/",
                        "sections": [
                            {"title": "Aplicativo e Atualizações FPOMAG", "ftpUrl": "ftp://arpoador.datasus.gov.br/siasus/fpo/", "showNameFilter": True}
                        ]
                    },
                    {
                        "name": "RAAS",
                        "title": "RAAS — Atenção Domiciliar e Psicossocial (CAPS)",
                        "ftpUrl": "ftp://arpoador.datasus.gov.br/siasus/raas/",
                        "sections": [
                            {"title": "Instaladores RAAS", "ftpUrl": "ftp://arpoador.datasus.gov.br/siasus/raas/", "showNameFilter": True},
                            {"title": "BDSIA — Base de Dados SIA", "ftpUrl": "ftp://arpoador.datasus.gov.br/siasus/SIA/", "includePrefixes": ["BDSIA"], "showNameFilter": False},
                            {"title": "EQUIPES ESF BRASIL (CNES)", "ftpUrl": "ftp://arpoador.datasus.gov.br/cnes/", "includePrefixes": ["EQUIPESESFBRASIL"], "showNameFilter": False},
                        ]
                    },
                    {
                        "name": "SIA",
                        "title": "SIA — Sistema de Informações Ambulatoriais Principal",
                        "ftpUrl": "ftp://arpoador.datasus.gov.br/siasus/SIA/",
                        "sections": [
                            {"title": "Aplicativo SIA & Notas de Versão", "ftpUrl": "ftp://arpoador.datasus.gov.br/siasus/SIA/", "includePrefixes": ["SIA", "LERNOTAS.TXT"], "showNameFilter": False},
                            {"title": "BDSIA — Competências Mensais", "ftpUrl": "ftp://arpoador.datasus.gov.br/siasus/SIA/", "includePrefixes": ["BDSIA"], "showNameFilter": False},
                            {"title": "Outros Utilitários SIA", "ftpUrl": "ftp://arpoador.datasus.gov.br/siasus/SIA/", "excludePrefixes": ["SIA", "BDSIA", "LERNOTAS.TXT"], "showNameFilter": True},
                        ]
                    },
                    {
                        "name": "Remessa",
                        "title": "Acompanhamento de Entrega de Remessas (LogCarga)",
                        "ftpUrl": "ftp://arpoador.datasus.gov.br/siasus/LogCarga/",
                        "specialView": "remessa"
                    }
                ]
            }
        ]
    },
    {
        "area_id": "CADASTROS_TABELAS",
        "area_title": "Tabelas & Cadastros Nacionais",
        "area_badge": "SIGTAP & CNES",
        "groups": [
            {
                "id": "SIGTAP",
                "name": "SIGTAP",
                "icon": "table-2",
                "subGroups": [
                    {
                        "name": "Tabelas",
                        "title": "SIGTAP — Tabelas Unificadas Mensais (Competências)",
                        "ftpUrl": "ftp://ftp2.datasus.gov.br/pub/sistemas/tup/downloads/",
                        "sections": [
                            {"title": "Pacotes Mensais TabelaUnificada_YYYYMM.zip", "ftpUrl": "ftp://ftp2.datasus.gov.br/pub/sistemas/tup/downloads/", "includePrefixes": ["TabelaUnificada_"], "showNameFilter": False}
                        ],
                        "normatives": [
                            {"code": "Consulta & Comparativo de Competências SIGTAP", "title": "Consulte procedimentos, valores (SA/SH/SP) e compare reajustes entre duas competências na aba 'Consulta & Comparativo'", "tag": "SIGTAP Online", "url": "#workspace-sigtap"}
                        ]
                    },
                    {
                        "name": "Aplicativo",
                        "title": "SIGTAP Desktop — Instalador do Aplicativo",
                        "ftpUrl": "ftp://ftp2.datasus.gov.br/pub/sistemas/tup/downloads/",
                        "sections": [
                            {"title": "Instalador SIGTAP (sigtap-setup)", "ftpUrl": "ftp://ftp2.datasus.gov.br/pub/sistemas/tup/downloads/", "includePrefixes": ["sigtap-setup"], "showNameFilter": False}
                        ]
                    },
                    {
                        "name": "Nota Técnica",
                        "title": "SIGTAP — Notas Técnicas e Portarias de Alteração",
                        "ftpUrl": "ftp://ftp2.datasus.gov.br/public/sistemas/tup/downloads/notastecnicas/",
                        "sections": [
                            {"title": "Notas Técnicas Oficiais (PDF/ZIP)", "ftpUrl": "ftp://ftp2.datasus.gov.br/public/sistemas/tup/downloads/notastecnicas/", "includePrefixes": ["nota_tecnica_"], "showNameFilter": True}
                        ]
                    },
                    {
                        "name": "Outros",
                        "title": "SIGTAP — Outros Arquivos e Layouts",
                        "ftpUrl": "ftp://ftp2.datasus.gov.br/pub/sistemas/tup/downloads/",
                        "sections": [
                            {"title": "Layouts e Utilitários TUP", "ftpUrl": "ftp://ftp2.datasus.gov.br/pub/sistemas/tup/downloads/", "excludePrefixes": ["sigtap-setup", "TabelaUnificada_"], "showNameFilter": True}
                        ]
                    },
                    {
                        "name": "Consulta & Comparativo",
                        "title": "SIGTAP — Consulta de Procedimentos & Comparativo de Competências",
                        "ftpUrl": "ftp://ftp2.datasus.gov.br/pub/sistemas/tup/downloads/",
                        "specialView": "sigtap"
                    }
                ]
            },
            {
                "id": "CNES",
                "name": "CNES",
                "icon": "hospital",
                "subGroups": [
                    {
                        "name": "Aplicativo SCNES",
                        "title": "SCNES — Instalador e Atualizações Nacionais",
                        "ftpUrl": "ftp://ftp.datasus.gov.br/cnes/Versoes-Fces-Nacional/",
                        "sections": [
                            {"title": "Versões SCNES (Completa e Atualização)", "ftpUrl": "ftp://ftp.datasus.gov.br/cnes/Versoes-Fces-Nacional/", "includeRegexes": [r"^SCNES\d{4}"], "showNameFilter": False},
                            {"title": "Leia-me de Versão SCNES", "ftpUrl": "ftp://ftp.datasus.gov.br/cnes/Download/", "includePrefixes": ["LEIA_ME_"], "showNameFilter": False}
                        ]
                    },
                    {
                        "name": "Arquivos da aplicação",
                        "title": "CNES — Arquivos Nacionais e Estaduais para Importação",
                        "ftpUrl": "ftp://ftp.datasus.gov.br/cnes/",
                        "sections": [
                            {"title": "Arquivos Nacionais da Aplicação CNES", "ftpUrl": "ftp://ftp.datasus.gov.br/cnes/", "includePrefixes": [r["startsWith"] for r in CNES_APP_DESCRIPTIONS if "startsWith" in r], "includeRegexes": [r["regex"] for r in CNES_APP_DESCRIPTIONS if "regex" in r], "descriptionRules": CNES_APP_DESCRIPTIONS, "showNameFilter": True},
                            {"title": "Arquivo Único por UF (SCNES_ARQUIVOS_UF)", "ftpUrl": "ftp://ftp.datasus.gov.br/cnes/", "includePrefixes": ["SCNES_ARQUIVOS_"], "descriptionRules": [{"description": "ARQUIVO ÚNICO/UF", "startsWith": "SCNES_ARQUIVOS_"}], "showNameFilter": False}
                        ]
                    },
                    {
                        "name": "Arquivos para outros sistemas",
                        "title": "CNES — Arquivos de Exportação para SIA e ANVISA",
                        "ftpUrl": "ftp://ftp.datasus.gov.br/cnes/",
                        "sections": [
                            {"title": "Arquivos para Outros Sistemas", "ftpUrl": "ftp://ftp.datasus.gov.br/cnes/", "includePrefixes": [r["startsWith"] for r in CNES_OTHER_SYSTEMS_DESCRIPTIONS], "descriptionRules": CNES_OTHER_SYSTEMS_DESCRIPTIONS, "showNameFilter": True}
                        ]
                    },
                    {
                        "name": "Base de dados",
                        "title": "CNES — Base de Dados Nacional Completa",
                        "ftpUrl": "ftp://ftp.datasus.gov.br/cnes/",
                        "sections": [
                            {"title": "Pacotes BASE_DE_DADOS_CNES_YYYYMM.ZIP", "ftpUrl": "ftp://ftp.datasus.gov.br/cnes/", "includePrefixes": ["BASE_DE_DADOS_CNES_"], "showNameFilter": False}
                        ]
                    },
                    {
                        "name": "Outros",
                        "title": "CNES — Outros Arquivos e Documentações",
                        "ftpUrl": "ftp://ftp.datasus.gov.br/cnes/",
                        "sections": [
                            {
                                "title": "Demais Arquivos do Diretório CNES",
                                "ftpUrl": "ftp://ftp.datasus.gov.br/cnes/",
                                "excludePrefixes": [
                                    "EQUIPESBRASIL_", "PROFISSIONAIS_BRASIL_", "CEP_BRASIL", "COOPERCBO",
                                    "GERENTEADMINCONTRATOS", "HOSPFILANTROPICOSBRASIL", "ESTNATJURBRASIL",
                                    "GESTORFEDERAL_", "SAMUBRASIL_", "S_UPSVAL_3", "CNES_NOTIF_",
                                    "CERTHOSPITALENSINOBRASIL", "MUNICIPIO_BRASIL", "TERCEIROSBRASIL_",
                                    "SAUDE_INDIGENA_", "SCNES_ARQUIVOS_", "FORNEC_", "CNESBRASIL",
                                    "EQUIPESESFBRASIL", "S_UPSVAL_7", "IMPORT_", "SISGERF",
                                    "ESTABELECIMENTO_LEITO_65", "BASE_DE_DADOS_CNES_", "LEIA_ME_"
                                ],
                                "excludeRegexes": [r"^TERCEIROSBRASIL\d{2}", r"^SCNES\d{4}"],
                                "showNameFilter": True
                            }
                        ]
                    }
                ]
            }
        ]
    },
    {
        "area_id": "ATENCAO_PRIMARIA",
        "area_title": "Atenção Primária",
        "area_badge": "APS & LEDI",
        "groups": [
            {
                "id": "ESUS_APS",
                "name": "e-SUS APS",
                "icon": "heart-pulse",
                "subGroups": [
                    {
                        "name": "Equipes ESF & LEDI",
                        "title": "e-SUS APS — Equipes ESF, Profissionais CNES & Padrão LEDI 8.7.0",
                        "ftpUrl": "ftp://arpoador.datasus.gov.br/cnes/",
                        "sections": [
                            {"title": "EQUIPES ESF BRASIL (Arquivo Mensal de Equipes para Importação)", "ftpUrl": "ftp://arpoador.datasus.gov.br/cnes/", "includePrefixes": ["EQUIPESESFBRASIL"], "showNameFilter": False},
                            {"title": "Equipes, Profissionais e Saúde Indígena (CNES Nacional)", "ftpUrl": "ftp://ftp.datasus.gov.br/cnes/", "includePrefixes": ["EQUIPESBRASIL_", "PROFISSIONAIS_BRASIL_", "SAUDE_INDIGENA_"], "descriptionRules": CNES_APP_DESCRIPTIONS, "showNameFilter": False}
                        ],
                        "normatives": [
                            {"code": "LEDI 8.7.0 (Apache Thrift / REST API)", "title": "Layout e-SUS APS de Dados e Interface: endpoint REST /api/v1/recebimento/ficha, priorização obrigatória do CPF e suporte a CNPJ Alfanumérico (IN RFB 2.229)", "tag": "Interoperabilidade", "url": "https://sisab.saude.gov.br/"},
                            {"code": "Portaria GM/MS nº 3.493/2024 & SAPS nº 161/2024", "title": "Novo Cofinanciamento Federal da APS: 15 Indicadores de Qualidade (C1 a C7 para eSF, B1 a B6 para Saúde Bucal e M1 a M2 para eMulti)", "tag": "Financiamento APS", "url": "https://egestorab.saude.gov.br/"},
                            {"code": "Nota Técnica PSE nº 30/2024 & nº 7/2026", "title": "Programa Saúde na Escola Ciclo 2025/2026: 14 ações temáticas registradas via Ficha de Atividade Coletiva (FAC) com código INEP", "tag": "PSE / Escola", "url": "https://sisab.saude.gov.br/"},
                            {"code": "Portal Oficial de Downloads e-SUS APS", "title": "Instaladores oficiais do e-SUS PEC (Windows/Linux), Treinamento e APKs (e-SUS Território, Vacinação, Atividade Coletiva)", "tag": "Download Oficial SAPS", "url": "https://sisaps.saude.gov.br/esus/"}
                        ]
                    }
                ]
            },
            {
                "id": "VIG_IMUNIZACAO_AMBIENTAL",
                "name": "PNI & SISVAN",
                "icon": "syringe",
                "subGroups": [
                    {
                        "name": "SI-PNI Imunização",
                        "title": "PNI — Programa Nacional de Imunizações & Integração RNDS",
                        "ftpUrl": "ftp://ftp.datasus.gov.br/dissemin/publicos/PNI/",
                        "sections": [
                            {"title": "Repositório Público PNI (FTP DataSUS)", "ftpUrl": "ftp://ftp.datasus.gov.br/dissemin/publicos/PNI/", "showNameFilter": True}
                        ],
                        "normatives": [
                            {"code": "Calendário Nacional de Vacinação PNI 2026", "title": "Esquema atualizado: VIP exclusiva (extinção VOP), Rotavírus ampliado até 24m, HPV dose única (9-14 anos), dTpa a cada gestação (>=20 sem)", "tag": "Norma Técnica PNI", "url": "https://www.gov.br/saude/pt-br/assuntos/saude-de-a-a-z/c/calendario-nacional-de-vacinacao"},
                            {"code": "Portaria GM/MS nº 7.639/2025 (RNDS 48h)", "title": "Transmissão obrigatória dos registros de vacinação (e-SUS PEC / Novo SI-PNI) para a RNDS em até 48 horas", "tag": "Interoperabilidade", "url": "#workspace-municipal"},
                            {"code": "Lei Federal nº 13.595/2018 (Lei Ruth Brilhante)", "title": "Atribuições integradas de ACE (Agente de Combate às Endemias) e ACS no controle vetorial de arboviroses e busca ativa vacinal", "tag": "ACS / ACE", "url": "#workspace-municipal"}
                        ]
                    },
                    {
                        "name": "SISVAN Nutricional",
                        "title": "SISVAN — Vigilância Alimentar e Nutricional & Bolsa Família",
                        "ftpUrl": "ftp://ftp.datasus.gov.br/dissemin/publicos/SISVAN/",
                        "sections": [
                            {"title": "Repositório Público SISVAN (FTP DataSUS)", "ftpUrl": "ftp://ftp.datasus.gov.br/dissemin/publicos/SISVAN/", "showNameFilter": True}
                        ],
                        "normatives": [
                            {"code": "Condicionalidades de Saúde do Bolsa Família", "title": "Acompanhamento semestral obrigatório de peso, altura, pré-natal e vacinação (fator multiplicador de vulnerabilidade no cofinanciamento APS)", "tag": "e-Gestor / PBF", "url": "https://egestorab.saude.gov.br/"}
                        ]
                    }
                ]
            }
        ]
    },
    {
        "area_id": "TABWIN_MICRODADOS",
        "area_title": "TabWin & Microdados",
        "area_badge": "DADOS .DBC",
        "groups": [
            {
                "id": "TABWIN_DISSEMIN",
                "name": "TabWin & DBC",
                "icon": "database",
                "subGroups": [
                    {
                        "name": "TabWin",
                        "title": "TabWin — Tabulador para Windows, Mapas e Conversores",
                        "ftpUrl": "ftp://ftp.datasus.gov.br/tabwin/tabwin/",
                        "sections": [
                            {"title": "Pacotes TabWin (FTP DataSUS)", "ftpUrl": "ftp://ftp.datasus.gov.br/tabwin/tabwin/", "showNameFilter": True}
                        ],
                        "normatives": [
                            {"code": "Formato .DBC (PKWARE DataSUS)", "title": "Os microdados públicos do DataSUS são distribuídos em formato .DBC (DBF comprimido) compatível com TabWin, PySUS e R (read.dbc)", "tag": "Ciência de Dados", "url": "https://datasus.saude.gov.br/transferencia-de-arquivos/"}
                        ]
                    },
                    {
                        "name": "Microdados SIA",
                        "title": "Repositório Dissemin — Produção Ambulatorial (PA / APAC)",
                        "ftpUrl": "ftp://ftp.datasus.gov.br/dissemin/publicos/SIASUS/",
                        "sections": [
                            {"title": "Diretório Dissemin SIASUS", "ftpUrl": "ftp://ftp.datasus.gov.br/dissemin/publicos/SIASUS/", "showNameFilter": True}
                        ]
                    },
                    {
                        "name": "Microdados CNES",
                        "title": "Repositório Dissemin — Microdados Históricos CNES",
                        "ftpUrl": "ftp://ftp.datasus.gov.br/dissemin/publicos/CNES/",
                        "sections": [
                            {"title": "Diretório Dissemin CNES", "ftpUrl": "ftp://ftp.datasus.gov.br/dissemin/publicos/CNES/", "showNameFilter": True}
                        ]
                    }
                ]
            }
        ]
    }
]
