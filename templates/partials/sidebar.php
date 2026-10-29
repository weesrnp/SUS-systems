<?php
declare(strict_types=1);

/** @var array{areas: list<array<string, mixed>>, ufs: list<string>} $catalog */
$areas = $catalog['areas'] ?? [];
?>
<!-- Backdrop para fechar o menu mobile ao tocar fora -->
<div class="sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>

<aside class="app-sidebar" id="appSidebar" aria-label="Navegação dos Sistemas do SUS">
  <div class="sidebar-header">
    <div class="sidebar-heading">
      <span class="sidebar-title">Sistemas e Tabelas</span>
      <span class="sidebar-count"><?= e((string) count($areas)) ?> Macro-Áreas</span>
    </div>
    <button type="button" class="icon-btn sidebar-close-btn" id="btnCloseSidebarMobile" aria-label="Fechar menu lateral">
      <svg class="ui-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <line x1="18" y1="6" x2="6" y2="18"></line>
        <line x1="6" y1="6" x2="18" y2="18"></line>
      </svg>
    </button>
  </div>

  <!-- Campo de filtro rápido da barra lateral -->
  <div class="sidebar-filter-wrap">
    <div class="sidebar-search-box">
      <svg class="search-mini-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="11" cy="11" r="8"></circle>
        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
      </svg>
      <input type="search" id="sidebarFilterInput" class="sidebar-search-input" placeholder="Filtrar sistemas..." aria-label="Filtrar menu de sistemas" autocomplete="off">
    </div>
  </div>

  <!-- Lista de Navegação por Macro-Áreas e Subgrupos -->
  <nav class="sidebar-nav" id="sidebarNav" aria-label="Catálogo SUS">
    <?php foreach ($areas as $areaIdx => $area): ?>
      <?php
        $areaId = (string) ($area['area_id'] ?? "area-{$areaIdx}");
        $areaTitle = (string) ($area['area_title'] ?? 'Área');
        $areaBadge = (string) ($area['area_badge'] ?? '');
        $groups = (array) ($area['groups'] ?? []);
      ?>
      <div class="macro-area-block" data-macro-area="<?= e($areaId) ?>">
        <div class="macro-area-heading">
          <span class="macro-area-title"><?= e($areaTitle) ?></span>
          <?php if ($areaBadge !== ''): ?>
            <span class="macro-area-badge"><?= e($areaBadge) ?></span>
          <?php endif; ?>
        </div>

        <ul class="macro-groups-list">
          <?php foreach ($groups as $grp): ?>
            <?php
              $grpId = (string) ($grp['id'] ?? '');
              $grpName = (string) ($grp['name'] ?? $grpId);
              $subGroups = (array) ($grp['subGroups'] ?? []);
            ?>
            <li class="macro-group-item" data-group-id="<?= e($grpId) ?>">
              <div class="group-label">
                <span class="group-name"><?= e($grpName) ?></span>
                <span class="group-counter"><?= e((string) count($subGroups)) ?></span>
              </div>

              <ul class="subgroups-list">
                <?php foreach ($subGroups as $sub): ?>
                  <?php
                    $subName = (string) ($sub['name'] ?? '');
                    $specialView = (string) ($sub['specialView'] ?? '');
                    $targetHash = $specialView !== '' ? "#/{$specialView}" : "#/{$grpId}/" . urlencode($subName);
                  ?>
                  <li>
                    <a href="<?= e($targetHash) ?>"
                       class="subgroup-link"
                       data-nav-group="<?= e($grpId) ?>"
                       data-nav-sub="<?= e($subName) ?>"
                       data-nav-special="<?= e($specialView) ?>"
                       data-nav-search="<?= e(mb_strtolower("{$areaTitle} {$grpName} {$subName}")) ?>">
                      <span class="sub-link-dot" aria-hidden="true"></span>
                      <span class="sub-link-text"><?= e($subName) ?></span>
                      <?php if ($specialView === 'sigtap'): ?>
                        <span class="sub-link-tag tag-sigtap">Tabela</span>
                      <?php elseif ($specialView === 'remessa'): ?>
                        <span class="sub-link-tag tag-remessa">Log</span>
                      <?php endif; ?>
                    </a>
                  </li>
                <?php endforeach; ?>
              </ul>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endforeach; ?>

    <div class="sidebar-empty-search" id="sidebarEmptySearch" hidden>
      Nenhum sistema encontrado com este filtro.
    </div>
  </nav>

  <!-- Rodapé compacto da sidebar -->
  <div class="sidebar-footer">
    <div class="sidebar-status-pill" title="Status da conexão do gateway FTP">
      <span class="status-pulse-dot" aria-hidden="true"></span>
      <span class="status-text">Gateway DataSUS Ativo</span>
    </div>
  </div>
</aside>
