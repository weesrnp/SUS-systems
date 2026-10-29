/**
 * SUS-systems 3.0 — Aplicação Principal (Router & Estado Global).
 * Módulo ES nativo sem ferramentas de build.
 */

import { getCatalog } from './api.js';
import { closeAllPopovers } from './ui/table.js';
import { showToast } from './ui/toast.js';
import { initCommandPalette } from './ui/command-palette.js';
import { renderFilesView } from './views/files.js';
import { initSigtapView, bindSigtapEvents } from './views/sigtap.js';
import { initRemessaView, loadRemessas } from './views/remessa.js';

const DEFAULT_GROUP = 'SIA';
const DEFAULT_SUB = 'BPA';

const state = {
  catalogAreas: [],
  ufs: [],
  activeUf: 'PR',
  activeWorkspace: 'catalog',
  selectedGroup: DEFAULT_GROUP,
  selectedSub: DEFAULT_SUB
};

/**
 * Anuncia mensagem para tecnologias assistivas via live region.
 * @param {string} msg
 */
export function announce(msg) {
  const el = document.getElementById('liveStatus');
  if (!el) return;
  el.textContent = '';
  requestAnimationFrame(() => {
    el.textContent = msg;
  });
}

/**
 * Ponto de entrada do frontend.
 */
async function initApp() {
  // 1. Carrega o catálogo embutido no HTML ou busca da API
  loadEmbeddedCatalog();

  // 2. Configura ouvintes globais (fechamento de popover, tema, responsividade)
  setupGlobalListeners();

  // 3. Configura a busca global (Ctrl+K)
  initCommandPalette(state.catalogAreas, handleCommandAction);

  // 4. Configura eventos dos workspaces SIGTAP e Remessa
  bindSigtapEvents();
  initRemessaView(false);

  // 5. Configura a barra lateral e filtros
  setupSidebar();

  // 6. Lê a rota inicial por hash e renderiza
  handleRoute();
}

/**
 * Lê o JSON do catálogo injetado pelo PageController no HTML.
 */
function loadEmbeddedCatalog() {
  const scriptTag = document.getElementById('initial-catalog');
  if (scriptTag && scriptTag.textContent) {
    try {
      const data = JSON.parse(scriptTag.textContent.trim());
      if (data && Array.isArray(data.areas)) {
        state.catalogAreas = data.areas;
        state.ufs = data.ufs || [];
        return;
      }
    } catch (e) {
      console.warn('Erro ao decodificar catálogo embutido:', e);
    }
  }

  // Fallback caso o script tag não esteja disponível
  getCatalog()
    .then(data => {
      state.catalogAreas = data.areas || [];
      state.ufs = data.ufs || [];
    })
    .catch(err => {
      console.error('Falha ao carregar catálogo da API:', err);
    });
}

/**
 * Trata ações disparadas pela Command Palette (Ctrl+K).
 */
function handleCommandAction(action) {
  if (!action) return;

  if (action.type === 'nav') {
    if (action.special === 'sigtap') {
      navigateToSpecial('sigtap');
    } else if (action.special === 'remessa') {
      navigateToSpecial('remessa');
    } else if (action.group && action.sub) {
      navigateToSubgroup(action.group, action.sub);
    }
  } else if (action.type === 'theme') {
    toggleTheme();
  } else if (action.type === 'refresh') {
    refreshActiveView();
  }
}

/**
 * Navegação por Hash Router.
 * Suporta formatos:
 * - #/SIA/BPA ou #SIA/BPA
 * - #/sigtap ou #sigtap
 * - #/remessa ou #remessa
 */
