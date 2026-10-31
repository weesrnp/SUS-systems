# Relatório de Garantia de Qualidade (QA) — SUS-systems 3.0

**Data:** 29 de Outubro de 2026  
**Ambiente de Testes:** Container Docker `sus-systems-app` (PHP 8.3 Apache)  
**URL do Portal:** `http://localhost:8090`  
**Portal Maestri:** `SUS App`  
**Viewports Testados:** Desktop (1440×892 / 1900×1192) e Mobile (390×844)  
**Responsável QA:** Midia  

---

## 1. Escopo e Matriz de Testes

| ID | Fluxo / Funcionalidade | Cenário Avaliado | Resultado |
| :---: | :--- | :--- | :---: |
| **TC01** | **Bootstrap & SSR** | Carregamento inicial da página raiz (`/`), renderização dos templates PHP e injeção do catálogo inicial via JSON | **PASSOU** |
| **TC02** | **Navegação & Deep-links** | Troca de sistemas e subgrupos via hash router (`#/SIA/BPA`, `#/SIA/FPO`, `#/sigtap`, `#/remessa`) | **PASSOU** |
| **TC03** | **Filtro de Arquivos** | Busca textual em tempo real no campo de filtro de tabelas de arquivos FTP | **PASSOU** |
| **TC04** | **Histórico de Versões** | Agrupamento de arquivos com histórico retroativo (botão "Anteriores") | **PASSOU** |
| **TC05** | **SIGTAP — Consulta** | Pesquisa de procedimentos por código ou descrição, com filtros por grupo e financiamento | **PASSOU** |
| **TC06** | **SIGTAP — Comparativo (Diff)** | Cálculo posicional de inclusões, exclusões, reajustes e alterações de regras entre competências | **PASSOU** |
| **TC07** | **Remessas SIA (LogCarga)** | Consulta direta e nativa via FTP de remessas e logs por IBGE e ano, com atalhos de municípios | **PASSOU** |
| **TC08** | **Tema Claro / Escuro** | Alternância instantânea via botão na barra superior e persistência de preferência | **PASSOU** |
| **TC09** | **Busca Global (Ctrl+K)** | Abertura do modal Command Palette por teclado (`Ctrl+K`) e clique, navegação e fechamento com `ESC` | **PASSOU** |
| **TC10** | **Responsividade Mobile (390×844)** | Menu lateral adaptado como drawer recolhível com botão de fechar, ajuste de tabelas e inputs | **PASSOU** |

---

## 2. Detalhamento dos Resultados por Módulo

### 2.1. Catálogo FTP e Tabela de Arquivos
- **Deep-links**: Ao acessar `http://localhost:8090/#/SIA/BPA`, a view de arquivos foi montada diretamente com as seções e arquivos correspondentes (`BPA_LEIAME.txt`, `BPAMAG0500.exe`, `BDSIA202610a.exe`).
- **Filtro Instantâneo**: No subgrupo `SIA / FPO`, a digitação de `"Instalador"` filtrou instantaneamente a lista, isolando `FPOMAG_Instalador_0100.exe`.
- **Histórico**: Subgrupos com versões anteriores exibem corretamente os botões (ex.: `Anteriores (230)` e `Anteriores (1)`).

### 2.2. SIGTAP (Explorer & Diff)
- **Consulta**: O endpoint `/api/sigtap/explore?comp=202610&q=consulta` retornou com precisão 33 procedimentos na competência selecionada.
- **Comparativo (Diff)**: A comparação entre as competências `10/2026` e `09/2026` via `/api/sigtap/diff` identificou:
  - 17 inclusões;
  - 2 exclusões;
  - 1 reajuste de valor na tabela SA/SH/SP (`03.04.02.012-5 - QUIMIOTERAPIA DO CARCINOMA DE ADRENAL AVANÇADO`);
  - 3 alterações de regras/complexidade/idade.
- A tabela de comparativo oficial foi renderizada no container de resultados com badges coloridos de delta.

### 2.3. Remessas do SIA
- Acesso pela rota `#/remessa` exibe a tela de acompanhamento de processamento mensal via FTP `LogCarga<ano>`.
- Atalhos rápidos preenchem automaticamente municípios (ex.: Honório Serpa - PR `410965`, Curitiba - PR `410690`, São Paulo - SP `355030`).
- A tabela lista os arquivos mensais disponíveis com botão "Baixar Log".

### 2.4. Acessibilidade e Tema
- Alternância de tema via botão superior atualiza o atributo `data-theme="dark"` no elemento `<html>` e notifica leitores de tela na live region (`"Modo escuro ativado."`).
- A janela de busca rápida (Ctrl+K) captura o foco imediatamente no campo de busca e responde ao evento `Escape` fechando sem efeitos colaterais.

### 2.5. Mobile (390×844)
- Na resolução de smartphone, a topbar contrai e exibe o botão hamburguer para acionar o menu lateral.
- A navegação lateral atua como gaveta com botão explícito de fechamento (`Fechar menu lateral`).

---

## 3. Registro de Bugs Encontrados para Noticia

### 🐛 BUG-QA-01: Decodificação de espaços no roteador por hash para subgrupos com nomes compostos

- **Status:** **CORRIGIDO E VALIDADO NO PORTAL**
- **Componentes envolvidos:** `templates/partials/sidebar.php` e `public/assets/js/app.js`
- **Passos para reproduzir:**
  1. Abrir a aplicação em `http://localhost:8090/`.
  2. No menu lateral, navegar até `CNES` e clicar no subgrupo **"Base de dados"** ou **"Aplicativo SCNES"**.
  3. Ou navegar até `VIG_IMUNIZACAO_AMBIENTAL` e clicar em **"PNI (histórico)"**.
