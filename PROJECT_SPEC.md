# Liquidity Balancer — Project Spec

## Overview

A single-page **DEX liquidity deposit planner** for two-sided pools. Users enter pool value weights, market prices, and either current holdings or new capital; the app returns exact buy/sell amounts (with optional slippage buffer) so both sides match before deposit.

**Product URL / brand:** Liquidity-Balancer.com  
**Stack:** Laravel 10 (PHP 8.1+), Blade, static CSS in `public/css/style.css`, Vite scaffold (mostly unused for the calculator UI), PHPUnit.

## Current features

### Deposit planner modes (`LiquidityBalancer`)

| Mode | Behavior |
|------|----------|
| `rebalance` | Reshape existing Coin A/B holdings to match pool value weights (buy/sell both sides as needed). |
| `deploy_budget` | Split a dollar budget into Coin A and Coin B by pool weights (holdings ignored). |
| `keep_a` | Hold Coin A fixed; buy only the missing Coin B (reports capital required). |
| `keep_b` | Hold Coin B fixed; buy only the missing Coin A (reports capital required). |

- Optional **slippage buffer** (0–5%) inflates **buy** amounts only.
- Warnings when holdings are zero, capital missing, etc.

### HTTP / UI

- Routes: `/` → `/calculate` (`calculate.show` / `calculate.store`, throttled POST).
- FormRequest validation (`CalculateRequest`) — positive prices/amounts, mode allowlist, deploy-budget capital required.
- Blade UI: mode cards, pool ratio, prices, holdings, slippage; results as actionable buy/sell **receipts**, pool weight summary, swap instructions (e.g. Llamaswap), expandable ocean personality badge.
- Shared `x-input-field` component + tooltip partials.
- Client-side mode UI sync (show/hide capital vs holdings) via inline script on `calculate.blade.php`.

### Personality copy (`PortfolioCopy` + `config/portfolio_tiers.php`)

Tier labels by total adjusted USD value: Shrimp → Fish → Dolphin → Shark → Whale (default).

### Tests

- Unit: `LiquidityBalancerTest`, `PortfolioCopyTest`
- Feature: `CalculateTest` (redirect, form, modes, validation, results copy)

### Explicitly out of scope today

- No wallet connect, on-chain txs, or live price APIs
- No auth / multi-user accounts (Sanctum present but unused)
- No database-backed calculator state (SQLite/.env present for Laravel defaults only)
- Ads placeholder in layout is empty

## Architecture map

| Concern | Location |
|---------|----------|
| Routes | `routes/web.php` |
| Controller | `app/Http/Controllers/CalculationController.php` |
| Validation | `app/Http/Requests/CalculateRequest.php` |
| Rebalance math | `app/Services/LiquidityBalancer.php` |
| Personality | `app/Services/PortfolioCopy.php`, `config/portfolio_tiers.php` |
| Main view | `resources/views/calculate.blade.php` + `partials/` |
| Styles | `public/css/style.css` (Vite `resources/css/app.css` is empty) |
| Tests | `tests/Unit/*`, `tests/Feature/CalculateTest.php` |

## Local development

```bash
composer install
npm install          # optional; UI does not require Vite for the calculator
cp .env.example .env # or use existing .env (SQLite OK)
php artisan key:generate
php artisan serve    # http://127.0.0.1:8000/calculate
php artisan test
```

## Remaining TODOs / next milestones

1. **Docs polish** — Replace stock Laravel `README.md` with product-specific setup + mode explanations (this file is the agent-facing source of truth).
2. **Vite alignment** — Either wire calculator CSS/JS through Vite (`resources/css`, extract inline mode script to `resources/js`) or document that static `public/css` is intentional and trim unused Vite deps.
3. **Live prices (optional)** — CoinGecko (or similar) lookup helpers so users do not paste prices by hand; keep manual override.
4. **Ads / monetization** — Fill or remove `.ad-placeholder` once an ad partner is chosen.
5. **Accessibility / UX follow-ups** — Persist form state across refresh, clearer keep-side capital-required messaging, mobile pass on mode cards + receipts.
6. **Hardening** — Rate-limit messaging, CSRF already on; consider honeypot/bot friction if public traffic grows.
7. **Cleanup** — Unused `welcome.blade.php`; avoid committing file-mode-only (`644→755`) noise on storage/database gitignores; decide whether to track `package-lock.json`.
8. **Eval coverage** — Expand unit cases for keep-side edge cases and slippage; keep feature asserts tied to user-visible copy when UX copy changes.

## Agent conventions

- Keep controllers thin: domain math stays in `app/Services/*`.
- Prefer FormRequest + typed service return arrays over ad-hoc request arrays in controllers.
- When changing calculator UX copy, update `tests/Feature/CalculateTest.php` in the same change.
- Do not hardcode secrets; use `.env` / `.env.example`.
- See `.cursor/rules/*.mdc` for stack-scoped coding rules.
