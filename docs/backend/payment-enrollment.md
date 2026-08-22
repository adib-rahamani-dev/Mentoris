# Payment & Enrollment

Phase 8 adds course checkout, orders, transactions, payment result handling, and enrollment synchronization.

## Architecture

```text
Checkout / Enrollment
        ↓
PaymentService
        ↓
PaymentGatewayInterface
        ↓
├── SandboxIranianGateway
└── IranianGateway
```

Controllers never call a provider directly. `PaymentService` creates the order and capacity reservation, asks the selected gateway for a payment authority, records the transaction, verifies the callback server-side, and only then finalizes the order and creates the enrollment. Repeated callbacks are idempotent.

Orders reserve one seat for 15 minutes. Paid enrollments are synchronized to the authenticated user's `courses` collection so they appear in My Courses. Free courses create a zero-value verified transaction and enrollment without leaving the site.

## Local sandbox

`PAYMENT_GATEWAY=sandbox` is the safe default. The sandbox creates an order and transaction and displays a simulated Iranian payment page where success or cancellation can be tested. It never collects card data and never transfers funds.

## Production gateway

Set `PAYMENT_GATEWAY=iranian` and configure:

- `IRANIAN_GATEWAY_MERCHANT_ID`
- `IRANIAN_GATEWAY_REQUEST_URL`
- `IRANIAN_GATEWAY_VERIFY_URL`
- `IRANIAN_GATEWAY_START_URL`
- `IRANIAN_GATEWAY_AMOUNT_MULTIPLIER` (defaults to `10` when the provider expects rial and Mentoris stores toman)

The bundled `IranianGateway` expects a JSON provider contract with `merchant_id`, `amount`, `callback_url`, `description`, `authority`, `code`, and `ref_id`. Confirm the exact payload and response contract with the selected provider before production. If its API differs, add a new adapter implementing `PaymentGatewayInterface`; checkout and enrollment code remain unchanged.

Use HTTPS in production, protect merchant credentials outside version control, reconcile verified transactions with provider reports, and define refund/accounting workflows before accepting live money.
