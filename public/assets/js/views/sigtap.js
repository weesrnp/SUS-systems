/**
 * View SIGTAP — Consulta de Procedimentos e Comparativo de Competências (Diff).
 */

import { getSigtapCompetencias, searchSigtap, diffSigtap, buildDownloadUrl, triggerDownload } from '../api.js';
import {
  escapeHtml,
  formatBRL,
  formatSignedBRL,
  formatCount,
  formatCompetencia,
  formatAgeRange,
  loadingSkeletonHtml,
  ICON_DOWNLOAD
} from '../ui/table.js';
import { showToast } from '../ui/toast.js';

const SIGTAP_FTP = 'ftp://ftp2.datasus.gov.br/pub/sistemas/tup/downloads/';

let competencias = [];
let initialLoaded = false;

export async function initSigtapView(force = false) {
  const selNew = document.getElementById('sigtapCompNew');
  const selOld = document.getElementById('sigtapCompOld');
  const resultsContainer = document.getElementById('sigtapResultsContainer');
  if (!selNew || !selOld || !resultsContainer) return;

  if (competencias.length === 0 || force) {
    resultsContainer.innerHTML = loadingSkeletonHtml('Carregando competências da Tabela Unificada...');
    try {
      const data = await getSigtapCompetencias(force);
      competencias = data.competencias || [];

      if (competencias.length === 0) {
        resultsContainer.innerHTML = `<div class="table-card"><div class="state-box">Nenhuma competência SIGTAP encontrada no FTP.</div></div>`;
        return;
      }

      const opts = competencias.map(c => `
        <option value="${escapeHtml(c.competencia)}" title="${escapeHtml(c.file || '')}">
          ${escapeHtml(formatCompetencia(c.competencia))}
        </option>
      `).join('');

      selNew.innerHTML = opts;
      selOld.innerHTML = opts;
      if (competencias.length > 1) {
        selOld.selectedIndex = 1; // Seleciona o mês anterior por padrão para diff
      }
    } catch (err) {
      renderError(resultsContainer, 'Não foi possível carregar as competências do SIGTAP.', err, () => initSigtapView(true));
      return;
    }
  }

  // Executa busca inicial uma única vez
  if (!initialLoaded || force) {
    initialLoaded = true;
    runSearch();
  }
}

export function bindSigtapEvents() {
  const btnSearch = document.getElementById('btnSigtapSearch');
  const btnDiff = document.getElementById('btnSigtapDiff');
  const queryInput = document.getElementById('sigtapQuery');

  if (btnSearch) {
    btnSearch.addEventListener('click', () => runSearch());
  }

  if (queryInput) {
    queryInput.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        runSearch();
      }
    });
  }

  if (btnDiff) {
    btnDiff.addEventListener('click', () => runDiff());
  }
}

async function runSearch() {
  const comp = document.getElementById('sigtapCompNew')?.value;
  const q = document.getElementById('sigtapQuery')?.value || '';
  const grupo = document.getElementById('sigtapGrupo')?.value || '';
  const fin = document.getElementById('sigtapFin')?.value || '';
  const container = document.getElementById('sigtapResultsContainer');
  if (!container || !comp) return;

  container.innerHTML = loadingSkeletonHtml('Consultando procedimentos da Tabela Unificada...');

  try {
    const data = await searchSigtap({ comp, q, grupo, financiamento: fin, limit: 120 });
    renderSearchResults(container, data);
  } catch (err) {
    renderError(container, 'Não foi possível consultar os procedimentos do SIGTAP.', err, runSearch);
  }
}

