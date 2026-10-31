<?php
declare(strict_types=1);

/** @var string $content */
/** @var array<string, mixed> $catalog */
/** @var string|null $title */
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($title ?? 'SUS-systems — Central DataSUS, SIA, SIGTAP, CNES & e-SUS APS') ?></title>
  <meta name="description" content="Portal de Sistemas e Faturamento do SUS — Downloads Diretos do DataSUS, SIA, SIGTAP, CNES e e-SUS APS.">
  <meta name="theme-color" content="#F6F7F9" id="metaThemeColor">

  <!-- Ícones e Favicons -->
  <link rel="icon" href="<?= asset('img/favicon.svg') ?>" type="image/svg+xml">
  <link rel="icon" href="<?= asset('img/favicon.ico') ?>" sizes="32x32">
  <link rel="apple-touch-icon" href="<?= asset('img/apple-touch-icon.png') ?>">

  <!-- Fontes Oficiais: Plus Jakarta Sans (UI) + JetBrains Mono (Dados Técnicos / Códigos / FTP) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- Folha de Estilos Principal -->
  <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body>

  <!-- Barra Superior Global (56px) -->
  <?php require SUS_ROOT . '/templates/partials/topbar.php'; ?>

  <!-- Estrutura Principal com Barra Lateral (280px) e Canvas -->
  <div class="app-layout" id="appLayout">
    <!-- Barra Lateral de Navegação dos Sistemas -->
    <?php require SUS_ROOT . '/templates/partials/sidebar.php'; ?>

    <!-- Área Principal de Conteúdo -->
    <div class="app-main-wrap">
      <main class="app-main" id="mainCanvas" tabindex="-1">
        <?= $content ?>
      </main>

      <!-- Rodapé Institucional -->
      <?php require SUS_ROOT . '/templates/partials/footer.php'; ?>
    </div>
  </div>

  <!-- Modal da Busca Global (Ctrl+K) -->
  <div class="command-palette-backdrop" id="commandPaletteBackdrop" hidden aria-hidden="true">
    <div class="command-palette-modal" id="commandPaletteModal" role="dialog" aria-modal="true" aria-labelledby="cmdPaletteTitle">
      <div class="command-palette-input-wrap">
        <svg class="cmd-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <circle cx="11" cy="11" r="8"></circle>
          <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
        <input type="search" id="commandPaletteInput" class="command-palette-input" placeholder="Buscar sistemas, tabelas, subgrupos ou ações rápidas..." aria-label="Digite para pesquisar" autocomplete="off">
        <kbd class="cmd-esc-hint">ESC</kbd>
      </div>

      <div class="command-palette-results" id="commandPaletteResults" role="listbox" aria-label="Resultados da busca"></div>

      <div class="command-palette-footer">
        <div class="cmd-keyboard-tips">
          <span><kbd class="key-hint">↑</kbd><kbd class="key-hint">↓</kbd> navegar</span>
          <span><kbd class="key-hint">ENTER</kbd> selecionar</span>
          <span><kbd class="key-hint">ESC</kbd> fechar</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Contêiner de Notificações Toast -->
  <div class="toast-container" id="toastContainer" aria-live="polite" aria-atomic="true"></div>

  <!-- Anunciador de Acessibilidade para Leitores de Tela -->
  <div id="liveStatus" class="sr-only" role="status" aria-live="polite"></div>

  <!-- Dados Iniciais do Catálogo Embutidos (Primeira Pintura sem Latência de Rede) -->
  <script id="initial-catalog" type="application/json">
<?= json_encode($catalog ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>
  </script>

  <!-- Módulos JavaScript (ES Modules nativos sem build) -->
  <script type="module" src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
