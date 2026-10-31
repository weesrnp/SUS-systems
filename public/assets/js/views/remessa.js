/**
 * View Remessas SIA — Consulta de entregas e processamento no LogCarga DataSUS.
 */

import { getRemessa, buildDownloadUrl, triggerDownload } from '../api.js';
import {
  escapeHtml,
  formatCount,
  formatCompetencia,
  loadingSkeletonHtml,
  ICON_DOWNLOAD
} from '../ui/table.js';
import { showToast } from '../ui/toast.js';

const LOGCARGA_FTP = 'ftp://arpoador.datasus.gov.br/siasus/LogCarga/';

let lastLoadedKey = '';

export function initRemessaView(force = false) {
  const form = document.getElementById('remessaForm');
  const chips = document.querySelectorAll('.quick-ibge-chip');
  const ibgeInput = document.getElementById('remIbgeInput');

  chips.forEach(chip => {
    chip.addEventListener('click', () => {
      chips.forEach(c => c.classList.remove('active'));
      chip.classList.add('active');
      if (ibgeInput) {
        ibgeInput.value = chip.dataset.ibge;
        loadRemessas(true);
      }
    });
  });

  if (form) {
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      loadRemessas(true);
    });
  }

  // Carga inicial
  if (!lastLoadedKey || force) {
    loadRemessas(force);
  }
}

export async function loadRemessas(force = false) {
  const ibge = document.getElementById('remIbgeInput')?.value?.trim();
  const ano = document.getElementById('remAnoSelect')?.value;
  const container = document.getElementById('remessaResultsContainer');
  if (!container || !ibge || !ano) return;

  const key = `${ibge}|${ano}`;
  if (!force && lastLoadedKey === key) return;

  container.innerHTML = loadingSkeletonHtml(`Consultando remessas para IBGE ${ibge} (${ano})...`);

  try {
    const data = await getRemessa(ibge, ano);
    if (data.error) throw new Error(data.error);

    lastLoadedKey = key;
    renderRemessaTable(container, data, ibge, ano);
  } catch (err) {
    renderError(container, 'Não foi possível consultar as remessas no LogCarga.', err, () => loadRemessas(true));
  }
}

function cleanStatus(text) {
  return String(text || '').replace(/^[^\p{L}\p{N}]+/u, '').trim();
}

function statusTone(text) {
  const t = String(text || '').toLowerCase();
  if (/sem erro|sucesso|carregad|processad/.test(t)) return 'ok';
  if (/erro|rejeit|falh|inconsist|bloque/.test(t)) return 'err';
  return 'neutral';
}

function renderRemessaTable(container, data, ibge, ano) {
  const rows = data.rows || [];
  const ftpUrl = data.ftpUrl || LOGCARGA_FTP;

  if (rows.length === 0) {
    container.innerHTML = `
      <div class="table-card">
        <div class="state-box">
          <strong>Nenhuma remessa encontrada</strong>
          <span>Não há registros no LogCarga para o IBGE ${escapeHtml(ibge)} no ano de ${escapeHtml(ano)}.</span>
        </div>
      </div>
    `;
    return;
  }

  const errCount = rows.filter(r => statusTone(r.status) === 'err').length;

  container.innerHTML = `
    <div class="table-card">
      <div class="table-card-header">
        <div class="table-card-heading">
          <h2 class="table-card-title">Remessas Processadas — Município IBGE ${escapeHtml(ibge)} · Ano ${escapeHtml(ano)}</h2>
          <span class="table-card-ftp">${escapeHtml(ftpUrl)}</span>
        </div>
      </div>

      <div class="summary-line">
        <span><strong>${formatCount(rows.length)}</strong> remessas localizadas</span>
        <span><strong class="${errCount > 0 ? 'delta-neg' : 'delta-pos'}">${formatCount(errCount)}</strong> com erro ou rejeição</span>
      </div>

      <div class="table-scroll">
        <table class="data-table">
          <thead>
            <tr>
              <th style="width: 130px;">Competência</th>
              <th style="width: 100px;">Remessa</th>
              <th style="width: 155px;">Recebida em</th>
              <th style="width: 155px;">Verificada em</th>
              <th style="width: 155px;">Carga em</th>
              <th>Situação do Processamento</th>
            </tr>
          </thead>
          <tbody>
            ${rows.map(r => {
              const tone = statusTone(r.status);
              return `
                <tr class="${tone === 'err' ? 'row-err' : ''}">
                  <td class="mono-cell cell-nowrap" style="font-weight:700;">
                    ${escapeHtml(formatCompetencia(r.competencia))}
                  </td>
                  <td class="mono-cell">${escapeHtml(r.remessa || '–')}</td>
                  <td class="mono-cell cell-nowrap">${escapeHtml(r.recebidoEm || '–')}</td>
                  <td class="mono-cell cell-nowrap">${escapeHtml(r.verificadoEm || '–')}</td>
                  <td class="mono-cell cell-nowrap">${escapeHtml(r.cargaEm || '–')}</td>
                  <td>
                    <div class="status-cell">
                      <span class="status-pill status-${tone}">
                        ${escapeHtml(cleanStatus(r.status))}
                      </span>
                      ${r.statusFile ? `
                        <button type="button" class="link-inline btn-dl-log"
                                data-dl-base="${escapeHtml(ftpUrl)}"
                                data-dl-file="${escapeHtml(r.statusFile)}">
                          ${ICON_DOWNLOAD}<span>Baixar Log</span>
                        </button>
                      ` : ''}
                    </div>
                  </td>
                </tr>
              `;
            }).join('')}
          </tbody>
        </table>
      </div>
    </div>
  `;

  // Downloads de logs de status
  container.querySelectorAll('.btn-dl-log').forEach(btn => {
    btn.addEventListener('click', () => {
      const base = btn.dataset.dlBase;
      const file = btn.dataset.dlFile;
      if (base && file) {
        showToast(`Baixando log ${file}...`, 'info');
        triggerDownload(base, file);
      }
    });
  });
}

function renderError(container, title, err, retry) {
  container.innerHTML = `
    <div class="table-card">
      <div class="state-box is-error" role="alert">
        <strong>${escapeHtml(title)}</strong>
        <span>${escapeHtml(err.message || 'Erro ao consultar remessas.')}</span>
        <button type="button" class="btn-secondary" style="margin-top:0.75rem;">Tentar novamente</button>
      </div>
    </div>
  `;
  container.querySelector('button').addEventListener('click', retry);
}
