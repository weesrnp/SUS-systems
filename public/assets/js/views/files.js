/**
 * View de Catálogo de Arquivos FTP e Normativas.
 */

import { getSubgroupData, triggerDownload } from '../api.js';
import {
  escapeHtml,
  formatSizeMB,
  formatDateBR,
  formatRelativeTime,
  formatCompetencia,
  loadingSkeletonHtml,
  closeAllPopovers,
  placePopover,
  ICON_DOWNLOAD,
  ICON_CHEVRON
} from '../ui/table.js';
import { showToast } from '../ui/toast.js';

let currentRequestKey = '';

const SOURCE_LABELS = {
  CACHE: 'Fonte: cache local (10 min)',
  FTP_DIRECT: 'Fonte: FTP direto DataSUS',
  HTTPS_BRIDGE: 'Fonte: espelho HTTPS',
  HTTPS_MIRROR: 'Fonte: espelho HTTPS',
  READY: 'Fonte: pronta',
  OFFLINE: 'Sem conexão com o FTP DataSUS'
};

/**
 * Renderiza o conteúdo do subgrupo selecionado na área do catálogo.
 * @param {string} groupId
 * @param {string} subName
 * @param {string} activeUf
 * @param {boolean} [forceRefresh=false]
 * @param {Function} [onNavigateSpecial]
 */
export async function renderFilesView(groupId, subName, activeUf = 'PR', forceRefresh = false, onNavigateSpecial = null) {
  const container = document.getElementById('catalogDynamicContent');
  if (!container) return;

  const reqKey = `${groupId}/${subName}`;
  currentRequestKey = reqKey;

  container.innerHTML = loadingSkeletonHtml(`Carregando arquivos de ${subName}...`);

  try {
    const data = await getSubgroupData(groupId, subName, forceRefresh);
    if (currentRequestKey !== reqKey) return; // descarta resposta desatualizada

    renderSubgroupHtml(container, data, activeUf, onNavigateSpecial);
  } catch (err) {
    if (currentRequestKey !== reqKey) return;
    renderErrorState(container, `Não foi possível carregar ${subName}.`, err, () => {
      renderFilesView(groupId, subName, activeUf, true, onNavigateSpecial);
    });
  }
}

function renderSubgroupHtml(container, data, activeUf, onNavigateSpecial) {
  const sections = data.sections || [];
  const mode = data.sourceSummary || 'READY';

  let html = `
    <div class="content-header">
      <div class="content-heading">
        <div class="breadcrumb-trail">
          <span class="crumb-root">${escapeHtml(data.groupId || 'Catálogo')}</span>
          <span class="crumb-sep">/</span>
          <span class="crumb-current">${escapeHtml(data.subName || '')}</span>
        </div>
        <h1 class="content-title">${escapeHtml(data.title || data.subName)}</h1>
        <div class="ftp-url-bar" title="URL primária do diretório no servidor DataSUS">
          <span class="ftp-protocol">FTP:</span> ${escapeHtml(data.primaryFtpUrl || '')}
        </div>
      </div>
      <div class="content-header-badges">
        <span class="source-badge ${mode === 'OFFLINE' ? 'badge-offline' : 'badge-ftp'}">
          ${escapeHtml(SOURCE_LABELS[mode] || mode)}
        </span>
      </div>
    </div>
  `;

  // Normativas e manuais técnicos
  if (data.normatives && data.normatives.length > 0) {
    html += `
      <div class="normatives-section">
        <div class="normatives-grid">
          ${data.normatives.map(n => {
            const external = n.url && n.url.startsWith('http');
            return `
              <div class="norm-card">
                <div>
                  <div class="norm-card-top">
                    <span class="norm-code">${escapeHtml(n.code)}</span>
                    <span class="norm-tag">${escapeHtml(n.tag || 'Portaria')}</span>
                  </div>
                  <p class="norm-title" style="margin-top:0.35rem;">${escapeHtml(n.title)}</p>
                </div>
                <a class="norm-link" href="${escapeHtml(n.url)}" ${external ? 'target="_blank" rel="noopener noreferrer"' : ''}>
                  ${external ? 'Abrir referência oficial ↗' : 'Abrir no SUS-systems →'}
                </a>
              </div>
            `;
          }).join('')}
        </div>
      </div>
    `;
  }

  // Seções e tabelas de arquivos
  sections.forEach((sec, secIdx) => {
    const tableId = `sec-table-${secIdx}`;
    const showDesc = Boolean(sec.showDescriptionColumn);
    const files = sec.files || [];

    html += `
      <div class="table-card" data-sec-idx="${secIdx}">
        <div class="table-card-header">
          <div class="table-card-heading">
            <h2 class="table-card-title">${escapeHtml(sec.title)}</h2>
            <span class="table-card-ftp">${escapeHtml(sec.ftpUrl)}</span>
          </div>
          ${sec.showNameFilter && files.length > 0 ? `
            <input type="search" class="table-filter-input" data-filter-table="${tableId}"
                   placeholder="Filtrar arquivos por nome ou descrição..." aria-label="Filtrar ${escapeHtml(sec.title)}" autocomplete="off">
          ` : ''}
        </div>

        ${files.length === 0 ? `
          <div class="state-box">Nenhum arquivo retornado neste diretório do FTP.</div>
        ` : `
          <div class="table-scroll">
            <table class="data-table is-catalog ${showDesc ? 'has-desc' : ''}" id="${tableId}">
              <thead>
                <tr>
                  ${showDesc ? `<th class="col-desc">Descrição</th>` : ''}
                  <th>Arquivo</th>
                  <th class="col-avail">Disponibilizado</th>
                  <th class="col-date">Data FTP</th>
                  <th class="col-size col-right">Tamanho</th>
                  <th class="col-action col-right">Ação / UF</th>
                  <th class="col-hist col-right">Anteriores</th>
                </tr>
              </thead>
              <tbody>
                ${files.map((f, fIdx) => renderFileRow(f, sec.ftpUrl, showDesc, activeUf, `${secIdx}-${fIdx}`)).join('')}
              </tbody>
            </table>
          </div>
          <div class="state-box filter-empty" hidden>Nenhum arquivo corresponde ao filtro de busca.</div>
        `}
      </div>
    `;
  });

  container.innerHTML = html;
  bindTableEvents(container, activeUf, onNavigateSpecial);
}

