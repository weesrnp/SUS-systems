<p align="center">
  <img src="assets/banner.svg" alt="SUS-systems Banner" width="100%">
</p>

<p align="center">
  <a href="https://www.python.org/downloads/"><img src="https://img.shields.io/badge/Python-3.10%2B-3776AB?style=for-the-badge&logo=python&logoColor=white" alt="Python 3.10+"></a>
  <a href="https://fastapi.tiangolo.com/"><img src="https://img.shields.io/badge/FastAPI-0.115%2B-009688?style=for-the-badge&logo=fastapi&logoColor=white" alt="FastAPI"></a>
  <a href="https://www.docker.com/"><img src="https://img.shields.io/badge/Docker-Ready-2496ED?style=for-the-badge&logo=docker&logoColor=white" alt="Docker"></a>
  <a href="https://echarts.apache.org/"><img src="https://img.shields.io/badge/Apache_ECharts-5.5-AA344D?style=for-the-badge&logo=apacheecharts&logoColor=white" alt="Apache ECharts"></a>
  <a href="LICENSE"><img src="https://img.shields.io/badge/License-MIT-10b981?style=for-the-badge" alt="MIT License"></a>
</p>

---

## 📌 Visão Geral

O **SUS-systems** é uma plataforma moderna, leve e open-source para **extração de arquivos massivos, telemetria e auditoria de dados do Datasus** (Ministério da Saúde). 

Construído para resolver os entraves crônicos de acesso às bases públicas de saúde no Brasil, o projeto substitui a dependência de clientes FTP legados e pontes serverless instáveis por um motor local assíncrono em FastAPI, com interface reativa, animações fluidas e módulos de auditoria analítica.

---

## 🚨 O Problema que o SUS-systems Resolve

```mermaid
flowchart LR
    A["Navegador Moderno<br/>(Chrome/Edge/Firefox)"] -- "❌ Bloqueia portas ftp://" --> B["FTP Legado Datasus<br/>(arpoador / ftp / ftp2)"]
    A -- "❌ Erro 4.5MB Payload Limit" --> C["Proxies em Nuvem / Vercel"]
    
    A ==> "✅ HTTP 1.1 Chunked Stream" ==> D["SUS-systems (Local :8090)"]
    D ==> "✅ Passive RFC 959 / 64KB" ==> B
```

1. **Fim do suporte a FTP nos navegadores modernos**: Navegadores como Google Chrome, Microsoft Edge e Firefox descontinuaram nativamente o protocolo `ftp://`.
2. **Queda de conexões em soluções serverless**: Aplicações web tradicionais (como Vercel/AWS Lambda) impõem limites estritos de payload (4.5MB a 10MB) e timeouts de 10 a 15 segundos. Isso inviabiliza o download de bases do Datasus (CNES, SIA, bases DBC e ZIPs que frequentemente ultrapassam 50MB a 500MB).
3. **Formatos posicionais e layout legado**: Arquivos como a tabela unificada do **SIGTAP** (`tb_procedimento.txt`) possuem 330 colunas posicionais sem cabeçalho amigável, exigindo softwares antigos de 32 bits ou scripts manuais complexos.
4. **Desconexão com novas diretrizes da APS**: Profissionais e gestores necessitavam de uma ferramenta direta para auditar a conformidade de procedimentos frente à nova **Portaria GM/MS nº 7.639/2025**.

---

## ✨ Funcionalidades Principais

- ⚡ **Passive FTP Streaming Engine**: Conexão com os clusters oficiais do Datasus (`arpoador.datasus.gov.br`, `ftp.datasus.gov.br`, `ftp2.datasus.gov.br`) com parser automático para listagens no padrão IIS Windows DOS e streaming em chunks de 64KB via HTTP.
- 📊 **SIGTAP Positional Parser**: Leitor cirúrgico de 330 colunas para procedimentos do SUS, incluindo busca instantânea por código ou nome, agrupamento por complexidade e conferência de instrumentos de registro (BPA, APAC).
- ⚖️ **Auditoria Portaria GM/MS 7.639/2025**: Matriz analítica de conformidade com indicadores para equipes de Saúde da Família (eSF), equipes de Atenção Primária (eAP) e equipes Multiprofissionais (eMulti).
- 🎨 **Interface Dark-First de Alta Fidelidade**: UI desenvolvida com CSS Tokens semânticos, *glider* deslizante no cabeçalho, micro-interações suaves com **Anime.js** e gráficos analíticos interativos via **Apache ECharts 5.5**.
- 🔒 **100% Local & Respeito à Privacidade**: Execução na máquina do usuário ou rede local sem dependência de serviços externos pagos, banco de dados pesado ou rastreadores de telemetria.

---

## 🏛️ Sistemas do Datasus Mapeados e Integrados

| Sistema | Sigla / Escopo | Fontes Datasus | Descrição |
| :--- | :--- | :--- | :--- |
| **SIA** | Sistema de Informações Ambulatoriais | `/dissemin/publicos/SIASUS/200801_/Dados/` | Produção ambulatorial, BPA, APAC e boletins por UF e competência |
| **SIGTAP** | Sistema de Gerenciamento da Tabela SUS | `/dissemin/publicos/SIGTAP/` | Tabela unificada de procedimentos, OPM, medicamentos e valores |
| **CNES** | Cadastro Nacional de Estabelecimentos | `/cnes/` e `/dissemin/publicos/CNES/` | Bases cadastrais de estabelecimentos, equipes, leitos e profissionais |
| **e-SUS APS**| Prontuário Eletrônico & Atenção Primária | `/esus/` | Instaladores oficiais, utilitários de migração e pacotes do e-SUS PEC |
| **PNI & SISVAN** | Imunização e Vigilância Alimentar | `/pni/` e `/sisvan/` | Calendários vacinais, boletins e dados de estado nutricional |
| **TabWin & DBC** | Tabuladores e Dicionários de Dados | `/tabwin/` | Executáveis do TabWin, arquivos DEF, CNV e dicionários estruturais |
| **Portaria 7.639** | Matriz de Conformidade APS 2025/2026 | *Módulo interno analítico* | Validação de elegibilidade de procedimentos para financiamento da APS |

