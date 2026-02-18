# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

PensoPay_Payment is a **MahoCommerce module** (migrated from Magento 1.x) that integrates with the **QuickPay payment gateway** (api.quickpay.net). It provides multiple Nordic payment methods: credit cards, MobilePay, Klarna, ViaBill, Dankort, Vipps, PayPal, and Anyday.

**Module version:** 1.2.2
**Code pool:** `app/code/community/PensoPay/Payment`
**Platform:** MahoCommerce (PHP 8.3+)
**Installation:** Composer

## Development Setup

- Install via Composer: `composer require pensopay/module-pensopay`
- No automated test suite or CI/CD pipeline
- No linting configuration
- Static assets live under `public/skin/` (not `skin/`)

## Architecture

### Payment Flow

1. Customer selects payment method at checkout
2. `PaymentController::redirectAction()` creates payment via `Api::createPayment()` + `Api::createPaymentLink()`
3. Customer is redirected to QuickPay hosted payment page
4. QuickPay sends callback to `PaymentController::callbackAction()` with HMAC-SHA256 signed payload
5. Order status updated based on payment result

### Key Classes

- **`Model/Api.php`** — QuickPay REST API client (base URL: `https://api.quickpay.net`). Uses Symfony HttpClient. Handles create/capture/refund/cancel operations.
- **`Model/Method.php`** — Base payment method class. All specific methods (Klarna, MobilePay, etc.) extend this in `Model/Method/`.
- **`Model/Payment.php`** — Payment record model backed by `pensopay_payment` DB table. Stores operations/metadata as JSON.
- **`Model/Observer.php`** — Event observers for order lifecycle, ViaBill pricetag injection, admin mass actions, and cron jobs.
- **`Model/Config.php`** — Configuration path constants (`XML_PATH_*`).
- **`Helper/Checkout.php`** — Checkout configuration access and state management.
- **`Helper/Data.php`** — Utility functions (color processing, email sending, ViaBill config).

### Controllers

- **`controllers/PaymentController.php`** — Frontend payment flow (redirect, callback, success, cancel)
- **`controllers/MobilepayController.php`** — MobilePay Checkout with address/shipping selection
- **`controllers/Adminhtml/PensopayController.php`** — Admin Virtual Terminal

### Checkout Modes

Configured via `checkout_method` setting:
- `redirect` — redirect to QuickPay hosted page
- `embedded` — embedded payment form
- `iframe` — iframe-based payment

### Cron Jobs

- `update_virtualterminal_payment_status` — every 10 minutes
- `pensopay_pending_payment_order_cancel` — hourly

### API Authentication

- HMAC-SHA256 checksum validation on callbacks (private key)
- API key used for outbound requests
- Test mode support with separate credentials

## Conventions

- MahoCommerce patterns: XML config in `etc/`, factory methods via `Mage::getModel('pensopay/...')`, `Mage::helper('pensopay')`, `Mage::getStoreConfig()`
- Uses `\Maho\DataObject` instead of `Varien_Object`, `\Maho\Event\Observer` instead of `Varien_Event_Observer`, `\Maho\Data\Form` instead of `Varien_Data_Form`, etc.
- HTTP requests use `Symfony\Component\HttpClient\HttpClient` (not Zend_Http_Client)
- Payment amounts sent to API in minor units (cents) — multiply by 100
- Each payment method has its own Model class in `Model/Method/` that sets `$_paymentMethods` to the QuickPay method string
- The `pensopay_payment` table links to orders and stores the full QuickPay state (operations JSON, fraud probability, hash)
- MobilePay Checkout is a special flow with its own shipping carrier (`Model/Carrier/Shipping.php`) and address handling
- Use short array syntax `[]` everywhere (no `array()`)
- Use `Mage::LOG_WARNING` (not bare `LOG_WARNING` constant)
- Use `json_encode()`/`json_decode()` for serialization (not `serialize()`)
