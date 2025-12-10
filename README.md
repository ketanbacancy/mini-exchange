# Mini Exchange

A simplified crypto trading platform built with Laravel 12 and Vue 3.

## Requirements

- PHP 8.2+
- Composer
- Node.js & NPM
- MySQL
- **Pusher API Keys** (for real-time features)

## Installation

1.  **Clone the repository**
    ```bash
    git clone <repository_url>
    cd mini-exchange
    ```

2.  **Install PHP Dependencies**
    ```bash
    composer install
    ```

3.  **Install Frontend Dependencies**
    ```bash
    npm install
    ```

4.  **Environment Setup**
    - Copy `.env.example` to `.env`:
      ```bash
      cp .env.example .env
      ```
    - Configure your database in `.env`:
      ```env
      DB_CONNECTION=mysql
      DB_HOST=127.0.0.1
      DB_PORT=3306
      DB_DATABASE=your_database_name
      DB_USERNAME=your_username
      DB_PASSWORD=your_password
      ```
    - **CRITICAL: Configure Pusher & Session**
      Update these lines in `.env` to enable real-time features and fix session issues:
      ```env
      BROADCAST_CONNECTION=pusher
      SESSION_DOMAIN=127.0.0.1

      PUSHER_APP_ID=your_app_id
      PUSHER_APP_KEY=your_app_key
      PUSHER_APP_SECRET=your_app_secret
      PUSHER_APP_CLUSTER=ap2
      PUSHER_SCHEME=https
      ```
      *(Replace `your_app_*` with credentials from your Pusher dashboard)*

5.  **Generate Key & Migrate**
    ```bash
    php artisan key:generate
    php artisan migrate:refresh --seed
    ```
    *(The `--seed` flag creates the required test users)*

6.  **Build Assets**
    ```bash
    npm run build
    ```

## Usage

1.  **Start the Server**
    ```bash
    php artisan serve
    ```

2.  **Access the Application**
    Open [http://127.0.0.1:8000](http://127.0.0.1:8000) (Use 127.0.0.1 to match `SESSION_DOMAIN`).

3.  **Test Users**
    - **Buyer**: `alice@example.com` / `password`
    - **Seller**: `bob@example.com` / `password`

## Features

- **Order Matching**: Supports Limit Buy and Sell orders.
- **Real-time Updates**: Orders, Trades, and Balance updates push instantly via Pusher.
- **Bonus Features**:
    - **Advanced Filtering**: Filter by Symbol (BTC/ETH), Side, and Status.
    - **Recent Trades**: Live ticker of executed matches.
    - **Toast Notifications**: Interactive feedback for actions.
    - **UI Polish**: Formatted currency (decimals) and improved UX.

## Troubleshooting

- **Login Loops/Fails**: Ensure `SESSION_DOMAIN=127.0.0.1` in `.env` matches your browser URL.
- **Real-time not working**: Verify `BROADCAST_CONNECTION=pusher` and your Pusher keys in `.env`. Run `npm run build` after any `.env` change.
