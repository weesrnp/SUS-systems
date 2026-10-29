<?php
declare(strict_types=1);
?>
<footer class="app-footer">
  <div class="footer-inner">
    <!-- Clusters FTP oficiais do DataSUS -->
    <div class="footer-clusters" aria-label="Status dos servidores FTP do Ministério da Saúde">
      <span class="clusters-heading">Servidores DataSUS:</span>
      <div class="clusters-list">
        <span class="cluster-tag" title="Servidor primário SIA, BPA e CNES">
          <span class="cluster-dot dot-online" aria-hidden="true"></span>
          <span class="cluster-host font-mono">arpoador.datasus.gov.br</span>
          <span class="cluster-sub">(SIA / CNES)</span>
        </span>

        <span class="cluster-tag" title="Servidor oficial da Tabela Unificada SIGTAP">
          <span class="cluster-dot dot-online" aria-hidden="true"></span>
          <span class="cluster-host font-mono">ftp2.datasus.gov.br</span>
          <span class="cluster-sub">(SIGTAP)</span>
        </span>

        <span class="cluster-tag" title="Servidor de disseminação CNES, PNI e TabWin">
          <span class="cluster-dot dot-online" aria-hidden="true"></span>
          <span class="cluster-host font-mono">ftp.datasus.gov.br</span>
          <span class="cluster-sub">(CNES / PNI / TabWin)</span>
        </span>
      </div>
    </div>

    <!-- Links Oficiais e Telemetria -->
    <div class="footer-links-row">
      <div class="footer-gov-links">
        <span class="footer-gov-title">Portais Oficiais:</span>
        <a href="https://wiki.saude.gov.br/siasus/" target="_blank" rel="noopener noreferrer" class="footer-link">Wiki SIA/SUS</a>
        <span class="link-sep" aria-hidden="true">·</span>
        <a href="https://sisaps.saude.gov.br/esus/" target="_blank" rel="noopener noreferrer" class="footer-link">Portal e-SUS APS</a>
        <span class="link-sep" aria-hidden="true">·</span>
        <a href="https://sigtap.saude.gov.br/" target="_blank" rel="noopener noreferrer" class="footer-link">SIGTAP Web</a>
        <span class="link-sep" aria-hidden="true">·</span>
        <a href="https://datasus.saude.gov.br/transferencia-de-arquivos/" target="_blank" rel="noopener noreferrer" class="footer-link">DataSUS Arquivos (.DBC)</a>
      </div>

      <div class="footer-telemetry">
        <span class="telemetry-badge">SUS-systems v3.0</span>
        <span class="telemetry-desc">PHP 8.3 Nativo · Sem Frameworks · Streaming Direto</span>
      </div>
    </div>
  </div>
</footer>
