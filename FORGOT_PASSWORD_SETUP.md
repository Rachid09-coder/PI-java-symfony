# Forgot Password Feature - Setup Guide

## Overview
The forgot password feature allows users to reset their password by receiving an email with a secure reset link.

## Files Modified/Created

### Backend
- **User Entity** (`src/Entity/User.php`): Added `resetToken` and `resetTokenExpiresAt` fields
- **SecurityController** (`src/Controller/SecurityController.php`): Added `forgotPassword()` and `resetPassword()` routes
- **Forms**:
  - `src/Form/ForgotPasswordType.php`: Form for requesting password reset
  - `src/Form/PasswordResetType.php`: Form for setting new password
- **Email Templates**:
  - `templates/security/email/reset_password_email.html.twig`: Email sent when user requests reset
  - `templates/security/email/password_reset_confirmation_email.html.twig`: Confirmation email after reset

### Frontend
- `templates/security/forgot_password.html.twig`: Page for requesting password reset
- `templates/security/reset_password.html.twig`: Page for setting new password
- `templates/security/login.html.twig`: Updated "Oublié ?" link to forgot password page

### Database
- `migrations/Version20260215000000.php`: Migration adding reset token fields

## Setup Instructions

### 1. Run Database Migration
```bash
php bin/console doctrine:migrations:migrate
```

### 2. Configure Gmail SMTP

#### Option A: Using Gmail Account with App Password (Recommended)

1. **Enable 2-Step Verification:**
   - Go to https://myaccount.google.com/security
   - Click "2-Step Verification"
   - Follow the setup process

2. **Generate an App Password:**
   - Go to https://myaccount.google.com/apppasswords
   - Select "Mail" and "Windows Computer"
   - Google will generate a 16-character password

3. **Update `.env` file:**
   ```env
   MAILER_DSN=smtp://your-email@gmail.com:your-app-password@smtp.gmail.com:587?encryption=tls
   ```

#### Option B: Using Gmail Account Password (Less Secure)

**Note:** Not recommended. Enable "Less secure app access" in Google Account, then:

```env
MAILER_DSN=smtp://your-email@gmail.com:your-password@smtp.gmail.com:587?encryption=tls
```

### 3. Update Email Sender Address

In `src/Controller/SecurityController.php`, update the `from()` email address:

```php
$email = (new Email())
    ->from('your-email@gmail.com')  // Change this to your actual email
    ->to($user->getEmail())
    // ...
```

## Routes

### Public Routes
- **GET** `/forgot-password` - Display forgot password form
- **POST** `/forgot-password` - Submit forgot password request
- **GET** `/reset-password/{token}` - Display password reset form
- **POST** `/reset-password/{token}` - Submit new password

## Features

✅ **Security:**
- Unique token generation for each reset request
- Token expiration (1 hour)
- Password validation (min 8 chars, uppercase, lowercase, digit)
- Secure password hashing

✅ **User Experience:**
- Beautiful, responsive forms matching your design
- Clear error messages
- Success/info notifications
- Email confirmation after password change

✅ **Email:**
- Professional HTML email templates
- Password reset instructions
- Security warnings
- Confirmation email after successful reset

## Testing

1. Go to http://127.0.0.1:8000 and click "Oublié ?"
2. Enter an email address
3. Check your Gmail inbox for the reset link
4. Click the link and set a new password
5. Login with your new password

## Troubleshooting

### Emails Not Sending
1. Check that `.env` has correct `MAILER_DSN`
2. Check Gmail SMTP credentials
3. Check Symfony logs: `tail -f var/log/dev.log`
4. If using App Password, ensure you removed spaces

### Token Expired
- Tokens expire after 1 hour
- User must request a new password reset link

### Invalid Token
- Token might have been used already
- Token might have expired
- Wrong token in URL

## Email Content Customization

Edit the email templates in:
- `templates/security/email/reset_password_email.html.twig`
- `templates/security/email/password_reset_confirmation_email.html.twig`

You can customize:
- Sender information
- Email subject
- Message content
- Styling
