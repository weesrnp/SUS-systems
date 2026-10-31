<?php
declare(strict_types=1);

$currentYear = (int) date('Y');
?>
<div class="workspaces-container">

  <!-- ======================================================== -->
  <!-- WORKSPACE 1: CATÁLOGO GERAL (ARQUIVOS, TABELAS E NORMAS) -->
  <!-- ======================================================== -->
  <section class="ws-panel active" id="ws-catalog" role="tabpanel" aria-labelledby="tabCatalog">
    <!-- O conteúdo dinâmico do subgrupo (título, FTP, normativas, tabelas) é injetado aqui -->
    <div id="catalogDynamicContent" class="catalog-content-area">
      <!-- Skeleton inicial da primeira pintura -->
      <div class="content-header-skeleton shimmer-box" aria-busy="true">
        <div class="skeleton-line" style="width: 35%; height: 26px;"></div>
        <div class="skeleton-line" style="width: 55%; height: 16px;"></div>
      </div>
      <div class="table-card shimmer-box" aria-busy="true">
        <div class="skeleton-line" style="width: 25%; height: 20px;"></div>
        <div class="skeleton-line" style="width: 100%; height: 40px; margin-top: 1rem;"></div>
        <div class="skeleton-line" style="width: 100%; height: 40px;"></div>
        <div class="skeleton-line" style="width: 100%; height: 40px;"></div>
      </div>
    </div>
  </section>

  <!-- ======================================================== -->
  <!-- WORKSPACE 2: SIGTAP EXPLORER & COMPARATIVO DE COMPETÊNCIA -->
  <!-- ======================================================== -->
  <section class="ws-panel" id="ws-sigtap" role="tabpanel" aria-labelledby="tabSigtap">
    <div class="content-header">
      <div class="content-heading">
        <div class="breadcrumb-trail">
          <span class="crumb-root">Cadastros &amp; Tabelas</span>
          <span class="crumb-sep">/</span>
          <span class="crumb-current">SIGTAP</span>
        </div>
        <h1 class="content-title">SIGTAP — Consulta e Comparativo de Competências</h1>
        <div class="ftp-url-bar" title="URL do diretório FTP da Tabela Unificada">
          <span class="ftp-protocol">ftp://</span>ftp2.datasus.gov.br/pub/sistemas/tup/downloads/
        </div>
      </div>
      <div class="content-header-badges">
        <span class="source-badge badge-ftp" id="sigtapSourceBadge">Tabela Unificada Oficial</span>
      </div>
    </div>

    <!-- Barra de Controles e Filtros do SIGTAP -->
    <div class="controls-card">
      <div class="controls-header">
        <span class="controls-title">Pesquisa na Tabela Unificada Vigente</span>
      </div>

      <div class="controls-grid">
        <div class="field-group">
          <label for="sigtapCompNew" class="field-label">Competência</label>
          <select id="sigtapCompNew" class="field-control font-mono"></select>
        </div>

        <div class="field-group is-wide">
          <label for="sigtapQuery" class="field-label">Código ou nome do procedimento</label>
          <div class="input-with-icon">
            <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <circle cx="11" cy="11" r="8"></circle>
              <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input type="search" id="sigtapQuery" class="field-control" placeholder="Ex.: 0301010072 ou consulta médica" autocomplete="off">
          </div>
        </div>

        <div class="field-group">
          <label for="sigtapGrupo" class="field-label">Grupo</label>
          <select id="sigtapGrupo" class="field-control">
            <option value="">Todos os grupos</option>
            <option value="01">01 · Promoção e prevenção em saúde</option>
            <option value="02">02 · Procedimentos com finalidade diagnóstica</option>
            <option value="03">03 · Procedimentos clínicos</option>
            <option value="04">04 · Procedimentos cirúrgicos</option>
            <option value="05">05 · Transplantes de órgãos, tecidos e células</option>
            <option value="06">06 · Medicamentos</option>
            <option value="07">07 · Órteses, próteses e materiais especiais</option>
            <option value="08">08 · Ações complementares da atenção à saúde</option>
            <option value="09">09 · Ofertas de cuidados integrados</option>
          </select>
        </div>

        <div class="field-group">
          <label for="sigtapFin" class="field-label">Financiamento</label>
          <select id="sigtapFin" class="field-control">
            <option value="">Todos os financiamentos</option>
            <option value="01">01 · Atenção Básica (PAB)</option>
            <option value="02">02 · Assistência Farmacêutica</option>
            <option value="04">04 · FAEC</option>
            <option value="05">05 · Incentivo - MAC</option>
            <option value="06">06 · Média e Alta Complexidade (MAC)</option>
            <option value="07">07 · Vigilância em Saúde</option>
            <option value="08">08 · Gestão do SUS</option>
          </select>
        </div>

        <div class="field-group field-action">
          <button type="button" class="btn-primary btn-search-sigtap" id="btnSigtapSearch">
            <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <circle cx="11" cy="11" r="8"></circle>
              <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <span>Pesquisar</span>
          </button>
        </div>
      </div>

      <!-- Divisor e Linha de Comparativo (Diff) -->
      <div class="diff-section-divider">
        <span class="divider-text">Comparativo entre Duas Competências (Diff Oficial)</span>
      </div>

      <div class="diff-controls-row">
        <div class="field-group">
          <label for="sigtapCompOld" class="field-label">Comparar a competência selecionada acima com:</label>
          <select id="sigtapCompOld" class="field-control font-mono"></select>
        </div>

        <div class="diff-actions">
          <button type="button" class="btn-secondary" id="btnSigtapDiff">
            <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <polyline points="16 3 21 3 21 8"></polyline>
              <line x1="4" y1="20" x2="21" y2="3"></line>
              <polyline points="21 16 21 21 16 21"></polyline>
              <line x1="15" y1="15" x2="21" y2="21"></line>
              <line x1="4" y1="4" x2="9" y2="9"></line>
            </svg>
            <span>Calcular Reajustes &amp; Alterações</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Resultados do SIGTAP (Tabela de Procedimentos ou Comparativo Diff) -->
    <div id="sigtapResultsContainer" class="sigtap-results-area"></div>
  </section>

  <!-- ======================================================== -->
  <!-- WORKSPACE 3: REMESSAS SIA (LOGCARGA MUNICIPAL) -->
  <!-- ======================================================== -->
  <section class="ws-panel" id="ws-remessa" role="tabpanel" aria-labelledby="tabRemessa">
    <div class="content-header">
      <div class="content-heading">
        <div class="breadcrumb-trail">
          <span class="crumb-root">Faturamento &amp; Produção</span>
          <span class="crumb-sep">/</span>
          <span class="crumb-current">SIA / LogCarga</span>
        </div>
        <h1 class="content-title">Remessas do SIA — Acompanhamento de Processamento (LogCarga)</h1>
        <div class="ftp-url-bar" title="URL do diretório FTP de logs de carga do SIA">
          <span class="ftp-protocol">ftp://</span>arpoador.datasus.gov.br/siasus/LogCarga/
        </div>
      </div>
      <div class="content-header-badges">
        <span class="source-badge badge-ftp">FTP LogCarga DataSUS</span>
      </div>
    </div>

    <!-- Formulário de Consulta de Remessas por IBGE / Ano -->
    <div class="controls-card">
      <form id="remessaForm" class="remessa-form-row" action="#">
        <div class="field-group">
          <label for="remIbgeInput" class="field-label">Código IBGE do município (6 ou 7 dígitos)</label>
          <div class="input-with-icon">
            <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
              <circle cx="12" cy="10" r="3"></circle>
            </svg>
            <input type="text" id="remIbgeInput" class="field-control font-mono" value="410965" maxlength="7" inputmode="numeric" pattern="\d{6,7}" required title="Informe o código IBGE com 6 ou 7 dígitos">
          </div>
        </div>

        <div class="field-group">
          <label for="remAnoSelect" class="field-label">Ano da competência</label>
          <select id="remAnoSelect" class="field-control font-mono">
            <?php for ($y = 0; $y < 4; $y++): ?>
              <?php $yr = $currentYear - $y; ?>
              <option value="<?= e((string) $yr) ?>"><?= e((string) $yr) ?></option>
            <?php endfor; ?>
          </select>
        </div>

        <div class="field-group field-action">
          <button type="submit" class="btn-primary" id="btnRemessaSubmit">
            <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
              <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            <span>Consultar Remessas</span>
          </button>
        </div>
      </form>

      <!-- Chips de atalho para municípios comuns -->
      <div class="quick-ibge-row">
        <span class="quick-ibge-label">Atalhos rápidos:</span>
        <button type="button" class="quick-ibge-chip active" data-ibge="410965">Honório Serpa - PR (410965)</button>
        <button type="button" class="quick-ibge-chip" data-ibge="410690">Curitiba - PR (410690)</button>
        <button type="button" class="quick-ibge-chip" data-ibge="355030">São Paulo - SP (355030)</button>
        <button type="button" class="quick-ibge-chip" data-ibge="330455">Rio de Janeiro - RJ (330455)</button>
        <button type="button" class="quick-ibge-chip" data-ibge="310620">Belo Horizonte - MG (310620)</button>
      </div>
    </div>

    <!-- Resultados do LogCarga -->
    <div id="remessaResultsContainer" class="remessa-results-area"></div>
  </section>

</div>
