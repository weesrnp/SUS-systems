<?php
declare(strict_types=1);

/**
 * Funções globais de asserção para testes sem dependências externas.
 * Todas as asserções lançam AssertionError em caso de falha.
 */

if (!function_exists('assert_same')) {
    /**
     * Verifica igualdade estrita (===).
     */
    function assert_same(mixed $expected, mixed $actual, string $msg = ''): void
    {
        if ($expected !== $actual) {
            $message = $msg !== ''
                ? $msg
                : sprintf(
                    "assert_same falhou.\nEsperado: %s\nObtido:   %s",
                    var_export($expected, true),
                    var_export($actual, true)
                );
            throw new AssertionError($message);
        }
    }
}

if (!function_exists('assert_true')) {
    /**
     * Verifica se o valor é estritamente true.
     */
    function assert_true(mixed $cond, string $msg = ''): void
    {
        if ($cond !== true) {
            $message = $msg !== ''
                ? $msg
                : sprintf("assert_true falhou. Esperado true, obtido: %s", var_export($cond, true));
            throw new AssertionError($message);
        }
    }
}

if (!function_exists('assert_contains')) {
    /**
     * Verifica se $needle está contido em $haystack (string ou array).
     */
    function assert_contains(mixed $needle, mixed $haystack, string $msg = ''): void
    {
        if (is_string($haystack)) {
            $needleStr = (string) $needle;
            if (!str_contains($haystack, $needleStr)) {
                $message = $msg !== ''
                    ? $msg
                    : sprintf(
                        "assert_contains falhou. Substring '%s' não encontrada na string:\n%s",
                        $needleStr,
                        $haystack
                    );
                throw new AssertionError($message);
            }
            return;
        }

        if (is_array($haystack)) {
            if (!in_array($needle, $haystack, true)) {
                $message = $msg !== ''
                    ? $msg
                    : sprintf(
                        "assert_contains falhou. Item %s não encontrado no array: %s",
                        var_export($needle, true),
                        var_export($haystack, true)
                    );
                throw new AssertionError($message);
            }
            return;
        }

        throw new AssertionError("assert_contains falhou: haystack deve ser string ou array.");
    }
}

if (!function_exists('assert_throws')) {
    /**
     * Executa $fn e verifica se lançou a classe de exceção esperada.
     * Retorna a exceção capturada para eventuais asserções adicionais.
     *
     * @template T of Throwable
     * @param class-string<T> $class
     * @param callable(): mixed $fn
     * @return T
     */
    function assert_throws(string $class, callable $fn, string $msg = ''): \Throwable
    {
        try {
            $fn();
        } catch (\Throwable $e) {
            if (!($e instanceof $class)) {
                $message = $msg !== ''
                    ? $msg
                    : sprintf(
                        "assert_throws falhou. Esperava exceção '%s', mas foi lançada '%s' com mensagem: '%s'",
                        $class,
                        get_class($e),
                        $e->getMessage()
                    );
                throw new AssertionError($message, 0, $e);
            }
            return $e;
        }

        $message = $msg !== ''
            ? $msg
            : sprintf("assert_throws falhou. Nenhuma exceção foi lançada, esperava '%s'.", $class);
        throw new AssertionError($message);
    }
}
