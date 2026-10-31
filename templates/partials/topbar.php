<?php
declare(strict_types=1);
?>
<header class="app-topbar" id="appTopbar">
  <div class="topbar-left">
    <!-- Botão mobile para abrir o menu lateral -->
    <button type="button" class="icon-btn mobile-menu-btn" id="btnToggleSidebarMobile" aria-label="Abrir menu de navegação" aria-controls="appSidebar" aria-expanded="false">
      <svg class="ui-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <line x1="3" y1="12" x2="21" y2="12"></line>
        <line x1="3" y1="6" x2="21" y2="6"></line>
        <line x1="3" y1="18" x2="21" y2="18"></line>
      </svg>
    </button>

    <!-- Botão desktop para alternar recolhimento da barra lateral -->
    <button type="button" class="icon-btn desktop-collapse-btn" id="btnToggleSidebarDesktop" title="Recolher / expandir barra lateral (Ctrl+[)" aria-label="Recolher ou expandir menu lateral">
      <svg class="ui-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
        <line x1="9" y1="3" x2="9" y2="21"></line>
      </svg>
    </button>

    <!-- Marca e emblema institucional SUS-systems -->
    <a href="#/SIA/BPA" class="brand-link" id="btnHomeLogo" aria-label="SUS-systems 3.0: ir para o início">
      <span class="brand-emblem" aria-hidden="true">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M9 3H15V9H21V15H15V21H9V15H3V9H9V3Z" fill="currentColor"/>
        </svg>
      </span>
      <span class="brand-text">
        <span class="brand-sus">SUS</span><span class="brand-systems">-systems</span>
      </span>
      <span class="brand-badge" aria-label="Versão 3.0">v3.0</span>
    </a>

    <!-- Município ativo em destaque (Operate Mode) -->
    <div class="mun-pill" title="Município padrão para faturamento e remessas">
      <span class="mun-dot" aria-hidden="true"></span>
      <span class="mun-name">Honório Serpa - PR</span>
      <span class="mun-sep">·</span>
      <span class="mun-ibge">IBGE 410965</span>
    </div>

    <!-- Indicador discreto de telemetria de conexão DataSUS (via ponte HTTPS / offline) -->
    <span class="network-mode-badge" id="topbarModeBadge" style="display:none;" aria-live="polite"></span>
  </div>

  <!-- Segmented Workspace Switcher (Centro) -->
  <nav class="topbar-center workspace-nav" id="topWorkspaceNav" aria-label="Áreas de trabalho">
    <div class="workspace-tabs-group" role="tablist">
      <button type="button" role="tab" class="ws-tab active" id="tabCatalog" data-ws="catalog" aria-selected="true" aria-controls="ws-catalog">
        <svg class="tab-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
          <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
        </svg>
        <span>Catálogo FTP</span>
      </button>

      <button type="button" role="tab" class="ws-tab" id="tabSigtap" data-ws="sigtap" aria-selected="false" aria-controls="ws-sigtap">
        <svg class="tab-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
          <line x1="3" y1="9" x2="21" y2="9"></line>
          <line x1="3" y1="15" x2="21" y2="15"></line>
          <line x1="9" y1="3" x2="9" y2="21"></line>
          <line x1="15" y1="3" x2="15" y2="21"></line>
        </svg>
        <span>SIGTAP Explorer &amp; Diff</span>
      </button>

      <button type="button" role="tab" class="ws-tab" id="tabRemessa" data-ws="remessa" aria-selected="false" aria-controls="ws-remessa">
        <svg class="tab-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
          <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
          <line x1="12" y1="22.08" x2="12" y2="12"></line>
        </svg>
        <span>Remessas SIA</span>
      </button>
    </div>
  </nav>

  <!-- Utilidades da direita: Busca Ctrl+K, Atualizar FTP, Alternador de Tema -->
  <div class="topbar-right">
    <!-- Botão de busca rápida global (Ctrl+K) -->
    <button type="button" class="command-search-btn" id="btnOpenCommandPalette" aria-label="Abrir busca global (Ctrl+K)">
      <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="11" cy="11" r="8"></circle>
        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
      </svg>
      <span class="search-label">Buscar sistemas...</span>
      <kbd class="kbd-shortcut"><span class="kbd-mod">Ctrl</span> K</kbd>
    </button>

    <!-- Atualizar dados / limpar cache da tela atual -->
    <button type="button" class="icon-btn" id="btnForceRefresh" title="Recarregar dados do FTP (ignora cache)" aria-label="Recarregar tela atual do FTP">
      <svg id="iconRefresh" class="ui-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path>
        <path d="M3 3v5h5"></path>
        <path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"></path>
        <path d="M16 16h5v5"></path>
      </svg>
    </button>

    <!-- Alternador de Tema Claro / Escuro -->
    <button type="button" class="icon-btn" id="btnThemeToggle" title="Alternar modo escuro / claro" aria-label="Ativar modo escuro">
      <!-- Ícone Sol (aparece em modo escuro) -->
      <svg id="iconSun" class="ui-icon theme-icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="12" cy="12" r="4"></circle>
        <path d="M12 2v2"></path>
        <path d="M12 20v2"></path>
        <path d="m4.93 4.93 1.41 1.41"></path>
        <path d="m17.66 17.66 1.41 1.41"></path>
        <path d="M2 12h2"></path>
        <path d="M20 12h2"></path>
        <path d="m6.34 17.66-1.41 1.41"></path>
        <path d="m19.07 4.93-1.41 1.41"></path>
      </svg>
      <!-- Ícone Lua (aparece em modo claro) -->
      <svg id="iconMoon" class="ui-icon theme-icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"></path>
      </svg>
    </button>
  </div>
</header>
