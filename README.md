# PremierPayFast QR Pay Integration for Laravel 12

A complete, production-ready **PremierPayFast QR Pay** gateway integration for **Laravel 12**.

---

## 🚀 Features

- **Direct API QR Pay Flow**: Implements the official PremierPayFast Direct Transaction API flow:
  1. Access Token Generation (`/token`)
  2. Customer Validation & OTP Dispatch (`/customer/validate`)
  3. Transaction Processing with OTP (`/transaction`)
  4. Real-time Status Verification
- **Customizable QR Page UI**:
  - Dynamic branding (colors, store name, logo) via `.env` or config
  - Responsive layout optimized for mobile screens and desktop POS
  - Instant client-side QR rendering
  - Real-time auto-polling (AJAX) — automatically detects when the customer pays and redirects to receipt
  - "Pay On This Device" and "Copy Payment Link" buttons
  - Supported wallet badges: Easypaisa, JazzCash, Raast QR, and all 1Link banks
- **Multi-wallet / Account Support**:
  - Easypaisa (Wallet / Account)
  - JazzCash (Wallet)
  - Bank Accounts
  - UnionPay & Debit/Credit cards
- **Complete Transaction Lifecycle**:
  - `payfast_transactions` table with unique basket IDs, QR tokens, status tracking, and full API response logging
  - Dedicated receipts for successful payments (with print function) and clear error handling for failed attempts

---

## 🛠️ Requirements

- PHP >= 8.2
- Laravel 12.x
- Composer

---

## 📦 Installation & Setup

1. **Clone the repository:**
   ```bash
   git clone https://github.com/knsoftic/gatway.git
   cd gatway
   ```

2. **Install Composer dependencies:**
   ```bash
   composer install
   ```

3. **Configure Environment:**
   Copy `.env.example` to `.env` and generate an app key:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Add PayFast Credentials in `.env`:**
   ```env
   # PremierPayFast QR Pay Configuration
   PAYFAST_MERCHANT_ID=your_merchant_id_here
   PAYFAST_SECURED_KEY=your_secured_key_here
   PAYFAST_HASH_KEY=your_hash_key_here
   PAYFAST_BASE_URL=https://uat.premierpayfast.com/api # Use production URL for live

   # UI Customization
   PAYFAST_BRAND_NAME="My Store QR Pay"
   PAYFAST_BRAND_COLOR="#000e8f"
   PAYFAST_ACCENT_COLOR="#6bc197"
   PAYFAST_LOGO_URL=
   ```

5. **Run Migrations:**
   ```bash
   php artisan migrate
   ```

6. **Start the local server:**
   ```bash
   php artisan serve
   ```

---

## 🔗 Available Routes

| Method | URI | Description |
|---|---|---|
| `GET` | `/payfast/create` | Merchant form to generate a new QR payment |
| `POST` | `/payfast/create` | Stores transaction and creates unique QR link |
| `GET` | `/payfast/qr/{qrToken}` | Customer QR display page with auto-polling |
| `GET` | `/payfast/pay/{qrToken}` | Customer checkout form (Mobile & OTP) |
| `POST` | `/payfast/pay/{qrToken}/validate` | Step 1: Validates customer & requests OTP |
| `POST` | `/payfast/pay/{qrToken}/process` | Step 2: Verifies OTP & executes charge |
| `GET` | `/payfast/success/{qrToken}` | Success receipt page |
| `GET` | `/payfast/failed/{qrToken}` | Payment failed page |
| `GET` | `/payfast/status/{qrToken}` | AJAX JSON endpoint for real-time polling |

---

## 📁 Key Files & Architecture

- `config/payfast.php`: Configuration settings and UI parameters.
- `app/Services/PayfastService.php`: Core HTTP service handling authentication, customer validation, transaction execution, and signature calculations.
- `app/Http/Controllers/PayfastController.php`: Handles request lifecycle from QR display to OTP verification.
- `app/Models/PayfastTransaction.php`: Eloquent model tracking payment statuses (`pending`, `processing`, `completed`, `failed`).
- `database/migrations/2026_10_15_140810_create_payfast_transactions_table.php`: Database schema for transaction records.
- `resources/views/payfast/`:
  - `layout.blade.php`: Base responsive layout with CSS variables.
  - `create.blade.php`: Merchant QR invoice creator.
  - `qr.blade.php`: Customizable QR code display with real-time status polling.
  - `pay.blade.php`: Customer checkout & OTP input form.
  - `success.blade.php`: Printable receipt page.
  - `failed.blade.php`: Failure notification page.

---

## 🔒 Security
- All sensitive credentials are stored securely via environment variables (`.env`).
- API payloads are signed using HMAC-SHA256 according to PremierPayFast standards.
- Sensitive sessions and tokens are cleared upon transaction completion.
