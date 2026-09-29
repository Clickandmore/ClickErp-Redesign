# Customer app authentication

The login settings page has independent website and customer app controls.
`settings.app_auth_mode` accepts `password` (default) or `otp`. Apply migration
`2026_09_30_110000_add_app_auth_mode_to_settings_table.php` before saving it.
Only administrators with general settings control may change the app mode.

`GET /api/user/settings` includes:

```json
{
  "data": {
    "app_auth_mode": "otp",
    "uses_otp_authentication": true
  }
}
```

`GET /api/settings` also includes `app_auth_mode` and
`app_uses_otp_authentication`. Its existing `external_auth_mode` and
`uses_otp_authentication` still describe the website.

- Password mode: `POST /api/app/auth/login` with `phone` and `password`.
- OTP mode: the same login endpoint accepts `phone` alone and sends the code.
  `next_step: verify_otp` means verification is pending; there is no token yet.
  Submit `phone`, `phone_code`, and `code` to `/api/app/auth/confirm-code`.
- New customers must use `/api/app/auth/register` first. Password and confirmation
  are required in password mode and optional in OTP mode; the other registration
  fields remain required. OTP registration returns no token until verification.
- `verified: true` without a token does not mean login completed. If
  `next_step: register` is returned, create the account first.

The mobile client must read the settings and render the corresponding fields.
Changing the server setting does not itself update an already shipped app UI.