function parseHash() {
  const hash = window.location.hash.replace(/^#\/?/, '').trim();
  if (!hash) return null;

  if (hash === 'sigtap' || hash === 'workspace-sigtap') {
    return { type: 'special', special: 'sigtap' };
  }
  if (hash === 'remessa' || hash === 'workspace-remessa') {
    return { type: 'special', special: 'remessa' };
  }

  const parts = hash.split('/');
  if (parts.length >= 2) {
    return {
      type: 'subgroup',
      group: decodeURIComponent(parts[0]),
      sub: decodeURIComponent(parts.slice(1).join('/'))
    };
  }

  return null;
}

function updateHash(targetHash) {
  if (window.location.hash !== targetHash) {
    history.replaceState(null, '', targetHash);
  }
}

function handleRoute() {
  const route = parseHash();

  if (route && route.type === 'special') {
    switchWorkspace(route.special);
    updateHash(`#/${route.special}`);
  } else if (route && route.type === 'subgroup') {
    switchWorkspace('catalog');
    state.selectedGroup = route.group;
    state.selectedSub = route.sub;
    updateHash(`#/${route.group}/${encodeURIComponent(route.sub)}`);
    renderFilesView(route.group, route.sub, state.activeUf, false, navigateToSpecial);
  } else {
    // Rota padrão: SIA / BPA
    switchWorkspace('catalog');
    state.selectedGroup = DEFAULT_GROUP;
    state.selectedSub = DEFAULT_SUB;
    updateHash(`#/${DEFAULT_GROUP}/${encodeURIComponent(DEFAULT_SUB)}`);
    renderFilesView(DEFAULT_GROUP, DEFAULT_SUB, state.activeUf, false, navigateToSpecial);
  }

  updateSidebarActiveState();
}

/**
 * Alterna a visualização entre os workspaces principais.
 * @param {'catalog'|'sigtap'|'remessa'} wsName
 */
function switchWorkspace(wsName) {
  state.activeWorkspace = wsName;
  closeAllPopovers();

  // Atualiza painéis ativos
  document.querySelectorAll('.ws-panel').forEach(panel => {
    const isActive = panel.id === `ws-${wsName}`;
    panel.classList.toggle('active', isActive);
  });

  // Atualiza abas do topbar
  document.querySelectorAll('.ws-tab').forEach(tab => {
    const isTabActive = tab.dataset.ws === wsName;
    tab.classList.toggle('active', isTabActive);
    tab.setAttribute('aria-selected', isTabActive ? 'true' : 'false');
  });

  // Inicializa a view do workspace quando selecionado
  if (wsName === 'sigtap') {
    initSigtapView(false);
    announce('SIGTAP Explorer aberto.');
  } else if (wsName === 'remessa') {
    loadRemessas(false);
    announce('Acompanhamento de Remessas SIA aberto.');
  } else {
    announce(`Catálogo aberto: ${state.selectedSub}.`);
  }
}

export function navigateToSubgroup(groupId, subName) {
  state.selectedGroup = groupId;
  state.selectedSub = subName;
  switchWorkspace('catalog');
  updateHash(`#/${groupId}/${encodeURIComponent(subName)}`);
  updateSidebarActiveState();
  closeMobileSidebar();
  renderFilesView(groupId, subName, state.activeUf, false, navigateToSpecial);
}

export function navigateToSpecial(special) {
  switchWorkspace(special);
  updateHash(`#/${special}`);
  updateSidebarActiveState();
  closeMobileSidebar();
}

/**
 * Atualiza o link ativo na árvore da barra lateral.
 */
function updateSidebarActiveState() {
  document.querySelectorAll('.subgroup-link').forEach(link => {
    const isSpecial = link.dataset.navSpecial;
    let isActive = false;

    if (state.activeWorkspace === 'sigtap') {
      isActive = isSpecial === 'sigtap';
    } else if (state.activeWorkspace === 'remessa') {
      isActive = isSpecial === 'remessa';
    } else {
      isActive = link.dataset.navGroup === state.selectedGroup &&
                 link.dataset.navSub === state.selectedSub;
    }

    link.classList.toggle('active', isActive);
  });
}

/**
 * Recarrega a tela ativa ignorando o cache do servidor.
 */
function refreshActiveView() {
  const icon = document.getElementById('iconRefresh');
  if (icon) {
    icon.style.transform = 'rotate(360deg)';
    icon.style.transition = 'transform 500ms ease';
    setTimeout(() => {
      icon.style.transform = 'none';
      icon.style.transition = 'none';
    }, 550);
  }

  showToast('Atualizando dados do FTP...', 'info');

  if (state.activeWorkspace === 'catalog') {
    renderFilesView(state.selectedGroup, state.selectedSub, state.activeUf, true, navigateToSpecial);
  } else if (state.activeWorkspace === 'sigtap') {
    initSigtapView(true);
  } else if (state.activeWorkspace === 'remessa') {
    loadRemessas(true);
  }
}

/**
 * Configuração de alternância do tema (claro padrão, escuro opcional).
 */
function toggleTheme() {
  const html = document.documentElement;
  const current = html.getAttribute('data-theme') || 'light';
  const next = current === 'dark' ? 'light' : 'dark';

  html.setAttribute('data-theme', next);
  try {
    localStorage.setItem('sus-systems-theme', next);
  } catch (_) {}

  const metaTheme = document.getElementById('metaThemeColor');
  if (metaTheme) {
    metaTheme.setAttribute('content', next === 'dark' ? '#0B0F17' : '#F6F7F9');
  }

  const btn = document.getElementById('btnThemeToggle');
  if (btn) {
    const label = next === 'dark' ? 'Ativar modo claro' : 'Ativar modo escuro';
    btn.title = label;
    btn.setAttribute('aria-label', label);
  }

  showToast(`Modo ${next === 'dark' ? 'escuro' : 'claro'} ativado.`, 'info', 1800);
}

function loadSavedTheme() {
  try {
    const saved = localStorage.getItem('sus-systems-theme');
    if (saved === 'dark') {
      document.documentElement.setAttribute('data-theme', 'dark');
      const metaTheme = document.getElementById('metaThemeColor');
      if (metaTheme) metaTheme.setAttribute('content', '#0B0F17');
    }
  } catch (_) {}
}

/**
 * Configurações da barra lateral e eventos mobile/desktop.
 */
function setupSidebar() {
  const layout = document.getElementById('appLayout');
  const btnToggleDesktop = document.getElementById('btnToggleSidebarDesktop');
  const btnToggleMobile = document.getElementById('btnToggleSidebarMobile');
  const btnCloseMobile = document.getElementById('btnCloseSidebarMobile');
  const backdrop = document.getElementById('sidebarBackdrop');
  const filterInput = document.getElementById('sidebarFilterInput');

  // Alternador Desktop (Recolhível 280px)
  if (btnToggleDesktop && layout) {
    btnToggleDesktop.addEventListener('click', () => {
      layout.classList.toggle('sidebar-collapsed');
    });
  }

  // Drawer Mobile
  if (btnToggleMobile && layout) {
    btnToggleMobile.addEventListener('click', () => {
      layout.classList.add('sidebar-mobile-open');
      btnToggleMobile.setAttribute('aria-expanded', 'true');
    });
  }

  if (btnCloseMobile && layout) {
    btnCloseMobile.addEventListener('click', closeMobileSidebar);
  }

  if (backdrop && layout) {
    backdrop.addEventListener('click', closeMobileSidebar);
  }

  // Filtro de texto na barra lateral
  if (filterInput) {
    filterInput.addEventListener('input', () => {
      const q = filterInput.value.trim().toLowerCase();
      let totalVisible = 0;

      document.querySelectorAll('.macro-area-block').forEach(block => {
        let blockHasMatch = false;
        block.querySelectorAll('.subgroup-link').forEach(link => {
          const searchTxt = link.dataset.navSearch || '';
          const match = !q || searchTxt.includes(q);
          link.closest('li').style.display = match ? '' : 'none';
          if (match) {
            blockHasMatch = true;
            totalVisible++;
          }
        });

        block.style.display = blockHasMatch ? '' : 'none';
      });

      const emptyMsg = document.getElementById('sidebarEmptySearch');
      if (emptyMsg) {
        emptyMsg.hidden = totalVisible > 0;
      }
    });
  }

  // Delegação de cliques nos links da sidebar
  document.querySelectorAll('.subgroup-link').forEach(link => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      const special = link.dataset.navSpecial;
      if (special) {
        navigateToSpecial(special);
      } else {
        const group = link.dataset.navGroup;
        const sub = link.dataset.navSub;
        if (group && sub) {
          navigateToSubgroup(group, sub);
        }
      }
    });
  });
}

