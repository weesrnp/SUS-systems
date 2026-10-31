<?php
declare(strict_types=1);
use SusSystems\Domain\Ftp\FileGrouper;

// Amostras reais e saída produzida pelo Python legado em 29/10/2026.
return ['equivalência com catálogo e listagens reais' => static function(): void {
    $cases = json_decode(<<<'JSON'
[
  {
    "raw": [
      {
        "name": "DICIONARIO_DE_DADOS.ZIP",
        "size": 602386,
        "modifiedAt": "2026-01-23T11:37:00+00:00"
      },
      {
        "name": "informe_cnes.txt",
        "size": 21,
        "modifiedAt": "2012-09-04T12:24:00+00:00"
      },
      {
        "name": "leia_me.doc",
        "size": 7451136,
        "modifiedAt": "2012-01-31T10:51:00+00:00"
      },
      {
        "name": "leia_me.pdf",
        "size": 11183051,
        "modifiedAt": "2013-08-22T17:58:00+00:00"
      },
      {
        "name": "leia_me.zip",
        "size": 9364098,
        "modifiedAt": "2013-08-22T17:59:00+00:00"
      },
      {
        "name": "lista_arquivos_FTP.txt",
        "size": 29801846,
        "modifiedAt": "2016-02-12T09:35:00+00:00"
      },
      {
        "name": "PASSO_A_PASSO_DA_APLICACAO_DA_PORTARIA_SAS_134_VERSAO_DRAC_FINAL_CNES.doc",
        "size": 21504,
        "modifiedAt": "2011-05-03T10:55:00+00:00"
      },
      {
        "name": "Planilha dados cadastrais REGIONAIS -  Atualizada.xls",
        "size": 47104,
        "modifiedAt": "2010-03-19T16:32:00+00:00"
      },
      {
        "name": "PROFDUPLOMESMOMUN202610.XLS",
        "size": 2242048,
        "modifiedAt": "2026-10-19T02:18:00+00:00"
      },
      {
        "name": "PROFDUPLOMUNDIF202609.XLS",
        "size": 1807360,
        "modifiedAt": "2026-10-15T11:53:00+00:00"
      },
      {
        "name": "PSE202610.ZIP",
        "size": 16873,
        "modifiedAt": "2026-10-29T16:03:00+00:00"
      },
      {
        "name": "_ctrl.txt",
        "size": 23,
        "modifiedAt": "2012-01-25T12:31:00+00:00"
      },
      {
        "name": "EQUIPESBRASIL_201803.ZIP",
        "size": 10225848,
        "modifiedAt": "2018-04-10T15:17:00+00:00"
      },
      {
        "name": "EQUIPESBRASIL_201804.ZIP",
        "size": 10129614,
        "modifiedAt": "2018-05-14T17:43:00+00:00"
      },
      {
        "name": "EQUIPESBRASIL_201805.ZIP",
        "size": 10105011,
        "modifiedAt": "2018-06-13T18:00:00+00:00"
      },
      {
        "name": "EQUIPESBRASIL_201806.ZIP",
        "size": 10119306,
        "modifiedAt": "2018-07-10T18:03:00+00:00"
      },
      {
        "name": "EQUIPESBRASIL_201807.ZIP",
        "size": 10178894,
        "modifiedAt": "2018-08-13T15:35:00+00:00"
      },
      {
        "name": "EQUIPESBRASIL_201808.ZIP",
        "size": 10300193,
        "modifiedAt": "2018-09-10T00:33:00+00:00"
      },
      {
        "name": "EQUIPESBRASIL_201809.ZIP",
        "size": 10215002,
        "modifiedAt": "2018-10-10T17:56:00+00:00"
      },
      {
        "name": "EQUIPESBRASIL_201810.ZIP",
        "size": 10340835,
        "modifiedAt": "2018-11-12T17:26:00+00:00"
      },
      {
        "name": "EQUIPESBRASIL_201811.ZIP",
        "size": 10226188,
        "modifiedAt": "2018-12-11T18:20:00+00:00"
      },
      {
        "name": "EQUIPESBRASIL_201812.ZIP",
        "size": 10232354,
        "modifiedAt": "2019-01-14T18:55:00+00:00"
      },
      {
        "name": "EQUIPESBRASIL_201901.ZIP",
        "size": 10158519,
        "modifiedAt": "2019-02-11T19:34:00+00:00"
      },
      {
        "name": "EQUIPESBRASIL_201902.ZIP",
        "size": 10049971,
        "modifiedAt": "2019-03-12T20:28:00+00:00"
      },
      {
        "name": "EQUIPESBRASIL_201903.ZIP",
        "size": 10186655,
        "modifiedAt": "2019-04-11T16:58:00+00:00"
      },
      {
        "name": "EQUIPESBRASIL_201904.ZIP",
        "size": 10108864,
        "modifiedAt": "2019-05-13T20:56:00+00:00"
      },
      {
        "name": "EQUIPESBRASIL_201905.ZIP",
        "size": 10129490,
        "modifiedAt": "2019-06-11T18:58:00+00:00"
      }
    ],
    "section": {
      "excludePrefixes": [
        "EQUIPESBRASIL_",
        "PROFISSIONAIS_BRASIL_",
        "CEP_BRASIL",
        "COOPERCBO",
        "GERENTEADMINCONTRATOS",
        "HOSPFILANTROPICOSBRASIL",
        "ESTNATJURBRASIL",
        "GESTORFEDERAL_",
        "SAMUBRASIL_",
        "S_UPSVAL_3",
        "CNES_NOTIF_",
        "CERTHOSPITALENSINOBRASIL",
        "MUNICIPIO_BRASIL",
        "TERCEIROSBRASIL_",
        "SAUDE_INDIGENA_",
        "SCNES_ARQUIVOS_",
        "FORNEC_",
        "CNESBRASIL",
        "EQUIPESESFBRASIL",
        "S_UPSVAL_7",
        "IMPORT_",
        "SISGERF",
        "ESTABELECIMENTO_LEITO_65",
        "BASE_DE_DADOS_CNES_",
        "LEIA_ME_"
      ],
      "excludeRegexes": [
        "/^TERCEIROSBRASIL\\d{2}/i",
        "/^SCNES\\d{4}/i"
      ]
    },
    "expected": [
      {
        "name": "_ctrl.txt",
        "displayName": "_ctrl.txt",
        "description": "–",
        "size": 23,
        "modifiedAt": "2012-01-25T12:31:00+00:00",
        "ym": null,
        "kind": "normal",
        "history": [],
        "ufBySigla": null,
        "ufHistoryBySigla": null
      },
      {
        "name": "DICIONARIO_DE_DADOS.ZIP",
        "displayName": "DICIONARIO_DE_DADOS.ZIP",
        "description": "–",
        "size": 602386,
        "modifiedAt": "2026-01-23T11:37:00+00:00",
        "ym": null,
        "kind": "normal",
        "history": [],
        "ufBySigla": null,
        "ufHistoryBySigla": null
      },
      {
        "name": "informe_cnes.txt",
        "displayName": "informe_cnes.txt",
        "description": "–",
        "size": 21,
        "modifiedAt": "2012-09-04T12:24:00+00:00",
        "ym": null,
        "kind": "normal",
        "history": [],
        "ufBySigla": null,
        "ufHistoryBySigla": null
      },
      {
        "name": "leia_me.doc",
        "displayName": "leia_me.doc",
        "description": "–",
        "size": 7451136,
        "modifiedAt": "2012-01-31T10:51:00+00:00",
        "ym": null,
        "kind": "normal",
        "history": [],
        "ufBySigla": null,
        "ufHistoryBySigla": null
      },
      {
        "name": "leia_me.pdf",
        "displayName": "leia_me.pdf",
        "description": "–",
        "size": 11183051,
        "modifiedAt": "2013-08-22T17:58:00+00:00",
        "ym": null,
        "kind": "normal",
        "history": [],
        "ufBySigla": null,
        "ufHistoryBySigla": null
      },
      {
        "name": "leia_me.zip",
        "displayName": "leia_me.zip",
        "description": "–",
        "size": 9364098,
        "modifiedAt": "2013-08-22T17:59:00+00:00",
        "ym": null,
        "kind": "normal",
        "history": [],
        "ufBySigla": null,
        "ufHistoryBySigla": null
      },
      {
        "name": "lista_arquivos_FTP.txt",
        "displayName": "lista_arquivos_FTP.txt",
        "description": "–",
        "size": 29801846,
        "modifiedAt": "2016-02-12T09:35:00+00:00",
        "ym": null,
        "kind": "normal",
        "history": [],
        "ufBySigla": null,
        "ufHistoryBySigla": null
      },
      {
        "name": "PASSO_A_PASSO_DA_APLICACAO_DA_PORTARIA_SAS_134_VERSAO_DRAC_FINAL_CNES.doc",
        "displayName": "PASSO_A_PASSO_DA_APLICACAO_DA_PORTARIA_SAS_134_VERSAO_DRAC_FINAL_CNES.doc",
        "description": "–",
        "size": 21504,
        "modifiedAt": "2011-05-03T10:55:00+00:00",
        "ym": null,
        "kind": "normal",
        "history": [],
        "ufBySigla": null,
        "ufHistoryBySigla": null
      },
      {
        "name": "Planilha dados cadastrais REGIONAIS -  Atualizada.xls",
        "displayName": "Planilha dados cadastrais REGIONAIS -  Atualizada.xls",
        "description": "–",
        "size": 47104,
        "modifiedAt": "2010-03-19T16:32:00+00:00",
        "ym": null,
        "kind": "normal",
        "history": [],
        "ufBySigla": null,
        "ufHistoryBySigla": null
      },
      {
        "name": "PROFDUPLOMESMOMUN202610.XLS",
        "displayName": "PROFDUPLOMESMOMUN202610.XLS",
        "description": "–",
        "size": 2242048,
        "modifiedAt": "2026-10-19T02:18:00+00:00",
        "ym": "202610",
        "kind": "versioned",
        "history": [],
        "ufBySigla": null,
        "ufHistoryBySigla": null
      },
      {
        "name": "PROFDUPLOMUNDIF202609.XLS",
        "displayName": "PROFDUPLOMUNDIF202609.XLS",
        "description": "–",
        "size": 1807360,
        "modifiedAt": "2026-10-15T11:53:00+00:00",
        "ym": "202609",
        "kind": "versioned",
        "history": [],
        "ufBySigla": null,
        "ufHistoryBySigla": null
      },
      {
        "name": "PSE202610.ZIP",
        "displayName": "PSE202610.ZIP",
        "description": "–",
        "size": 16873,
        "modifiedAt": "2026-10-29T16:03:00+00:00",
        "ym": "202610",
        "kind": "versioned",
        "history": [],
        "ufBySigla": null,
        "ufHistoryBySigla": null
      }
    ]
  },
  {
    "raw": [
      {
        "name": "DICIONARIO_DE_DADOS.ZIP",
        "size": 384546,
        "modifiedAt": "2018-08-24T15:56:00+00:00"
      },
      {
        "name": "GLOSSARIO_SCNES_GERAL.ZIP",
        "size": 41333,
        "modifiedAt": "2018-08-24T15:56:00+00:00"
      },
      {
        "name": "LEIA_ME_COMPLETO.ZIP",
        "size": 17781794,
        "modifiedAt": "2019-12-13T15:56:00+00:00"
      },
      {
        "name": "SCNES4000-ATUALIZACAO.ZIP",
        "size": 92192380,
        "modifiedAt": "2017-06-23T18:32:00+00:00"
      },
      {
        "name": "SCNES4000-COMPLETA.ZIP",
        "size": 121324810,
        "modifiedAt": "2017-06-23T18:32:00+00:00"
      },
      {
        "name": "SCNES4010-ATUALIZACAO.ZIP",
        "size": 92730988,
        "modifiedAt": "2017-08-25T15:18:00+00:00"
      },
      {
        "name": "SCNES4010-COMPLETA.ZIP",
        "size": 122567492,
        "modifiedAt": "2017-08-25T15:18:00+00:00"
      },
      {
        "name": "SCNES4020-ATUALIZACAO.ZIP",
        "size": 94538997,
        "modifiedAt": "2017-12-15T16:47:00+00:00"
      },
      {
        "name": "SCNES4020-COMPLETA.ZIP",
        "size": 124297238,
        "modifiedAt": "2017-12-15T16:47:00+00:00"
      },
      {
        "name": "SCNES4021-ATUALIZACAO.ZIP",
        "size": 95960778,
        "modifiedAt": "2017-12-29T11:20:00+00:00"
      },
      {
        "name": "SCNES4021-COMPLETA.ZIP",
        "size": 126899260,
        "modifiedAt": "2017-12-29T11:20:00+00:00"
      },
      {
        "name": "SCNES4030-ATUALIZACAO.ZIP",
        "size": 95229380,
        "modifiedAt": "2018-04-13T15:38:00+00:00"
      },
      {
        "name": "SCNES4030-COMPLETA.ZIP",
        "size": 125611656,
        "modifiedAt": "2018-04-13T15:38:00+00:00"
      },
      {
        "name": "SCNES4040-ATUALIZACAO.ZIP",
        "size": 96065244,
        "modifiedAt": "2018-08-24T15:56:00+00:00"
      },
      {
        "name": "SCNES4040-COMPLETA.ZIP",
        "size": 127019762,
        "modifiedAt": "2018-08-24T15:56:00+00:00"
      },
      {
        "name": "SCNES4800-ATUALIZACAO.ZIP",
        "size": 264053524,
        "modifiedAt": "2026-04-17T12:05:00+00:00"
      },
      {
        "name": "SCNES4800-COMPLETA.ZIP",
        "size": 406908178,
        "modifiedAt": "2026-04-17T12:07:00+00:00"
      },
      {
        "name": "SCNES4810-ATUALIZACAO.ZIP",
        "size": 267908888,
        "modifiedAt": "2026-05-18T11:34:00+00:00"
      },
      {
        "name": "SCNES4810-COMPLETA.ZIP",
        "size": 411955243,
        "modifiedAt": "2026-05-18T11:37:00+00:00"
      },
      {
        "name": "SCNES4820-ATUALIZACAO.ZIP",
        "size": 267905071,
        "modifiedAt": "2026-05-20T20:11:00+00:00"
      },
      {
        "name": "SCNES4820-COMPLETA.ZIP",
        "size": 412058618,
        "modifiedAt": "2026-05-20T20:14:00+00:00"
      },
      {
        "name": "SCNES4830-ATUALIZACAO.ZIP",
        "size": 267910460,
        "modifiedAt": "2026-05-31T11:26:00+00:00"
      },
      {
        "name": "SCNES4830-COMPLETA.ZIP",
        "size": 411932463,
        "modifiedAt": "2026-05-31T11:27:00+00:00"
      },
      {
        "name": "SCNES4840-ATUALIZACAO.ZIP",
        "size": 270212786,
        "modifiedAt": "2026-06-26T14:21:00+00:00"
      },
      {
        "name": "SCNES4840-COMPLETA.ZIP",
        "size": 415082461,
        "modifiedAt": "2026-06-26T14:31:00+00:00"
      },
      {
        "name": "SCNES4850-ATUALIZACAO.ZIP",
        "size": 261903438,
        "modifiedAt": "2026-08-26T20:52:00+00:00"
      },
      {
        "name": "SCNES4850-COMPLETA.ZIP",
        "size": 403575891,
        "modifiedAt": "2026-08-26T20:54:00+00:00"
      },
      {
        "name": "SCNES4860-ATUALIZACAO.ZIP",
        "size": 265801288,
        "modifiedAt": "2026-10-19T15:31:00+00:00"
      },
      {
        "name": "SCNES4860-COMPLETA.ZIP",
        "size": 409147267,
        "modifiedAt": "2026-10-19T15:33:00+00:00"
      },
      {
        "name": "SCNES_DOMINIOS.ZIP",
        "size": 1044453,
        "modifiedAt": "2018-08-24T15:56:00+00:00"
      }
    ],
    "section": {
      "includeRegexes": [
        "/^SCNES\\d{4}/i"
      ]
    },
    "expected": [
      {
        "name": "SCNES4860-ATUALIZACAO.ZIP",
        "displayName": "SCNES4860-ATUALIZACAO.ZIP",
        "description": "–",
        "size": 265801288,
        "modifiedAt": "2026-10-19T15:31:00+00:00",
        "ym": null,
        "kind": "normal",
        "history": [
          {
            "name": "SCNES4850-ATUALIZACAO.ZIP",
            "ym": null,
            "label": "4850",
            "size": 261903438,
            "modifiedAt": "2026-08-26T20:52:00+00:00"
          },
          {
            "name": "SCNES4840-ATUALIZACAO.ZIP",
            "ym": null,
            "label": "4840",
            "size": 270212786,
            "modifiedAt": "2026-06-26T14:21:00+00:00"
          },
          {
            "name": "SCNES4830-ATUALIZACAO.ZIP",
            "ym": null,
            "label": "4830",
            "size": 267910460,
            "modifiedAt": "2026-05-31T11:26:00+00:00"
          },
          {
            "name": "SCNES4820-ATUALIZACAO.ZIP",
            "ym": null,
            "label": "4820",
            "size": 267905071,
            "modifiedAt": "2026-05-20T20:11:00+00:00"
          },
          {
            "name": "SCNES4810-ATUALIZACAO.ZIP",
            "ym": null,
            "label": "4810",
            "size": 267908888,
            "modifiedAt": "2026-05-18T11:34:00+00:00"
          },
          {
            "name": "SCNES4800-ATUALIZACAO.ZIP",
            "ym": null,
            "label": "4800",
            "size": 264053524,
            "modifiedAt": "2026-04-17T12:05:00+00:00"
          },
          {
            "name": "SCNES4040-ATUALIZACAO.ZIP",
            "ym": null,
            "label": "4040",
            "size": 96065244,
            "modifiedAt": "2018-08-24T15:56:00+00:00"
          },
          {
            "name": "SCNES4030-ATUALIZACAO.ZIP",
            "ym": null,
            "label": "4030",
            "size": 95229380,
            "modifiedAt": "2018-04-13T15:38:00+00:00"
          },
          {
            "name": "SCNES4021-ATUALIZACAO.ZIP",
            "ym": null,
            "label": "4021",
            "size": 95960778,
            "modifiedAt": "2017-12-29T11:20:00+00:00"
          },
          {
            "name": "SCNES4020-ATUALIZACAO.ZIP",
            "ym": null,
            "label": "4020",
            "size": 94538997,
            "modifiedAt": "2017-12-15T16:47:00+00:00"
          },
          {
            "name": "SCNES4010-ATUALIZACAO.ZIP",
            "ym": null,
            "label": "4010",
            "size": 92730988,
            "modifiedAt": "2017-08-25T15:18:00+00:00"
          },
          {
            "name": "SCNES4000-ATUALIZACAO.ZIP",
            "ym": null,
            "label": "4000",
            "size": 92192380,
            "modifiedAt": "2017-06-23T18:32:00+00:00"
          }
        ],
        "ufBySigla": null,
        "ufHistoryBySigla": null
      },
      {
        "name": "SCNES4860-COMPLETA.ZIP",
        "displayName": "SCNES4860-COMPLETA.ZIP",
        "description": "–",
        "size": 409147267,
        "modifiedAt": "2026-10-19T15:33:00+00:00",
        "ym": null,
        "kind": "normal",
        "history": [
          {
            "name": "SCNES4850-COMPLETA.ZIP",
            "ym": null,
            "label": "4850",
            "size": 403575891,
            "modifiedAt": "2026-08-26T20:54:00+00:00"
          },
          {
            "name": "SCNES4840-COMPLETA.ZIP",
            "ym": null,
            "label": "4840",
            "size": 415082461,
            "modifiedAt": "2026-06-26T14:31:00+00:00"
          },
          {
            "name": "SCNES4830-COMPLETA.ZIP",
            "ym": null,
            "label": "4830",
            "size": 411932463,
            "modifiedAt": "2026-05-31T11:27:00+00:00"
          },
          {
            "name": "SCNES4820-COMPLETA.ZIP",
            "ym": null,
            "label": "4820",
            "size": 412058618,
            "modifiedAt": "2026-05-20T20:14:00+00:00"
          },
          {
            "name": "SCNES4810-COMPLETA.ZIP",
            "ym": null,
            "label": "4810",
            "size": 411955243,
            "modifiedAt": "2026-05-18T11:37:00+00:00"
          },
          {
            "name": "SCNES4800-COMPLETA.ZIP",
            "ym": null,
            "label": "4800",
            "size": 406908178,
            "modifiedAt": "2026-04-17T12:07:00+00:00"
          },
          {
            "name": "SCNES4040-COMPLETA.ZIP",
            "ym": null,
            "label": "4040",
            "size": 127019762,
            "modifiedAt": "2018-08-24T15:56:00+00:00"
          },
          {
            "name": "SCNES4030-COMPLETA.ZIP",
            "ym": null,
            "label": "4030",
            "size": 125611656,
            "modifiedAt": "2018-04-13T15:38:00+00:00"
          },
          {
            "name": "SCNES4021-COMPLETA.ZIP",
            "ym": null,
            "label": "4021",
            "size": 126899260,
            "modifiedAt": "2017-12-29T11:20:00+00:00"
          },
          {
            "name": "SCNES4020-COMPLETA.ZIP",
            "ym": null,
            "label": "4020",
            "size": 124297238,
            "modifiedAt": "2017-12-15T16:47:00+00:00"
          },
          {
            "name": "SCNES4010-COMPLETA.ZIP",
            "ym": null,
            "label": "4010",
            "size": 122567492,
            "modifiedAt": "2017-08-25T15:18:00+00:00"
          },
          {
            "name": "SCNES4000-COMPLETA.ZIP",
            "ym": null,
            "label": "4000",
            "size": 121324810,
            "modifiedAt": "2017-06-23T18:32:00+00:00"
          }
        ],
        "ufBySigla": null,
        "ufHistoryBySigla": null
      }
    ]
  },
  {
    "raw": [
      {
        "name": "SCNES_ARQUIVOS_AC_202610.ZIP",
        "size": 297569048,
        "modifiedAt": "2026-10-29T00:31:00+00:00"
      },
      {
        "name": "SCNES_ARQUIVOS_PR_202610.ZIP",
        "size": 306331123,
        "modifiedAt": "2026-10-29T00:57:00+00:00"
      },
      {
        "name": "SCNES_ARQUIVOS_SP_202610.ZIP",
        "size": 335400209,
        "modifiedAt": "2026-10-29T01:11:00+00:00"
      }
    ],
    "section": {
      "includePrefixes": [
        "SCNES_ARQUIVOS_"
      ],
      "descriptionRules": [
        {
          "description": "ARQUIVO ÚNICO/UF",
          "startsWith": "SCNES_ARQUIVOS_"
        }
      ]
    },
    "expected": [
      {
        "name": "SCNES_ARQUIVOS_SP_202610.ZIP",
        "displayName": "SCNES_ARQUIVOS_UF_202610.ZIP (selecionar UF)",
        "description": "ARQUIVO ÚNICO/UF",
        "size": 335400209,
        "modifiedAt": "2026-10-29T01:11:00+00:00",
        "ym": "202610",
        "kind": "uf_sigla",
        "history": [],
        "ufBySigla": {
          "AC": {
            "name": "SCNES_ARQUIVOS_AC_202610.ZIP",
            "size": 297569048,
            "modifiedAt": "2026-10-29T00:31:00+00:00",
            "ym": "202610"
          },
          "PR": {
            "name": "SCNES_ARQUIVOS_PR_202610.ZIP",
            "size": 306331123,
            "modifiedAt": "2026-10-29T00:57:00+00:00",
            "ym": "202610"
          },
          "SP": {
            "name": "SCNES_ARQUIVOS_SP_202610.ZIP",
            "size": 335400209,
            "modifiedAt": "2026-10-29T01:11:00+00:00",
            "ym": "202610"
          }
        },
        "ufHistoryBySigla": {}
      }
    ]
  },
  {
    "raw": [
      {
        "name": "PROFISSIONAIS_BRASIL_AC_202607.ZIP",
        "size": 126596514,
        "modifiedAt": "2026-08-11T16:22:00+00:00"
      },
      {
        "name": "PROFISSIONAIS_BRASIL_AC_202608.ZIP",
        "size": 126917138,
        "modifiedAt": "2026-09-11T21:05:00+00:00"
      },
      {
        "name": "PROFISSIONAIS_BRASIL_AC_202609.ZIP",
        "size": 127555203,
        "modifiedAt": "2026-10-14T16:12:00+00:00"
      },
      {
        "name": "PROFISSIONAIS_BRASIL_PR_202607.ZIP",
        "size": 135285104,
        "modifiedAt": "2026-08-11T17:40:00+00:00"
      },
      {
        "name": "PROFISSIONAIS_BRASIL_PR_202608.ZIP",
        "size": 135510855,
        "modifiedAt": "2026-09-11T22:25:00+00:00"
      },
      {
        "name": "PROFISSIONAIS_BRASIL_PR_202609.ZIP",
        "size": 136153793,
        "modifiedAt": "2026-10-14T17:27:00+00:00"
      }
    ],
    "section": {
      "includePrefixes": [
        "PROFISSIONAIS_BRASIL_"
      ],
      "descriptionRules": [
        {
          "description": "PROFISSIONAIS BRASIL",
          "startsWith": "PROFISSIONAIS_BRASIL_"
        }
      ]
    },
    "expected": [
      {
        "name": "PROFISSIONAIS_BRASIL_PR_202609.ZIP",
        "displayName": "PROFISSIONAIS_BRASIL_UF_202609.ZIP (selecionar UF)",
        "description": "PROFISSIONAIS BRASIL",
        "size": 136153793,
        "modifiedAt": "2026-10-14T17:27:00+00:00",
        "ym": "202609",
        "kind": "uf_sigla",
        "history": [],
        "ufBySigla": {
          "AC": {
            "name": "PROFISSIONAIS_BRASIL_AC_202609.ZIP",
            "size": 127555203,
            "modifiedAt": "2026-10-14T16:12:00+00:00",
            "ym": "202609"
          },
          "PR": {
            "name": "PROFISSIONAIS_BRASIL_PR_202609.ZIP",
            "size": 136153793,
            "modifiedAt": "2026-10-14T17:27:00+00:00",
            "ym": "202609"
          }
        },
        "ufHistoryBySigla": {
          "AC": [
            {
              "name": "PROFISSIONAIS_BRASIL_AC_202608.ZIP",
              "ym": "202608",
              "label": "2026/08"
            },
            {
              "name": "PROFISSIONAIS_BRASIL_AC_202607.ZIP",
              "ym": "202607",
              "label": "2026/07"
            }
          ],
          "PR": [
            {
              "name": "PROFISSIONAIS_BRASIL_PR_202608.ZIP",
              "ym": "202608",
              "label": "2026/08"
            },
            {
              "name": "PROFISSIONAIS_BRASIL_PR_202607.ZIP",
              "ym": "202607",
              "label": "2026/07"
            }
          ]
        }
      }
    ]
  }
]
JSON, true, 512, JSON_THROW_ON_ERROR);
    foreach ($cases as $i => $case) {
        $actual = json_decode(json_encode((new FileGrouper())->group($case['raw'], $case['section']), JSON_UNESCAPED_UNICODE), true);
        assert_same($case['expected'], $actual, "Amostra do legado $i divergiu");
    }
}];