---

## 🚀 Como Executar

### 1. No Windows (1 Clique)

Dê um duplo clique no arquivo [`run_windows.bat`](run_windows.bat) ou execute no prompt de comando:

```cmd
run_windows.bat
```

> O script verifica o Python, cria automaticamente um ambiente virtual `.venv`, instala as dependências necessárias e abre seu navegador em `http://127.0.0.1:8090`.

---

### 2. Com Docker / Docker Compose

Se preferir rodar isolado em container:

```bash
# Iniciar container
docker compose up -d --build

# Acessar aplicacao
open http://localhost:8090
```

Para visualizar os logs:
```bash
docker compose logs -f
```

---

### 3. Manualmente via Terminal (Linux / macOS / Windows)

```bash
# 1. Clonar o repositorio
git clone https://github.com/seu-usuario/SUS-systems.git
cd SUS-systems

# 2. Criar e ativar o ambiente virtual
python3 -m venv .venv
source .venv/bin/activate  # No Windows: .venv\Scripts\activate

# 3. Instalar dependencias
pip install -r requirements.txt

# 4. Iniciar o servidor FastAPI
python -m uvicorn app.main:app --host 127.0.0.1 --port 8090 --reload
```

Abra seu navegador em: [`http://127.0.0.1:8090`](http://127.0.0.1:8090)

---

## 🛠️ Estrutura do Projeto

```
SUS-systems/
├── assets/
│   └── banner.svg               # Banner vetorizado em alta resolução
├── app/
│   ├── static/
│   │   ├── index.html           # SPA reativa com Dark Mode e Glider
│   │   └── banner.svg           # Cópia estática do banner
│   ├── catalog.py               # Catálogo centralizado de diretórios FTP do Datasus
│   ├── ftp_engine.py            # Cliente RFC 959 passivo com streaming HTTP
│   ├── sigtap_engine.py         # Leitor posicional de 330 colunas do SIGTAP
│   ├── municipal_engine.py      # Matriz de conformidade da Portaria GM/MS 7.639
│   └── main.py                  # Servidor e rotas REST do FastAPI
├── Dockerfile                   # Imagem otimizada baseada em Python 3.12-slim
├── docker-compose.yml           # Configuração de orquestração local
├── run_windows.bat              # Inicializador automático para Windows
├── run_linux.sh                 # Inicializador automático para Linux/macOS
├── requirements.txt             # Dependências Python mínimas (FastAPI, Uvicorn, HTTPX)
├── verify_app.py                # Suíte de verificação e testes de sanidade
├── .gitignore                   # Regras de exclusão do Git
├── LICENSE                      # Licença MIT
└── README.md                    # Documentação do projeto
```

---

## 📡 Endpoints da API REST

A API do **SUS-systems** pode ser consumida programaticamente ou integrada a pipelines de dados:

| Método | Endpoint | Parâmetros | Descrição |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/systems` | — | Lista todos os sistemas SUS configurados e metadados |
| `GET` | `/api/browse` | `system_id`, `path` | Lista arquivos e subpastas de um sistema no FTP |
| `GET` | `/api/download` | `system_id`, `file_path` | Realiza o download/streaming direto de qualquer arquivo |
| `GET` | `/api/sigtap/search` | `query`, `competencia` | Busca procedimentos na tabela SIGTAP por código ou nome |
| `GET` | `/api/municipal/matrix` | — | Retorna a matriz de auditoria da Portaria 7.639/2025 |
| `GET` | `/api/health` | — | Health check com status dos clusters FTP e memória |

Documentação interativa Swagger disponível em: [`http://127.0.0.1:8090/docs`](http://127.0.0.1:8090/docs)

---

## ⚖️ Conformidade Legal & Transparência

- **Lei de Acesso à Informação (LAI - Lei nº 12.527/2011)**: Todos os dados acessados pelo SUS-systems provêm de diretórios públicos do Departamento de Informática do Sistema Único de Saúde (Datasus).
- **LGPD (Lei Geral de Proteção de Dados - Lei nº 13.709/2018)**: As bases disseminadas publicamente nos diretórios FTP consultados são anonimizadas ou agregadas por competência e estabelecimento, em estrita conformidade com as diretrizes do Ministério da Saúde.
- **Isenção de Vínculo Governamental**: Este é um projeto de código aberto e iniciativa cívica independente, sem afiliação direta com o Ministério da Saúde ou Datasus.

---

## 🤝 Contribuições

Contribuições são muito bem-vindas! Se você deseja adicionar novos sistemas, otimizar decodificadores DBC ou aprimorar a interface:

1. Faça um Fork do projeto
2. Crie uma branch para sua funcionalidade (`git checkout -b feature/nova-funcionalidade`)
3. Faça o commit das alterações (`git commit -m 'feat: adiciona suporte a novo sistema'`)
4. Faça o push para a branch (`git push origin feature/nova-funcionalidade`)
5. Abra um Pull Request

---

## 📄 Licença

Distribuído sob a licença **MIT**. Consulte o arquivo [`LICENSE`](LICENSE) para mais detalhes.