- **Comportamento esperado:**
  O sistema deve carregar os arquivos e seções do subgrupo normalmente, requisitando:  
  `GET /api/subgroup-data?group_id=CNES&sub_name=Base%20de%20dados`
- **Comportamento obtido:**
  O sistema exibe o card de erro:  
  `"Não foi possível carregar Base+de+dados. Subgrupo CNES / Base+de+dados não encontrado (404)."`
- **Causa Raiz Identificada:**
  1. No template `templates/partials/sidebar.php`, o link é gerado com a função PHP `urlencode()`, que codifica espaços como sinal de mais: `href="#/CNES/Base+de+dados"`.
  2. No roteador JavaScript `public/assets/js/app.js` (`parseHash()`), a função padrão `decodeURIComponent(parts.slice(1).join('/'))` decodifica sequências percentuais (`%20`), mas **não converte o sinal `+` em espaço** (o `+` só representa espaço no padrão MIME `application/x-www-form-urlencoded` de query string).
  3. Como consequência, o valor passado para o backend no parâmetro `sub_name` é `"Base+de+dados"` literal, falhando na busca em `Catalog::findSubgroup()`.
- **Solução Recomendada:**
  - **No JavaScript (`app.js`):** Ajustar a leitura do hash em `parseHash()` substituindo o `+` antes de decodificar:
    ```javascript
    sub: decodeURIComponent(parts.slice(1).join('/').replace(/\+/g, ' '))
    ```
  - **OU no PHP (`templates/partials/sidebar.php`):** Substituir `urlencode(...)` por `rawurlencode(...)`, gerando URLs com `%20` padrão RFC 3986:
    ```php
    href="#/<?= e($group['id']) ?>/<?= rawurlencode($sub['name']) ?>"
    ```

---

## 4. Validação em Produção Serverless (Vercel Preview)

**Data:** 30 de Outubro de 2026  
**Ambiente:** Vercel Serverless Functions (`vercel-php@0.7.4` / PHP 8.3.8) na região **São Paulo (`gru1`)**  
**URL de Preview:** `https://sus-systems-git-feat-vercel-an-91ec9f-wesleis-projects-3b1e95bc.vercel.app/`  
**Viewports:** Desktop (1440×892) e Mobile Pequeno (360×800)  

### 4.1. Matriz de Testes em Produção Serverless

| Fluxo / Funcionalidade | Cenário / Entrada | Comportamento Observado | Tempo de Resposta | Status |
| :--- | :--- | :--- | :---: | :---: |
| **Home & SSR** | Acesso inicial com cabeçalho de share Vercel | Renderização instantânea do shell HTML, montagem do catálogo com 6 grupos e status dos clusters FTP | < 120 ms | **PASSOU** |
| **Catálogo SIA / BPA** | Rota `#/SIA/BPA` | Carregamento da listagem de versões, `BPA_LEIAME.txt`, `BPAMAG0500.exe` e 230 versões anteriores | ~1,8 s (FTP direto) | **PASSOU** |
| **Catálogo CNES** | Subgrupo `CNES / Base de dados` | Acesso com nome composto com espaços via `rawurlencode()`, listagem de 116 arquivos de base | ~1,5 s | **PASSOU** |
| **SIGTAP Explorer** | Busca pelo termo `"folico"` na comp. `202610` | Retorno imediato do procedimento `01.01.04.010-5` (DOSAGEM DE ACIDO FOLICO) | < 0,8 s | **PASSOU** |
| **SIGTAP Comparativo** | Diff entre competências `10/2026` e `09/2026` | Cálculo posicional completo: 17 adições, 1 reajuste, 3 regras alteradas e 2 exclusões | ~0,8 s | **PASSOU** |
| **Remessas SIA** | Município Honório Serpa - PR (`410965`), ano `2025` | Leitura de remessas e logs de processamento DataSUS via FTP nativo | 3,5 s (frio) / 0,27 s (quente) | **PASSOU** |
| **Download por Streaming** | Arquivo `BPA_LEIAME.txt` via `/api/ftp-download` | HTTP 200 OK, cabeçalhos `X-Download-Mode: FTP_DIRECT_STREAM`, `X-Powered-By: PHP/8.3.8` | Streaming imediato | **PASSOU** |
| **Mobile (360×800)** | Resolução ultra compacta (360 px de largura) | Menu hambúrguer abre drawer lateral com botão `Fechar menu lateral`; `document.documentElement.scrollWidth <= 360px` sem overflow horizontal | Instantâneo | **PASSOU** |

### 4.2. Conclusões do QA Serverless
1. **Conectividade FTP Direta:** O runtime serverless na região `gru1` conecta diretamente aos servidores FTP do Ministério da Saúde (`ftp.datasus.gov.br`), eliminando a necessidade obrigatória de ponte HTTPS em produção.
2. **Desempenho em `gru1`:** A latência na região de São Paulo é até 5× menor em comparação a `iad1` (listagem SIA reduzida de 10,5 s para 1,8 s).
3. **Persistência de Cache:** O cache de arquivos e metadados opera perfeitamente em `/tmp/sus-cache`, garantindo aceleração em execuções subsequentes na mesma instância lambda.
4. **Interface e Acessibilidade:** Em 360 px a aplicação adapta-se responsivamente sem quebras visuais ou scroll horizontal indesejado.

