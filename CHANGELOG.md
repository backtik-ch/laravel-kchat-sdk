# Changelog

All notable changes to `laravel-kchat-sdk` will be documented in this file.

## v0.2.2 - 2026-11-18

- Reverted `channels()->public()` after the public-channel API returned 403 for the configured bot. Use `channels()->list()` to retrieve channels accessible to the authenticated user.

**Full Changelog**: https://github.com/backtik-ch/laravel-kchat-sdk/compare/v0.2.1...v0.2.2

## v0.1.1 - 2026-06-16

**Full Changelog**: https://github.com/backtik-ch/laravel-kchat-sdk/compare/v0.1.0...v0.1.1

## v0.1.0 - Initial release - 2026-06-16

**Full Changelog**: https://github.com/backtik-ch/laravel-kchat-sdk/commits/v0.1.0
