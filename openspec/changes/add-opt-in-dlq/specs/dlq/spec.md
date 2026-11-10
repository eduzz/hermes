## ADDED Requirements

### Requirement: Opt-in Dead Letter Queue declaration
The library SHALL allow declaring an additional durable Dead Letter Queue named `{queue}.dlq` bound to the default exchange (`eduzz`) with routing key `{queue}.dlq` when `$createDlq` is set to `true` in `addQueue`, without modifying any arguments of the primary queue `{queue}` or the error/retry queue `{queue}.nack`.

#### Scenario: Default addQueue keeps current behavior without DLQ
- **WHEN** `addQueue($name, $createErrorQueue, $durable, $retryTtl, $retryQueueBind)` is called without `$createDlq` or with `$createDlq = false`
- **THEN** only `{name}` and (if enabled) `{name}.nack` are declared and bound, and `{name}.dlq` is NOT declared

#### Scenario: Opt-in addQueue declares and binds DLQ
- **WHEN** `addQueue($name, true, true, 30000, $retryQueueBind, true)` is called with `$createDlq = true`
- **THEN** `{name}.dlq` is declared with empty arguments and bound to exchange `eduzz` with routing key `{name}.dlq`, while `{name}` and `{name}.nack` declarations remain unchanged

### Requirement: Explicit nackOrDeadLetter with max delivery attempts
The consumer SHALL provide `nackOrDeadLetter($msg, $maxAttempts = 3, $dlqRoutingKey = null, $exchange = 'eduzz')` that inspects the RabbitMQ `x-death` header for `reason = rejected` and either nacks the message for retry or publishes it to the `.dlq` routing key and acknowledges the original message once `$maxAttempts` is reached.

#### Scenario: Delivery attempt is below maxAttempts
- **WHEN** `nackOrDeadLetter($msg, 3)` is called on a message whose previous `rejected` death count plus the current attempt is less than `3`
- **THEN** the consumer calls `basic_nack` with `requeue = false` and does NOT publish to the DLQ

#### Scenario: Delivery attempt reaches maxAttempts
- **WHEN** `nackOrDeadLetter($msg, 3)` is called on a message whose previous `rejected` death count is `2` (3rd delivery attempt)
- **THEN** the consumer publishes the message payload to exchange `eduzz` with routing key `{queue}.dlq` and calls `basic_ack` on the original message
