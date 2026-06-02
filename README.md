# PensoPay Payment Module for Maho Commerce

![Maho Commerce](https://img.shields.io/badge/Maho_Commerce-module-orange)
![Maho Version](https://img.shields.io/badge/Maho-%3E%3D26.5-orange)
![License](https://img.shields.io/badge/license-OSL--3.0-blue)
![PHP](https://img.shields.io/badge/php-%3E%3D8.3-8892BF)
![PHPStan Level](https://img.shields.io/badge/PHPStan-level%208-brightgreen)

A QuickPay payment gateway integration for Maho Commerce, supporting multiple Nordic and international payment methods through PensoPay.

## Requirements

- Maho Commerce 26.5+
- PHP 8.3+
- PensoPay/QuickPay merchant account

## Installation

```bash
composer require mahocommerce/module-pensopay
```

## Features

### Payment Methods

- **Credit Card** - Visa, MasterCard, Dankort, American Express, JCB, Diners Club, Maestro
- **MobilePay** - MobilePay Online payments
- **Klarna** - Klarna and Klarna Payments
- **ViaBill** - ViaBill payment with pricetag integration
- **Vipps** - Vipps mobile payments
- **PayPal** - PayPal via QuickPay
- **Anyday** - Anyday split payments
- **Dankort** - Standalone Dankort payments

### Additional Features

- **Virtual Terminal** - Create and manage payments directly from the admin panel
- **Multiple Checkout Modes** - Redirect, embedded, or iframe-based payment
- **Autocapture** - Optional automatic payment capture
- **Fraud Detection** - Fraud probability reporting from QuickPay
- **Payment Link Emails** - Send payment links to customers via email
- **Mass Actions** - Bulk capture, refund, and cancel from admin grids
- **Test Mode** - Sandbox support with separate credentials

## Configuration

1. Navigate to **System > Configuration > Payment Methods**
2. Configure **PensoPay Settings** with your API credentials:
   - API Key
   - Private Key
   - Agreement ID
3. Enable and configure individual payment methods as needed

## Credits

Originally developed by [PensoPay](https://pensopay.com), now adapted for [Maho](https://mahocommerce.com).
