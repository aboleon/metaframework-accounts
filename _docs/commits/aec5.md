# aec5 Update: use namespaced MetaFramework translations

## Summary

- Use the MetaFramework translation namespace in Accounts controllers, views, and mail templates.
- Require MetaFramework 2.x, where package translations are loaded directly from the package.
- Preserve application-level overrides through Laravel's `lang/vendor/mfw` convention.

## Verification

- `composer validate --strict`
- `vendor/bin/pint --dirty --format agent`
- `php vendor/bin/phpunit --testsuite Feature` (58 passed; 11 pre-existing Testbench/provider/model/route failures remain)