function renderFileRow(f, ftpUrl, showDesc, activeUf, rowKey) {
  const isUf = f.kind === 'uf_sigla' || f.kind === 'uf_cod' || f.kind === 'dsihd017';
  const ufMap = f.ufBySigla || {};
  const ufHistMap = f.ufHistoryBySigla || {};
  const availableUfs = Object.keys(ufMap).sort();

  const defaultUf = isUf && availableUfs.includes(activeUf) ? activeUf : '';
  const activeMeta = isUf && defaultUf ? ufMap[defaultUf] : null;

  const fileNameToUse = isUf ? (activeMeta ? activeMeta.name : f.displayName) : f.displayName;
  const actualDownloadName = isUf ? (activeMeta ? activeMeta.name : '') : f.name;
  const modAt = isUf ? (activeMeta ? activeMeta.modifiedAt : null) : f.modifiedAt;
  const sizeBytes = isUf ? (activeMeta ? activeMeta.size : 0) : f.size;
  const histEntries = isUf ? (defaultUf && ufHistMap[defaultUf] ? ufHistMap[defaultUf] : []) : (f.history || []);
  const needsUf = isUf && !activeMeta;

  const rowPayload = encodeURIComponent(JSON.stringify({ f, ftpUrl }));

  return `
    <tr data-row-key="${rowKey}" data-row-payload="${rowPayload}">
      ${showDesc ? `<td class="col-desc-badge">${escapeHtml(f.description || '–')}</td>` : ''}
      <td class="cell-name">
        ${!needsUf ? `
          <button type="button" class="file-link-btn" data-dl-base="${escapeHtml(ftpUrl)}" data-dl-file="${escapeHtml(actualDownloadName)}">
            ${escapeHtml(fileNameToUse)}
          </button>
        ` : `<span class="mono-cell">${escapeHtml(f.displayName)}</span>`}
      </td>
      <td class="cell-avail" title="${modAt ? escapeHtml(formatDateBR(modAt)) : ''}">
        ${modAt ? formatRelativeTime(modAt) : '–'}
      </td>
      <td class="mono-cell cell-date col-date">${modAt ? formatDateBR(modAt) : '–'}</td>
      <td class="mono-cell cell-size col-right">${sizeBytes ? formatSizeMB(sizeBytes) : '–'}</td>
      <td class="col-right">
        <div class="action-group">
          ${isUf ? `
            <select class="uf-select" aria-label="UF do arquivo ${escapeHtml(f.displayName)}">
              <option value="">UF…</option>
              ${availableUfs.map(u => `<option value="${u}" ${u === defaultUf ? 'selected' : ''}>${u}</option>`).join('')}
            </select>
          ` : ''}
          <button type="button" class="btn-primary btn-row-dl"
                  ${needsUf ? 'disabled title="Escolha a UF para baixar"' : ''}
                  data-dl-base="${escapeHtml(ftpUrl)}"
                  data-dl-file="${escapeHtml(actualDownloadName)}">
            ${ICON_DOWNLOAD}<span>Baixar</span>
          </button>
        </div>
      </td>
      <td class="cell-hist col-right">
        ${renderHistoryPopover(ftpUrl, histEntries)}
      </td>
    </tr>
  `;
}

function renderHistoryPopover(ftpUrl, entries) {
  if (!entries || entries.length === 0) {
    return `<span style="color:var(--text-muted)">–</span>`;
  }

  return `
    <div class="hist-wrap">
      <button type="button" class="btn-hist" aria-expanded="false">
        <span>Anteriores (${entries.length})</span>
        ${ICON_CHEVRON}
      </button>
      <ul class="hist-popover">
        ${entries.map(e => {
          const label = formatCompetencia(e.label || (e.ym ? `${e.ym.slice(0, 4)}/${e.ym.slice(4)}` : e.name));
          return `
            <li>
              <button type="button" class="hist-item-btn" data-dl-base="${escapeHtml(ftpUrl)}" data-dl-file="${escapeHtml(e.name)}" title="${escapeHtml(e.name)}">
                <span>${escapeHtml(label)}</span>
                ${ICON_DOWNLOAD}
              </button>
            </li>
          `;
        }).join('')}
      </ul>
    </div>
  `;
}

