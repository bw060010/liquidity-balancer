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
- Mode labels/subtitles from `LiquidityBalancer::MODE_CATALOG` (single source for form + results).
- Controller passes one `$result` array (plus `input` / `personality` / `modes`) to the view.
- Client-side mode UI sync in `public/js/calculate-mode.js` (form `data-mode-*` attrs; no inline script).
- Display helper `format_trimmed_number()` in `app/helpers.php`; slippage cap `SLIPPAGE_MAX_PCT`.

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
| Rebalance math | `app/Services/LiquidityBalancer.php` (`MODE_CATALOG`, `SLIPPAGE_MAX_PCT`) |
| Personality | `app/Services/PortfolioCopy.php`, `config/portfolio_tiers.php` |
| Main view | `resources/views/calculate.blade.php` + `partials/` |
| Mode UI JS | `public/js/calculate-mode.js` |
| Helpers | `app/helpers.php` (`format_trimmed_number`) |
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

## Production checklist

Before a public deploy:

- `APP_ENV=production` and `APP_DEBUG=false` (never ship with debug on).
- `APP_URL` matches the public HTTPS host (TrustHosts uses it).
- `SESSION_SECURE_COOKIE=true` when TLS terminates in front of the app.
- `TRUSTED_PROXIES=*` (or explicit proxy IPs) when behind a CDN / load balancer so rate limits see real client IPs.

## Remaining TODOs / next milestones

1. **Docs polish** — Replace stock Laravel `README.md` with product-specific setup + mode explanations (this file is the agent-facing source of truth).
2. **Vite alignment (optional)** — Calculator CSS/JS stay static under `public/` by design (`style.css`, `calculate-mode.js`). Optionally migrate to Vite later or trim unused Vite deps.
3. **Live prices (optional)** — CoinGecko (or similar) lookup helpers so users do not paste prices by hand; keep manual override.
4. **Ads / monetization** — Fill or remove `.ad-placeholder` once an ad partner is chosen.
5. **Accessibility / UX follow-ups** — Persist form state across refresh; clearer keep-side capital-required messaging. (Mode cards + receipts mobile/visual pass done.)
6. **Hardening (optional follow-ups)** — Consider honeypot/bot friction if public traffic grows; CSP/HSTS at reverse proxy.
7. **Cleanup** — Unused `welcome.blade.php`; avoid committing file-mode-only (`644→755`) noise on storage/database gitignores. (`package-lock.json` is tracked.)
8. **Eval coverage** — Expand unit cases for keep-side edge cases and slippage; keep feature asserts tied to user-visible copy when UX copy changes.

### Done recently

- Security remediation: `INPUT_MAX` + finite validation, TrustHosts/TrustProxies, escaped tooltips, production checklist, cleaned `.gitignore`.
- Calculator UI polish: spacing scale, paired field rows, buy/sell receipt badges, empty-results panel, sticky results on desktop; fixed New capital tooltip clipped by `#new-capital-section` overflow.
- Architecture DRY pass: parameterized `keepSide`, `MODE_CATALOG`, slim `$result` view bag, extracted mode JS, `format_trimmed_number` + `SLIPPAGE_MAX_PCT`.

## Agent conventions

- Keep controllers thin: domain math stays in `app/Services/*`.
- Prefer FormRequest + typed service return arrays over ad-hoc request arrays in controllers.
- When changing calculator UX copy, update `tests/Feature/CalculateTest.php` in the same change.
- Do not hardcode secrets; use `.env` / `.env.example`.
- See `.cursor/rules/*.mdc` for stack-scoped coding rules.
