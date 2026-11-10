## 1. Implementation

- [x] 1.1 Add `$createDlq = false` and `getDlqQueueNameFor()` to `src/CommonOperations.php`
- [x] 1.2 Implement `nackOrDeadLetter()` and `x-death` inspection in `src/Consumer/Consumer.php`
- [x] 1.3 Forward optional `$maxAttempts = null` in `src/Hermes.php`

## 2. Verification & Docs

- [x] 2.1 Add unit tests in `tests/CommonOperationsTest.php` and `tests/ConsumerTest.php`
- [x] 2.2 Document opt-in DLQ and `nackOrDeadLetter` in `README.md`
