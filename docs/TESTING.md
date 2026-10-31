# Guia de Testes — SUS-systems 3.0

O projeto utiliza uma suíte de testes unitários e de integração leve, **sem dependências externas** (sem PHPUnit ou Pest). Isso garante execução ultrarrápida, compatibilidade com qualquer ambiente PHP 8.2+ e zero overhead de instalação via Composer.

---

## 1. Como Executar os Testes

Como o PHP não está instalado nativamente na máquina host, utilize o container Docker `sus-systems-app` que já se encontra em execução:

```bash
# Executa todos os testes
docker exec sus-systems-app php tests/run.php

# Executar filtrando por suíte ou nome de arquivo
docker exec sus-systems-app php tests/run.php Core
docker exec sus-systems-app php tests/run.php Sigtap
docker exec sus-systems-app php tests/run.php Ftp

# Executar filtrando por substring no nome do teste
docker exec sus-systems-app php tests/run.php "404"
```

No ambiente local ou CI com PHP 8.2+ instalado no PATH:

```bash
php tests/run.php
```

---

## 2. Estrutura e Convenções

Todos os testes ficam localizados no diretório `tests/` e seguem o padrão de nomenclatura `tests/*Test.php`.

Cada arquivo de teste deve declarar tipos estritos (`declare(strict_types=1);`) e **retornar um array associativo** onde a chave é a descrição do caso de teste e o valor é uma função anônima (`callable`):

```php
<?php
declare(strict_types=1);

use SusSystems\Core\Request;

return [
    'Request: valida parâmetro obrigatório com sucesso' => function (): void {
        $req = new Request('GET', '/test', ['uf' => 'PR']);
        assert_same('PR', $req->required('uf'));
    },

    'Request: lança 422 quando parâmetro obrigatório está ausente' => function (): void {
        $req = new Request('GET', '/test', []);
        assert_throws(\SusSystems\Core\HttpException::class, function () use ($req) {
            $req->required('uf');
        });
    },
];
```

---

## 3. Helpers e Funções de Asserção (`tests/helpers.php`)

O runner carrega automaticamente `src/bootstrap.php` e `tests/helpers.php`. Em qualquer teste, você tem acesso às seguintes funções globais que lançam `AssertionError` em caso de divergência:

### `assert_same(mixed $expected, mixed $actual, string $msg = ''): void`
Verifica igualdade estrita (`===`).
```php
assert_same(200, $response->status());
assert_same(['status' => 'ok'], $data);
```

### `assert_true(mixed $cond, string $msg = ''): void`
Verifica se a condição é estritamente `true`.
```php
assert_true(is_array($resultado));
assert_true(file_exists($caminho));
```

### `assert_contains(mixed $needle, mixed $haystack, string $msg = ''): void`
Verifica se `$needle` está contido em `$haystack`:
- Se `$haystack` for string: verifica `str_contains()`.
- Se `$haystack` for array: verifica se o elemento existe no array com checagem estrita (`in_array(..., strict: true)`).
```php
assert_contains('competencia', $jsonString);
assert_contains('SP', $ufsDisponiveis);
```

### `assert_throws(string $class, callable $fn, string $msg = ''): Throwable`
Executa o callable `$fn` e garante que a classe de exceção `$class` (ou subtipo) foi lançada. Retorna a exceção capturada para checagens complementares (ex.: código de status HTTP, mensagem).
```php
$e = assert_throws(\SusSystems\Core\HttpException::class, function () {
    $router->dispatch(new Request('GET', '/404-rota', []));
});
assert_same(404, $e->status());
```

---

## 4. Boas Práticas para Novos Testes

1. **Isolamento e Limpeza**:
   - Caso o teste utilize arquivos temporários ou de cache, utilize diretórios com prefixo único via `sys_get_temp_dir()` e certifique-se de limpar os arquivos no bloco `finally`.
2. **Sem I/O Externo Lento**:
   - Para testes de domínio que dependem de FTP ou rede (como `FtpClient`), prefira mocks de socket/stream ou fixtures locais sempre que possível.
3. **Erros Formatados**:
   - Se um teste falhar, o runner imprime `✗`, o nome do teste, a classe do erro, a mensagem e as primeiras linhas do trace de execução.
4. **Código de Saída (Exit Code)**:
   - O runner retorna `0` quando todos os testes passam e `1` quando qualquer teste falha ou quando nenhum teste é encontrado para o filtro informado.
