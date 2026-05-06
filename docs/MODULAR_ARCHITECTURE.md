# Modular Architecture (Transitional)

This project now includes a modular layer under `app/Modules` for seven domains:

- inventory
- accounting
- orders
- delivery
- workshop
- pos
- users

## Goals

- Keep full backward compatibility with existing namespaces under `App\\Models`, `App\\Services`, and `App\\Http\\Controllers`.
- Introduce domain-first organization for new code.
- Enable gradual migration without risky big-bang refactors.

## Structure

Each domain includes:

- `Models/` (module namespace wrappers over legacy models)
- `Repositories/` (interface + Eloquent implementation)
- `Services/` (domain service facade over repositories)
- `Http/Controllers/` (module namespace wrappers over legacy controllers)

## Container Bindings

`App\\Providers\\ModuleServiceProvider` binds repository interfaces to Eloquent implementations.

## Backward Compatibility

- Legacy classes are unchanged and continue to be used by current routes and business logic.
- Module wrappers extend legacy classes and can be adopted incrementally.
- No route path or existing controller namespace was removed.

## Migration Strategy

1. Keep existing routes and controllers running.
2. New features should prefer `App\\Modules\\{Domain}` namespaces.
3. Gradually update old controllers/services to consume module domain services.
4. After full migration, legacy namespaces can be slimmed down in controlled releases.
