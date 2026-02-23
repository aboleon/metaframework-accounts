# Dashboard

## Overview

The dashboard provides a financial summary across clients and invoices, including turnover figures, client statistics, and geographic breakdowns.

## Route

| Method | URI | Route Name |
|--------|-----|------------|
| GET | `/dashboard` | `mfw-accounts.dashboard.index` |

The route requires the `auth` middleware.

## DashboardController

The `DashboardController@index` action computes and passes the following data to the view:

### Turnover Metrics

| Metric | Description |
|--------|-------------|
| Operative turnover | Turnover from paid invoices in the selected year |
| Total turnover | Turnover from all invoices (paid + unpaid) in the selected year |

### Client Statistics

- Number of active clients
- Client breakdown by country

### Filters

The dashboard supports filtering by **year**. The year filter is applied to `invoice_date`.

## Multi-Currency Handling

Invoice amounts are aggregated across all currencies using the `conversion_rate` stored on each `Currency` record. Currencies without a conversion rate (i.e. the reference currency) are taken at face value. All dashboard figures are normalised to the reference currency for display.

## Per-Client Dashboard

Each account has its own mini-dashboard:

| Method | URI | Route Name |
|--------|-----|------------|
| GET | `/clients/{client}/dashboard` | `mfw-accounts.clients.dashboard` |

This view shows the invoice history, payment status, and summary figures for a single client.

## Currency Index

A read-only currency reference page is also available:

| Method | URI | Route Name |
|--------|-----|------------|
| GET | `/currency/index` | `mfw-accounts.currency.index` |