function renderSearchResults(container, data) {
  const items = data.items || [];
  const limit = 120;
  const zipUrl = buildDownloadUrl(SIGTAP_FTP, data.file || '');

  container.innerHTML = `
    <div class="table-card">
      <div class="table-card-header">
        <div class="table-card-heading">
          <h2 class="table-card-title">Procedimentos — Competência ${escapeHtml(formatCompetencia(data.competencia))}</h2>
          <span class="table-card-ftp">${escapeHtml(data.file || 'TabelaUnificada')}</span>
        </div>
        ${data.file ? `
          <button type="button" class="btn-primary" id="btnDownloadSigtapZip" data-dl-file="${escapeHtml(data.file)}">
            ${ICON_DOWNLOAD}<span>Baixar Pacote ZIP da Competência</span>
          </button>
        ` : ''}
      </div>

      <div class="summary-line">
        <span><strong>${formatCount(data.totalInTable)}</strong> procedimentos cadastrados na competência</span>
        <span><strong class="delta-info">${formatCount(data.returnedCount)}</strong> exibidos${data.returnedCount >= limit ? ` (limite de ${limit}; refine a busca se necessário)` : ''}</span>
      </div>

      ${items.length === 0 ? `
        <div class="state-box">Nenhum procedimento encontrado com os filtros selecionados.</div>
      ` : `
        <div class="table-scroll">
          <table class="data-table">
            <thead>
              <tr>
                <th style="width: 140px;">Código</th>
                <th>Procedimento</th>
                <th style="width: 170px;">Complexidade</th>
                <th style="width: 190px;">Financiamento</th>
                <th style="width: 130px;">Faixa Etária</th>
                <th style="width: 110px;" class="col-right">Valor SA</th>
                <th style="width: 125px;" class="col-right">Valor Hosp.</th>
              </tr>
            </thead>
            <tbody>
              ${items.map(p => `
                <tr>
                  <td class="code-cell">${escapeHtml(p.formattedCode || p.code)}</td>
                  <td class="col-desc-badge">${escapeHtml(p.name)}</td>
                  <td>${escapeHtml(p.complexidade || '–')}</td>
                  <td>${escapeHtml(p.financiamento || '–')}</td>
                  <td class="mono-cell">${escapeHtml(formatAgeRange(p.idadeMin, p.idadeMax))}</td>
                  <td class="mono-cell col-right cell-nowrap">${formatBRL(p.vlSA)}</td>
                  <td class="mono-cell col-right cell-nowrap">${formatBRL(p.vlTotalHosp)}</td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        </div>
      `}
    </div>
  `;

  const btnDl = document.getElementById('btnDownloadSigtapZip');
  if (btnDl && data.file) {
    btnDl.addEventListener('click', () => {
      showToast(`Baixando pacote ${data.file}...`, 'info');
      triggerDownload(SIGTAP_FTP, data.file);
    });
  }
}

async function runDiff() {
  const compNew = document.getElementById('sigtapCompNew')?.value;
  const compOld = document.getElementById('sigtapCompOld')?.value;
  const container = document.getElementById('sigtapResultsContainer');
  if (!container || !compNew || !compOld) return;

  if (compNew === compOld) {
    showToast('Escolha duas competências diferentes para calcular o comparativo.', 'error');
    return;
  }

  container.innerHTML = loadingSkeletonHtml('Calculando alterações e reajustes entre as competências...');

  try {
    const d = await diffSigtap(compNew, compOld);
    renderDiffResults(container, d);
  } catch (err) {
    renderError(container, 'Não foi possível comparar as competências do SIGTAP.', err, runDiff);
  }
}

function renderDiffResults(container, d) {
  const s = d.summary || {};
  const oldLabel = formatCompetencia(d.compOld);
  const newLabel = formatCompetencia(d.compNew);

  container.innerHTML = `
    <div class="table-card">
      <div class="table-card-header">
        <div class="table-card-heading">
          <h2 class="table-card-title">Comparativo Oficial: ${escapeHtml(oldLabel)} → ${escapeHtml(newLabel)}</h2>
          <span class="table-card-ftp">${escapeHtml(d.fileOld || '')} → ${escapeHtml(d.fileNew || '')}</span>
        </div>
      </div>
      <div class="summary-line">
        <span><strong class="delta-pos">${formatCount(s.addedCount)}</strong> incluídos</span>
        <span><strong class="delta-info">${formatCount(s.valueChangedCount)}</strong> com reajuste de valor</span>
        <span><strong class="delta-warn">${formatCount(s.ruleChangedCount)}</strong> com mudança de regra</span>
        <span><strong class="delta-neg">${formatCount(s.removedCount)}</strong> excluídos</span>
      </div>
    </div>

    <!-- Reajustes de Valor -->
    ${diffBlockHtml({
      title: 'Reajustes de Valor (Tabela SA / SH / SP)',
      total: s.valueChangedCount,
      shown: (d.valueChanged || []).length,
      emptyText: 'Nenhum procedimento mudou de valor entre essas competências.',
      headers: `<th>Código</th><th>Procedimento</th><th class="col-right">SA Anterior</th><th class="col-right">SA Novo</th><th class="col-right">Δ SA</th><th class="col-right">Δ Hospitalar</th>`,
      rows: (d.valueChanged || []).map(v => {
        const dHosp = (v.deltaSH || 0) + (v.deltaSP || 0);
        return `
          <tr>
            <td class="code-cell">${escapeHtml(v.formattedCode || v.code)}</td>
            <td class="col-desc-badge">${escapeHtml(v.name)}</td>
            <td class="mono-cell col-right cell-nowrap">${formatBRL(v.oldSA)}</td>
            <td class="mono-cell col-right cell-nowrap">${formatBRL(v.newSA)}</td>
            <td class="mono-cell col-right cell-nowrap ${(v.deltaSA || 0) >= 0 ? 'delta-pos' : 'delta-neg'}">${formatSignedBRL(v.deltaSA)}</td>
            <td class="mono-cell col-right cell-nowrap ${dHosp >= 0 ? 'delta-pos' : 'delta-neg'}">${formatSignedBRL(dHosp)}</td>
          </tr>
        `;
      }).join('')
    })}

    <!-- Procedimentos Incluídos -->
    ${diffBlockHtml({
      title: `Procedimentos Novos Incluídos em ${newLabel}`,
      total: s.addedCount,
      shown: (d.added || []).length,
      emptyText: 'Nenhum novo código foi introduzido nesta competência.',
      headers: `<th>Código</th><th>Procedimento</th><th>Complexidade</th><th>Financiamento</th><th class="col-right">Valor SA</th>`,
      rows: (d.added || []).map(a => `
        <tr>
          <td class="code-cell">${escapeHtml(a.formattedCode || a.code)}</td>
          <td class="col-desc-badge">${escapeHtml(a.name)}</td>
          <td>${escapeHtml(a.complexidade || '–')}</td>
          <td>${escapeHtml(a.financiamento || '–')}</td>
          <td class="mono-cell col-right cell-nowrap">${formatBRL(a.vlSA)}</td>
        </tr>
      `).join('')
    })}

    <!-- Mudanças de Regra -->
    ${diffBlockHtml({
      title: 'Alterações de Regra, Nome ou Faixa Etária',
      total: s.ruleChangedCount,
      shown: (d.ruleChanged || []).length,
      emptyText: 'Nenhuma alteração de regra cadastral entre essas competências.',
      headers: `<th>Código</th><th>Procedimento</th><th>Alterações Detectadas</th>`,
      rows: (d.ruleChanged || []).map(r => `
        <tr>
          <td class="code-cell">${escapeHtml(r.formattedCode || r.code)}</td>
          <td class="col-desc-badge">${escapeHtml(r.name)}</td>
          <td>${escapeHtml(r.changes || '–')}</td>
        </tr>
      `).join('')
    })}

    <!-- Procedimentos Excluídos -->
    ${diffBlockHtml({
      title: `Procedimentos Excluídos em ${newLabel}`,
      total: s.removedCount,
      shown: (d.removed || []).length,
      emptyText: 'Nenhum procedimento foi excluído nesta competência.',
      headers: `<th>Código</th><th>Procedimento</th><th>Complexidade</th><th>Financiamento</th><th class="col-right">Valor SA</th>`,
      rows: (d.removed || []).map(r => `
        <tr>
          <td class="code-cell">${escapeHtml(r.formattedCode || r.code)}</td>
          <td class="col-desc-badge">${escapeHtml(r.name)}</td>
          <td>${escapeHtml(r.complexidade || '–')}</td>
          <td>${escapeHtml(r.financiamento || '–')}</td>
          <td class="mono-cell col-right cell-nowrap">${formatBRL(r.vlSA)}</td>
        </tr>
      `).join('')
    })}
  `;
}

function diffBlockHtml({ title, total, shown, emptyText, headers, rows }) {
  return `
    <div class="table-card">
      <div class="table-card-header">
        <h2 class="table-card-title">${escapeHtml(title)} (${formatCount(total)})</h2>
      </div>
      ${shown === 0 ? `
        <div class="state-box">${escapeHtml(emptyText)}</div>
      ` : `
        <div class="table-scroll">
          <table class="data-table">
            <thead><tr>${headers}</tr></thead>
            <tbody>${rows}</tbody>
          </table>
        </div>
        ${total > shown ? `
          <div class="summary-line" style="font-size:0.75rem; color:var(--text-muted);">
            Mostrando os primeiros ${formatCount(shown)} de ${formatCount(total)} registros. Baixe o ZIP da competência para visualização integral.
          </div>
        ` : ''}
      `}
    </div>
  `;
}

function renderError(container, title, err, retry) {
  container.innerHTML = `
    <div class="table-card">
      <div class="state-box is-error" role="alert">
        <strong>${escapeHtml(title)}</strong>
        <span>${escapeHtml(err.message || 'Erro ao processar dados do SIGTAP.')}</span>
        <button type="button" class="btn-secondary" style="margin-top:0.75rem;">Tentar novamente</button>
      </div>
    </div>
  `;
  container.querySelector('button').addEventListener('click', retry);
}
