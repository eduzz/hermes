## Why

Filas configuradas com retry TTL e `retryQueueBind` no Hermes entram em loop infinito de retentativas quando o consumidor falha permanentemente. Além disso, as filas já declaradas em produção não podem ter seus argumentos (`x-dead-letter-*`) alterados sem recriação. Precisamos de uma fila Dead Letter final (`{name}.dlq`) opt-in e de um método explícito no consumidor (`nackOrDeadLetter`) que conte as rejeições via header `x-death` do RabbitMQ e publique a mensagem na DLQ após atingir o limite de tentativas, dando `ack` na fila principal sem alterar argumentos das filas existentes.

## What Changes

- Adicionar parâmetro opcional `$createDlq = false` em `CommonOperations::addQueue()` para declarar e bindar a fila `{name}.dlq` sem modificar a declaração de `{name}` ou `{name}.nack`.
- Adicionar helper `CommonOperations::getDlqQueueNameFor($name)` e constantes para sufixos e exchange padrão.
- Corrigir chamada interna `getNackQueueNameFor($name)` em `CommonOperations::addQueue()`.
- Adicionar método explícito `Consumer::nackOrDeadLetter($msg, $maxAttempts = 3, $dlqRoutingKey = null, $exchange = 'eduzz')` que inspeciona `application_headers['x-death']` (`reason === 'rejected'`), envia `nack` enquanto abaixo de `$maxAttempts`, e publica na fila `.dlq` + `ack` ao atingir `$maxAttempts`.
- Permitir opcionalmente `$maxAttempts = null` em `Consumer::addListenerTo()` e `Hermes::addListenerTo()` para consumidores com `$errorHandling = true`.

## Impact

- `src/CommonOperations.php`
- `src/Consumer/Consumer.php`
- `src/Hermes.php`
- `tests/CommonOperationsTest.php`
- `tests/ConsumerTest.php`
- `README.md`
