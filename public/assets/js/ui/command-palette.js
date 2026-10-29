/**
 * Command Palette Global (Ctrl+K).
 * Busca rápida por sistemas, subgrupos do catálogo e atalhos de ações.
 */

import { escapeHtml } from './table.js';

let commandList = [];
let selectedIndex = 0;
let onSelectCallback = null;

export function initCommandPalette(catalogAreas, onSelect) {
  onSelectCallback = onSelect;
  buildCommandList(catalogAreas);

  const backdrop = document.getElementById('commandPaletteBackdrop');
  const input = document.getElementById('commandPaletteInput');
  const openBtn = document.getElementById('btnOpenCommandPalette');

  if (openBtn) {
    openBtn.addEventListener('click', openPalette);
  }

  // Atalho global Ctrl+K / Cmd+K
  window.addEventListener('keydown', (e) => {
    if ((e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === 'K')) {
      e.preventDefault();
      if (backdrop.hasAttribute('hidden')) {
        openPalette();
      } else {
        closePalette();
      }
    } else if (e.key === 'Escape' && !backdrop.hasAttribute('hidden')) {
      closePalette();
    }
  });

  // Fechar ao clicar no backdrop
  if (backdrop) {
    backdrop.addEventListener('click', (e) => {
      if (e.target === backdrop) {
        closePalette();
      }
    });
  }

  if (input) {
    input.addEventListener('input', () => {
      renderResults(input.value.trim());
    });

    input.addEventListener('keydown', (e) => {
      const items = document.querySelectorAll('.cmd-item');
      if (!items.length) return;

      if (e.key === 'ArrowDown') {
        e.preventDefault();
        selectedIndex = (selectedIndex + 1) % items.length;
        updateSelected(items);
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        selectedIndex = (selectedIndex - 1 + items.length) % items.length;
        updateSelected(items);
      } else if (e.key === 'Enter') {
        e.preventDefault();
        if (items[selectedIndex]) {
          items[selectedIndex].click();
        }
      }
    });
  }
}

function buildCommandList(areas) {
  commandList = [
    {
      title: 'Catálogo Geral (Início)',
      subtitle: 'Voltar para a visão inicial do catálogo FTP',
      tag: 'Navegação',
      action: { type: 'nav', group: 'SIA', sub: 'BPA' }
    },
    {
      title: 'SIGTAP — Consulta e Comparativo',
      subtitle: 'Explorar procedimentos, valores e diff entre competências',
      tag: 'Ferramenta',
      action: { type: 'nav', special: 'sigtap' }
    },
    {
      title: 'Remessas SIA (LogCarga)',
      subtitle: 'Acompanhar entrega de arquivos magnéticos do município',
      tag: 'Ferramenta',
      action: { type: 'nav', special: 'remessa' }
    },
    {
      title: 'Alternar Tema Claro / Escuro',
      subtitle: 'Mudar aparência da interface',
      tag: 'Ação',
      action: { type: 'theme' }
    },
    {
      title: 'Recarregar Dados do FTP',
      subtitle: 'Limpar cache e buscar arquivos mais recentes',
      tag: 'Ação',
      action: { type: 'refresh' }
    }
  ];

  if (Array.isArray(areas)) {
    for (const area of areas) {
      for (const grp of (area.groups || [])) {
        for (const sub of (grp.subGroups || [])) {
          commandList.push({
            title: `${grp.name} · ${sub.name}`,
            subtitle: `${area.area_title} — ${sub.title || sub.name}`,
            tag: grp.name,
            action: sub.specialView
              ? { type: 'nav', special: sub.specialView }
              : { type: 'nav', group: grp.id, sub: sub.name }
          });
        }
      }
    }
  }
}

export function openPalette() {
  const backdrop = document.getElementById('commandPaletteBackdrop');
  const input = document.getElementById('commandPaletteInput');
  if (!backdrop || !input) return;

  backdrop.removeAttribute('hidden');
  input.value = '';
  selectedIndex = 0;
  renderResults('');
  setTimeout(() => input.focus(), 50);
}

export function closePalette() {
  const backdrop = document.getElementById('commandPaletteBackdrop');
  if (backdrop) {
    backdrop.setAttribute('hidden', '');
  }
}

function renderResults(query) {
  const container = document.getElementById('commandPaletteResults');
  if (!container) return;

  const q = query.toLowerCase();
  const filtered = commandList.filter(item => {
    if (!q) return true;
    return item.title.toLowerCase().includes(q) ||
           item.subtitle.toLowerCase().includes(q) ||
           item.tag.toLowerCase().includes(q);
  }).slice(0, 15);

  selectedIndex = 0;

  if (filtered.length === 0) {
    container.innerHTML = `<div class="state-box">Nenhum resultado encontrado para "${escapeHtml(query)}".</div>`;
    return;
  }

  container.innerHTML = filtered.map((item, idx) => `
    <div class="cmd-item ${idx === 0 ? 'selected' : ''}" data-idx="${idx}">
      <div class="cmd-item-left">
        <span class="cmd-item-title">${escapeHtml(item.title)}</span>
        <span class="cmd-item-sub" style="font-size:0.75rem; color:var(--text-muted);">${escapeHtml(item.subtitle)}</span>
      </div>
      <span class="cmd-item-tag">${escapeHtml(item.tag)}</span>
    </div>
  `).join('');

  container.querySelectorAll('.cmd-item').forEach((el, idx) => {
    el.addEventListener('click', () => {
      const item = filtered[idx];
      closePalette();
      if (item && onSelectCallback) {
        onSelectCallback(item.action);
      }
    });
  });
}

function updateSelected(items) {
  items.forEach((item, idx) => {
    const isSel = idx === selectedIndex;
    item.classList.toggle('selected', isSel);
    if (isSel) {
      item.scrollIntoView({ block: 'nearest' });
    }
  });
}