function closeMobileSidebar() {
  const layout = document.getElementById('appLayout');
  const btnToggleMobile = document.getElementById('btnToggleSidebarMobile');
  if (layout) {
    layout.classList.remove('sidebar-mobile-open');
  }
  if (btnToggleMobile) {
    btnToggleMobile.setAttribute('aria-expanded', 'false');
  }
}

/**
 * Ouvintes globais e atalhos de teclado.
 */
function setupGlobalListeners() {
  loadSavedTheme();

  // Fecha popovers ao clicar fora
  document.addEventListener('click', () => closeAllPopovers());

  // Fecha popovers com ESC
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      closeAllPopovers(true);
    }
    // Atalho Ctrl+[ para recolher sidebar no desktop
    if ((e.ctrlKey || e.metaKey) && e.key === '[') {
      e.preventDefault();
      const layout = document.getElementById('appLayout');
      if (layout) layout.classList.toggle('sidebar-collapsed');
    }
  });

  // Fechar popover ao rolar
  window.addEventListener('scroll', (e) => {
    const t = e.target;
    if (t instanceof Element && t.closest('.hist-popover')) return;
    closeAllPopovers();
  }, true);

  // Escuta alteração de rota externa por hash
  window.addEventListener('hashchange', () => {
    handleRoute();
  });

  // Botão Home no Logo
  const homeLogo = document.getElementById('btnHomeLogo');
  if (homeLogo) {
    homeLogo.addEventListener('click', (e) => {
      e.preventDefault();
      navigateToSubgroup(DEFAULT_GROUP, DEFAULT_SUB);
    });
  }

  // Abas de workspace no topo
  document.querySelectorAll('.ws-tab').forEach(tab => {
    tab.addEventListener('click', () => {
      const ws = tab.dataset.ws;
      if (ws === 'sigtap' || ws === 'remessa') {
        navigateToSpecial(ws);
      } else {
        navigateToSubgroup(state.selectedGroup, state.selectedSub);
      }
    });
  });

  // Botões de utilidades no topo
  const btnTheme = document.getElementById('btnThemeToggle');
  if (btnTheme) btnTheme.addEventListener('click', toggleTheme);

  const btnRefresh = document.getElementById('btnForceRefresh');
  if (btnRefresh) btnRefresh.addEventListener('click', refreshActiveView);
}

// Inicializa a aplicação quando o DOM estiver pronto
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initApp);
} else {
  initApp();
}
