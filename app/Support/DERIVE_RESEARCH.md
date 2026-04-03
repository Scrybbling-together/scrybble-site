# Derive — Real-world applicability research

Research into open-source Laravel projects where `Derive` would eliminate redundant factory state.

## Strongest case: Koel (~16k stars)

[`database/factories/SongFactory.php`](https://github.com/koel/koel/blob/master/database/factories/SongFactory.php)

4 denormalized attributes each requiring a separate closure + DB query to derive from the parent Album:

```php
'artist_id'   => static fn ($a) => Album::query()->find($a['album_id'])?->artist_id,
'artist_name' => static fn ($a) => Album::query()->find($a['album_id'])?->artist_name,
'album_name'  => static fn ($a) => Album::query()->find($a['album_id'])?->name,
'owner_id'    => static fn ($a) => Album::query()->find($a['album_id'])->user_id,
```

With Derive these become one-liners like `Derive::from('album.artist_name')`, eliminating 4 redundant DB queries per factory call.

## Multi-tenant pain: Monica (~22k stars)

[`database/factories/ContactFactory.php`](https://github.com/monicahq/monica/blob/main/database/factories/ContactFactory.php)

`Gender` must belong to the same `Account` as the `Contact`'s `Vault` — currently done with an inline closure that queries + creates. Multi-tenant apps have this everywhere: every child must manually thread `account_id`/`tenant_id` through.

## Hardcoded duplication: Crater (~8k stars)

[`database/factories/ItemFactory.php`](https://github.com/crater-invoice-inc/crater/blob/master/database/factories/ItemFactory.php)

Every financial factory (`Invoice`, `Item`, `Payment`, `Expense`) independently hardcodes `currency_id` and `company_id` rather than deriving from a relationship. Multi-currency testing requires overriding it in every factory.

## Community signal

- [laravel/framework#19230](https://github.com/laravel/framework/issues/19230) — "Factory creates extra related model"
- [laravel/framework#9245](https://github.com/laravel/framework/issues/9245) — "Relationships in factory builders"
- Multiple Laracasts threads about keeping factory state in sync between related models
- Laravel's official answer is the `fn ($attrs) =>` closure pattern, which is verbose and requires re-querying

## Where Derive fits best

1. **Denormalized schemas** (Koel) — copied columns that must match the source
2. **Multi-tenant apps** (Monica, Crater) — `tenant_id`/`org_id` threaded through every model
3. **Financial/e-commerce** — `currency`, `recurrence`, `pricing_tier` shared across order/line-item hierarchies

Private SaaS codebases likely benefit more than public ones since they tend to have heavier tenant scoping and domain-specific attribute cascading.