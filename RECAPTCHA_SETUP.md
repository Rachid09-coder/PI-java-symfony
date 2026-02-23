# reCAPTCHA Setup (Google)

This document explains how to obtain Google reCAPTCHA site and secret keys and how to configure this project.

## 1) Register your site with Google reCAPTCHA

1. Open the reCAPTCHA admin console: https://www.google.com/recaptcha/admin
2. Sign in with your Google account.
3. Click **+ Create** (or **Register a new site**).
4. Fill in the form:
   - **Label:** a descriptive name (e.g., `EduSmart - Production`).
   - **reCAPTCHA type:** choose **reCAPTCHA v2** → **"I'm not a robot" Checkbox** (this project uses v2 checkbox by default).
   - **Domains:** add the domain(s) where the site will run (e.g., `example.com`). For local testing add `localhost` and `127.0.0.1`.
   - **Owners:** verify your email(s).
   - Accept the reCAPTCHA Terms of Service and click **Submit**.
5. After registration you will see two values: **Site key** and **Secret key**.

## 2) Local test keys (optional)

Google provides public test keys you can use while developing (they always validate but are intended only for testing):

- **Site key:** `6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI`
- **Secret key:** `6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe`

Do NOT use the test keys in production.

## 3) Add keys to this Symfony project

1. Open or create `.env.local` at the project root.
2. Add the two variables (replace with your real keys):

```ini
RECAPTCHA_SITE_KEY=your_site_key_here
RECAPTCHA_SECRET=your_secret_key_here
```

PowerShell example:

```powershell
echo "RECAPTCHA_SITE_KEY=your_site_key_here" >> .env.local
echo "RECAPTCHA_SECRET=your_secret_key_here" >> .env.local
```

3. Clear Symfony cache and restart dev server:

```bash
php bin/console cache:clear
# then run your server (e.g. symfony server:start or your web server)
```

## 4) Verify on the login page

- Open the app login page (root `/`).
- You should see the reCAPTCHA checkbox. Complete it and submit. If keys are invalid or missing, the form will show an error.

## 5) Security and best practices

- Keep the **secret** out of source control; use `.env.local` or your deployment secrets manager.
- Restrict the allowed domains in the reCAPTCHA admin console to the domains you control.
- Rotate the secret if it may have been exposed.

## 6) Alternatives

- If you prefer **Invisible reCAPTCHA** or **reCAPTCHA v3**, tell me and I can switch the frontend and server verification accordingly.

---
File: `RECAPTCHA_SETUP.md`
