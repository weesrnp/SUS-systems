/**
 * Utilitários de renderização de tabelas, formatação pt-BR e popovers de histórico.
 */

export const ICON_DOWNLOAD = `<svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4v11"></path><path d="m7 11 5 5 5-5"></path><path d="M5 20h14"></path></svg>`;
export const ICON_CHEVRON = `<svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg>`;

export function escapeHtml(str) {
  return String(str ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

export function formatSizeMB(bytes) {
  if (!bytes || bytes <= 0) return '–';
  const mb = bytes / 1048576;
  if (mb < 0.1) {
    return (bytes / 1024).toLocaleString('pt-BR', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + ' KB';
  }
  return mb.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' MB';
}

export function formatDateBR(isoStr) {
  if (!isoStr) return '–';
  const d = new Date(isoStr);
  if (isNaN(d.getTime())) return '–';
  const shifted = new Date(d.getTime() - 10800000); // Fuso Brasil / Brasília
  return shifted.toLocaleString('pt-BR', {
    timeZone: 'UTC',
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit'
  });
}

export function formatRelativeTime(isoStr) {
  if (!isoStr) return '–';
  const d = new Date(isoStr);
  if (isNaN(d.getTime())) return '–';
  const shifted = new Date(d.getTime() - 10800000);
  const diffMs = Date.now() - shifted.getTime();
  if (diffMs < 0) return 'Recente';
  const days = Math.floor(diffMs / 86400000);
  if (days < 1) return '<span class="fresh-pill">menos de 24h</span>';
  if (days < 30) return `há ${days} dia${days > 1 ? 's' : ''}`;
  const months = Math.floor(days / 30);
  if (months < 12) return `há ${months} ${months > 1 ? 'meses' : 'mês'}`;
  const years = Math.floor(months / 12);
  return `há mais de ${years} ano${years > 1 ? 's' : ''}`;
}

export function formatBRL(value) {
  return Number(value || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

export function formatSignedBRL(value) {
  const v = Number(value || 0);
  return (v > 0 ? '+' : v < 0 ? '-' : '') + formatBRL(Math.abs(v));
}

export function formatCount(n) {
  return Number(n || 0).toLocaleString('pt-BR');
}

export function formatCompetencia(raw) {
  const s = String(raw ?? '');
  const m = s.match(/^(\d{4})\/?(\d{2})([a-z])?$/i);
  if (m && +m[2] >= 1 && +m[2] <= 12) return `${m[2]}/${m[1]}${m[3] ? ` (${m[3]})` : ''}`;
  return s;
}

export function formatAgeRange(min, max) {
  const minStr = String(min ?? '').trim();
  const maxStr = String(max ?? '').trim();

  if (!minStr && !maxStr) return '–';
  if (minStr.toLowerCase() === 'não se aplica' && maxStr.toLowerCase() === 'não se aplica') {
    return 'Não se aplica';
  }
  if (minStr === maxStr) {
    return minStr;
  }
  if (!minStr) return maxStr;
  if (!maxStr) return minStr;
  return `${minStr} a ${maxStr}`;
}

export function loadingSkeletonHtml(label = 'Carregando dados...') {
  return `
    <div class="table-card shimmer-box" aria-busy="true">
      <span class="sr-only">${escapeHtml(label)}</span>
      <div class="skeleton-line" style="width: 35%; height: 22px;"></div>
      <div class="skeleton-line" style="width: 100%; height: 38px; margin-top: 0.5rem;"></div>
      <div class="skeleton-line" style="width: 100%; height: 38px;"></div>
      <div class="skeleton-line" style="width: 100%; height: 38px;"></div>
    </div>
  `;
}

/**
 * Constrói regex insensível a acentos e maiúsculas/minúsculas para um termo.
 * Se o termo for numérico (ex.: 030101), permite pontuação opcional (03.01.01).
 * @param {string} term
 * @returns {string}
 */
export function buildAccentRegex(term) {
  if (!term) return '';
  const trimmed = String(term).trim();
  if (/^\d{2,}$/.test(trimmed)) {
    return trimmed.split('').join('[.\\s-]*');
  }
  const escaped = trimmed.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  const charMap = {
    'a': '[aáàãâäAÁÀÃÂÄ]',
    'e': '[eéèêëEÉÈÊË]',
    'i': '[iíìîïIÍÌÎÏ]',
    'o': '[oóòõôöOÓÒÕÔÖ]',
    'u': '[uúùûüUÚÙÛÜ]',
    'c': '[cçCÇ]',
    'A': '[aáàãâäAÁÀÃÂÄ]',
    'E': '[eéèêëEÉÈÊË]',
    'I': '[iíìîïIÍÌÎÏ]',
    'O': '[oóòõôöOÓÒÕÔÖ]',
    'U': '[uúùûüUÚÙÛÜ]',
    'C': '[cçCÇ]'
  };
  const deaccented = escaped.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
  let pattern = '';
  for (const ch of deaccented) {
    pattern += charMap[ch] || ch;
  }
  return pattern;
}

/**
 * Destaca ocorrências do termo de busca no texto ignorando acentos e case.
 * @param {string} text Texto original a ser renderizado
 * @param {string} query Termo ou termos buscados pelo usuário
 * @returns {string} Fragmento HTML com partes correspondentes envolvidas em <mark class="search-highlight">
 */
export function highlightTerm(text, query) {
  if (!text) return '';
  const raw = String(text);
  const q = String(query || '').trim();
  if (!q) return escapeHtml(raw);

  const tokens = q.split(/\s+/).filter(t => t.length >= 1);
  const patterns = tokens.map(buildAccentRegex).filter(Boolean);
  if (patterns.length === 0) return escapeHtml(raw);

  try {
    const rx = new RegExp(patterns.join('|'), 'gi');
    let lastIndex = 0;
    let html = '';
    let match;
    while ((match = rx.exec(raw)) !== null) {
      const matchIndex = match.index;
      const matchedText = match[0];
      if (matchIndex > lastIndex) {
        html += escapeHtml(raw.substring(lastIndex, matchIndex));
      }
      html += `<mark class="search-highlight">${escapeHtml(matchedText)}</mark>`;
      lastIndex = matchIndex + matchedText.length;
      if (matchedText.length === 0) {
        rx.lastIndex++;
      }
    }
    if (lastIndex < raw.length) {
      html += escapeHtml(raw.substring(lastIndex));
    }
    return html;
  } catch (_) {
    return escapeHtml(raw);
  }
}

/**
 * Renderiza o skeleton realista em linhas de tabela para o carregamento do LogCarga Remessas.
 * @param {string} ibge
 * @param {string} ano
 * @returns {string}
 */
export function remessaSkeletonHtml(ibge, ano) {
  const skeletonRows = Array.from({ length: 6 }).map((_, i) => `
    <tr>
      <td><div class="skeleton-line" style="width: 75px; height: 18px;"></div></td>
      <td><div class="skeleton-line" style="width: 60px; height: 18px;"></div></td>
      <td><div class="skeleton-line" style="width: 110px; height: 18px;"></div></td>
      <td><div class="skeleton-line" style="width: 110px; height: 18px;"></div></td>
      <td><div class="skeleton-line" style="width: 110px; height: 18px;"></div></td>
      <td><div class="skeleton-line" style="width: ${i % 2 === 0 ? '70%' : '50%'}; height: 22px; border-radius: 9999px;"></div></td>
    </tr>
  `).join('');

  return `
    <div class="table-card shimmer-box remessa-loading-card" aria-busy="true">
      <div class="table-card-header">
        <div class="table-card-heading">
          <h2 class="table-card-title">Remessas Processadas — Município IBGE ${escapeHtml(ibge)} · Ano ${escapeHtml(ano)}</h2>
          <span class="table-card-ftp">ftp://arpoador.datasus.gov.br/siasus/LogCarga/</span>
        </div>
      </div>

      <div class="remessa-loading-status" id="remessaLoadingStatus" aria-live="polite">
        <span class="loading-spinner" aria-hidden="true"></span>
        <span class="remessa-loading-text" id="remessaLoadingText">Consultando o LogCarga do DataSUS…</span>
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
            ${skeletonRows}
          </tbody>
        </table>
      </div>
    </div>
  `;
}

/**
 * Fecha todos os popovers de versões anteriores abertos na página.
 */
export function closeAllPopovers(returnFocus = false) {
  document.querySelectorAll('.hist-wrap.open').forEach(w => {
    w.classList.remove('open');
    const btn = w.querySelector('.btn-hist');
    if (btn) {
      btn.setAttribute('aria-expanded', 'false');
      if (returnFocus) btn.focus();
    }
  });
}

/**
 * Posiciona o popover flutuante alinhado ao botão que o abriu.
 * @param {HTMLElement} wrap
 */
export function placePopover(wrap) {
  const btn = wrap.querySelector('.btn-hist');
  const pop = wrap.querySelector('.hist-popover');
  if (!btn || !pop) return;

  const r = btn.getBoundingClientRect();
  const width = pop.offsetWidth || 240;
  const height = pop.offsetHeight || 200;

  const left = Math.max(8, Math.min(r.right - width, window.innerWidth - width - 8));
  const spaceBelow = window.innerHeight - r.bottom;
  const top = spaceBelow < height + 12 && r.top > spaceBelow ? r.top - height - 5 : r.bottom + 5;

  pop.style.left = `${left}px`;
  pop.style.top = `${Math.max(8, top)}px`;
}
