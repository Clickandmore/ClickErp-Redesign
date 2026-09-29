# Moyasar refunds in Click

Deploy the code and run the new migration only; historical full migrations in this repository have unrelated conflicts:

```sh
php artisan migrate --path=database/migrations/2026_09_29_180000_add_moyasar_refund_tracking.php --force
```

Configure the URL displayed in Moyasar settings, matching webhook secrets, and events payment_paid, payment_failed, payment_refunded.

Reconcile one known invoice first:

```sh
php artisan moyasar:sync-refunds --invoice=INVOICE_ID
```

Omit --invoice to synchronize all stored references. This command reads Moyasar and updates Click; it does not request money to be refunded. Failures remain retryable; check the failure count and application log.

Contract refunds create confirmed disbursement vouchers reversing the original receipt accounts and retaining the contract branch. Original receipts and revenue/VAT invoices remain unchanged except for paid/remaining balances. Cash refunds do not cancel services or issue tax credit notes. Contract closure and credit-note workflows remain separate. Posting requires an active financial year and a receipt creator/contract marketer for automated receipts.

Base-contract refunds reduce the base paid balance. Renewal refunds update the corresponding extension payment and reminder without changing dates, operational status or worker assignments. Hourly refunds are negative ledger entries. App orders expose refunded and net paid amounts. Duplicate and stale events never decrease the cumulative refund or duplicate accounting entries.

Existing paid-renewal behavior still applies when a pending renewal is synchronized as paid with no refund. A refunded notification never extends or reactivates a contract.