function bindTableEvents(container, activeUf, onNavigateSpecial) {
  // Links de normativas internas (#sigtap)
  container.querySelectorAll('.norm-link').forEach(a => {
    const href = a.getAttribute('href') || '';
    if (href.startsWith('#') && onNavigateSpecial) {
      a.addEventListener('click', (e) => {
        e.preventDefault();
        onNavigateSpecial(href.replace(/^#\/?/, ''));
      });
    }
  });

  // Downloads de arquivos principais e botões da linha
  container.querySelectorAll('[data-dl-file]').forEach(btn => {
    btn.addEventListener('click', () => {
      const base = btn.dataset.dlBase;
      const file = btn.dataset.dlFile;
      closeAllPopovers();
      if (base && file) {
        showToast(`Iniciando download de ${file}...`, 'info');
        triggerDownload(base, file);
      }
    });
  });

  // Abertura do Popover de Versões Anteriores
  container.querySelectorAll('.btn-hist').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const wrap = btn.closest('.hist-wrap');
      const wasOpen = wrap.classList.contains('open');
      closeAllPopovers();
      if (wasOpen) return;
      wrap.classList.add('open');
      btn.setAttribute('aria-expanded', 'true');
      placePopover(wrap);
    });
  });

  container.querySelectorAll('.hist-popover').forEach(pop => {
    pop.addEventListener('click', (e) => e.stopPropagation());
  });

  // Seletor de UF na linha
  container.querySelectorAll('.uf-select').forEach(sel => {
    sel.addEventListener('change', () => {
      const tr = sel.closest('tr');
      const payload = JSON.parse(decodeURIComponent(tr.dataset.rowPayload));
      const { f, ftpUrl } = payload;
      const chosenUf = sel.value;
      const meta = chosenUf && f.ufBySigla ? f.ufBySigla[chosenUf] : null;
      const hist = chosenUf && f.ufHistoryBySigla && f.ufHistoryBySigla[chosenUf] ? f.ufHistoryBySigla[chosenUf] : [];

      const nameCell = tr.querySelector('.cell-name');
      if (meta) {
        nameCell.innerHTML = `<button type="button" class="file-link-btn" data-dl-base="${escapeHtml(ftpUrl)}" data-dl-file="${escapeHtml(meta.name)}">${escapeHtml(meta.name)}</button>`;
        nameCell.querySelector('[data-dl-file]').addEventListener('click', () => {
          showToast(`Iniciando download de ${meta.name}...`, 'info');
          triggerDownload(ftpUrl, meta.name);
        });
      } else {
        nameCell.innerHTML = `<span class="mono-cell">${escapeHtml(f.displayName)}</span>`;
      }

      const availCell = tr.querySelector('.cell-avail');
      availCell.innerHTML = meta ? formatRelativeTime(meta.modifiedAt) : '–';
      availCell.title = meta ? formatDateBR(meta.modifiedAt) : '';
      tr.querySelector('.cell-date').textContent = meta ? formatDateBR(meta.modifiedAt) : '–';
      tr.querySelector('.cell-size').textContent = meta ? formatSizeMB(meta.size) : '–';

      const dlBtn = tr.querySelector('.btn-row-dl');
      dlBtn.disabled = !meta;
      dlBtn.title = meta ? '' : 'Escolha a UF para baixar';
      dlBtn.dataset.dlFile = meta ? meta.name : '';

      const histCell = tr.querySelector('.cell-hist');
      histCell.innerHTML = renderHistoryPopover(ftpUrl, hist);
      bindTableEvents(histCell, chosenUf, onNavigateSpecial);
    });
  });

  // Filtro de arquivos por nome
  container.querySelectorAll('[data-filter-table]').forEach(input => {
    input.addEventListener('input', () => {
      const tbl = document.getElementById(input.dataset.filterTable);
      if (!tbl) return;
      const q = input.value.trim().toLowerCase();
      let visible = 0;
      tbl.querySelectorAll('tbody tr').forEach(tr => {
        const match = tr.textContent.toLowerCase().includes(q);
        tr.style.display = match ? '' : 'none';
        if (match) visible++;
      });
      const empty = tbl.closest('.table-card').querySelector('.filter-empty');
      if (empty) empty.hidden = visible > 0;
    });
  });
}

function renderErrorState(container, title, err, retry) {
  container.innerHTML = `
    <div class="table-card">
      <div class="state-box is-error" role="alert">
        <strong>${escapeHtml(title)}</strong>
        <span>${escapeHtml(err.message || 'Erro de comunicação com o servidor.')}</span>
        <button type="button" class="btn-secondary" style="margin-top:0.75rem;">Tentar novamente</button>
      </div>
    </div>
  `;
  container.querySelector('button').addEventListener('click', retry);
}
