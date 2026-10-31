/**
 * Cliente de API nativo para o SUS-systems 3.0.
 * Faz requisições para os endpoints /api/* preservando os contratos do backend.
 */

/**
 * Trata respostas de erro padronizadas pelo backend (JSON {detail: '...'} ou HTTP status).
 * @param {Response} response
 * @returns {Promise<Error>}
 */
async function parseError(response) {
  let detail = '';
  try {
    const data = await response.json();
    if (data && data.detail) {
      detail = String(data.detail);
    } else if (data && data.error) {
      detail = String(data.error);
    }
  } catch (_) {
    try {
      detail = await response.text();
    } catch (_) {
      detail = `Erro HTTP ${response.status}`;
    }
  }

  const err = new Error(detail || `Erro HTTP ${response.status} (${response.statusText})`);
  err.status = response.status;
  return err;
}

/**
 * Wrapper genérico para fetch com headers e tratamento de erros.
 * @param {string} url
 * @param {RequestInit} [options]
 * @returns {Promise<any>}
 */
async function fetchJson(url, options = {}) {
  try {
    const response = await fetch(url, {
      headers: {
        'Accept': 'application/json',
        ...(options.headers || {})
      },
      ...options
    });

    if (!response.ok) {
      throw await parseError(response);
    }

    return await response.json();
  } catch (err) {
    if (err.name === 'TypeError' && /failed to fetch|networkerror/i.test(err.message)) {
      throw new Error('Falha de conexão com o servidor SUS-systems. Verifique se o container está ativo.');
    }
    throw err;
  }
}

/**
 * Consulta o catálogo mestre de sistemas e UFs.
 */
export async function getCatalog() {
  return await fetchJson('/api/catalog');
}

/**
 * Obtém arquivos e seções de um subgrupo do catálogo via FTP.
 * @param {string} groupId
 * @param {string} subName
 * @param {boolean} [forceRefresh=false]
 */
export async function getSubgroupData(groupId, subName, forceRefresh = false) {
  const params = new URLSearchParams({
    group_id: groupId,
    sub_name: subName,
    force_refresh: forceRefresh ? 'true' : 'false'
  });
  return await fetchJson(`/api/subgroup-data?${params.toString()}`);
}

/**
 * Lista direta de arquivos de um diretório FTP.
 * @param {string} ftpUrl
 * @param {boolean} [forceRefresh=false]
 */
export async function getFtpList(ftpUrl, forceRefresh = false) {
  const params = new URLSearchParams({
    url: ftpUrl,
    force_refresh: forceRefresh ? 'true' : 'false'
  });
  return await fetchJson(`/api/ftp-list?${params.toString()}`);
}

/**
 * Lista competências disponíveis na Tabela Unificada SIGTAP.
 * @param {boolean} [forceRefresh=false]
 */
export async function getSigtapCompetencias(forceRefresh = false) {
  const params = new URLSearchParams({
    force_refresh: forceRefresh ? 'true' : 'false'
  });
  return await fetchJson(`/api/sigtap/competencias?${params.toString()}`);
}

/**
 * Pesquisa procedimentos na competência do SIGTAP com filtros.
 * @param {Object} options
 * @param {string} options.comp
 * @param {string} [options.q='']
 * @param {string} [options.grupo='']
 * @param {string} [options.complexidade='']
 * @param {string} [options.financiamento='']
 * @param {number} [options.limit=120]
 */
export async function searchSigtap({ comp, q = '', grupo = '', complexidade = '', financiamento = '', limit = 120 }) {
  const params = new URLSearchParams({
    comp,
    q,
    grupo,
    complexidade,
    financiamento,
    limit: String(limit)
  });
  return await fetchJson(`/api/sigtap/explore?${params.toString()}`);
}

/**
 * Executa o diff entre duas competências do SIGTAP.
 * @param {string} compNew
 * @param {string} compOld
 */
export async function diffSigtap(compNew, compOld) {
  const params = new URLSearchParams({
    comp_new: compNew,
    comp_old: compOld
  });
  return await fetchJson(`/api/sigtap/diff?${params.toString()}`);
}

/**
 * Consulta remessas do SIA no LogCarga por IBGE e ano.
 * @param {string} ibge
 * @param {string} ano
 */
export async function getRemessa(ibge, ano) {
  const params = new URLSearchParams({ ibge, ano });
  return await fetchJson(`/api/remessa?${params.toString()}`);
}

/**
 * Constrói a URL para download via streaming do gateway FTP.
 * @param {string} baseFtpUrl
 * @param {string} fileName
 * @returns {string}
 */
export function buildDownloadUrl(baseFtpUrl, fileName) {
  const params = new URLSearchParams({
    base: baseFtpUrl,
    file: fileName
  });
  return `/api/ftp-download?${params.toString()}`;
}

/**
 * Dispara o download de um arquivo no navegador.
 * @param {string} baseFtpUrl
 * @param {string} fileName
 */
export function triggerDownload(baseFtpUrl, fileName) {
  const url = buildDownloadUrl(baseFtpUrl, fileName);
  const a = document.createElement('a');
  a.href = url;
  a.download = fileName;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
}
