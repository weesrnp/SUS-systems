# SUS-systems 3.0 — Arquitetura PHP

Reescrita do backend FastAPI (`legacy-python/`) em **PHP 8.2+ sem framework**, para rodar
em Docker, em hospedagem compartilhada (Apache + `.htaccess`) ou com `php -S`.
O código Python fica em `legacy-python/` **apenas como referência** — não editar.

## Princípios

1. **Contrato de API idêntico ao legado** — mesmos caminhos, parâmetros e chaves JSON
   (`legacy-python/app/main.py`). Erros: `{"detail": "..."}` com status HTTP.
2. **Sem dependências externas** — só extensões `ftp`, `zip`, `curl`, `mbstring`.
   Autoload PSR-4 próprio em `src/bootstrap.php` (namespace `SusSystems\` → `src/`).
3. **Cache em arquivo** (`SusSystems\Core\Cache`, em `storage/cache/`) — não usar estado estático
   entre requisições (PHP é share-nothing).
4. **Segurança** — FTP só para hosts em `config('ftp.allowed_hosts')` (anti-SSRF);
   nome de arquivo sempre `basename()`; toda saída HTML via `e()`.
5. `declare(strict_types=1)`, classes `final`, tipos em tudo, comentários curtos em português.

## Estrutura

```
public/            index.php (front controller), .htaccess, assets/{css,js,img}
config/            app.php (config + env), routes.php (rotas), catalog.php (catálogo de sistemas)
src/Core/          App, Router, Request, Response, StreamResponse, Cache, Config, View, HttpException
src/Http/Controllers/  Page, Health, Catalog, Ftp, Sigtap, Remessa
src/Domain/Ftp/    FtpClient, ListingParser, FileGrouper
src/Domain/Sigtap/ SigtapRepository
src/Domain/Catalog/Catalog
src/Domain/Remessa/RemessaService
templates/         layout.php, partials/*.php, pages/*.php
storage/cache/     cache em arquivo (gitignored)
tests/             run.php (runner próprio) + *Test.php
docker/            Dockerfile (php:8.3-apache)
```

## Core (pronto — não alterar sem combinar com o maestro "Claude Code")

- `Request`: `->str('k')`, `->required('k')` (422 se faltar), `->int('k', def, min, max)`, `->bool('k')`.
- Controller retorna `array` (vira JSON) ou `Response` / `StreamResponse`.
- `Response::json($data, $status, $headers)`.
- `new StreamResponse(function (callable $write) { $write($chunk); }, $headers)`.
- `throw new HttpException(404, 'mensagem')`.
- `Cache::default()->remember($key, $ttl, fn() => ..., $forceRefresh)`.
- `Config::get('ftp.timeout')`.
- `View::render('pages/home', [...])` → usa `templates/layout.php` com `$content`.

## Interfaces entre módulos (implementar exatamente estas assinaturas)

### `SusSystems\Domain\Ftp\FtpClient` — dono: **Lucio**
```php
/** @return array{0: list<array{name:string,size:int,modifiedAt:string}>, 1: string} [entries, sourceMode] */
public function listDirectory(string $ftpUrl, bool $forceRefresh = false): array;
// sourceMode: 'CACHE' | 'FTP_DIRECT' | 'HTTPS_BRIDGE' | 'OFFLINE'

/** Faz streaming do arquivo chamando $write(chunk) — retorna bytes enviados. */
public function streamFile(string $baseFtpUrl, string $filename, callable $write): int;

/** Tamanho via SIZE (0 se desconhecido). */
public function fileSize(string $baseFtpUrl, string $filename): int;

/** Baixa para um arquivo temporário local e retorna o caminho (usado pelo SIGTAP). */
public function downloadToTemp(string $baseFtpUrl, string $filename): string;

public static function assertAllowedUrl(string $ftpUrl): void; // HttpException 400 se host não permitido
```

### `SusSystems\Domain\Ftp\FileGrouper` — dono: **Lucio**
```php
/** Port fiel de process_and_group_files() do legado. */
public function group(array $rawFiles, array $section): array;
// $section usa as chaves do catálogo: includePrefixes, includeRegexes, excludePrefixes,
// excludeRegexes, descriptionRules. Saída: mesmas chaves do legado (name, displayName,
// description, size, modifiedAt, ym, kind, history, ufBySigla, ufHistoryBySigla).
```

### `SusSystems\Domain\Catalog\Catalog` — dono: **Lucio**
```php
public function areas(): array;               // config/catalog.php
public function ufs(): array;                  // siglas ordenadas
public function findSubgroup(string $groupId, string $subName): ?array;
```

### `SusSystems\Domain\Sigtap\SigtapRepository` — dono: **Alpha**
```php
public function competencias(bool $forceRefresh = false): array;
public function search(string $comp, string $q, string $grupo, string $complexidade, string $financiamento, int $limit): array;
public function diff(string $compNew, string $compOld): array;
```

## Divisão do trabalho

| Agente | Escopo (arquivos que só ele edita) |
| --- | --- |
| **Claude Code** (maestro) | `src/Core/*`, `config/app.php`, `config/routes.php`, `public/index.php`, integração, revisão, merge |
| **Lucio** | `src/Domain/{Ftp,Catalog}/*`, `config/catalog.php`, `Http/Controllers/{Ftp,Catalog}Controller.php`, `tests/{Ftp,Catalog}*Test.php` |
| **Alpha** | `src/Domain/{Sigtap,Remessa}/*`, `Http/Controllers/{Sigtap,Remessa}Controller.php`, `tests/{Sigtap,Remessa}*Test.php` |
| **Noticia** | `templates/**`, `public/assets/{css,js}/**`, `Http/Controllers/PageController.php` |
| **Midia** | `tests/run.php`, `.github/workflows/*`, `README.md`, `docs/*` (exceto este arquivo), `run_windows.bat`, `run_linux.sh`, QA no portal |

## Rodar

```bash
docker compose up -d --build        # http://localhost:8090  (código montado ao vivo)
docker exec sus-systems-app php tests/run.php
docker exec sus-systems-app php -l src/Domain/Ftp/FtpClient.php
```
Não há PHP instalado no host Windows — **use sempre o container** `sus-systems-app`.
