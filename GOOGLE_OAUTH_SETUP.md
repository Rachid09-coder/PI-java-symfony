# Google OAuth Login Setup Guide

## What's Been Configured

The following has been added to your Symfony application to support Google OAuth login:

### 1. **Database Migration**
- Created `migrations/Version20260220000000.php` to add `google_id` field to the `user` table
- Run the migration: `php bin/console doctrine:migrations:migrate`

### 2. **User Entity Update** 
- Added `googleId` field to `App\Entity\User`
- This stores the Google user ID for linking accounts

### 3. **Google Auth Controller**
- Created `src/Controller/GoogleAuthController.php` with two routes:
  - `/auth/google` - Initiates Google API login
  - `/auth/google/callback` - Handles Google OAuth callback

### 4. **Configuration Files Updated**
- Added OAuth parameters to `config/services.yaml`
- Created `.env` file with placeholder values for:
  - `OAUTH_GOOGLE_CLIENT_ID`
  - `OAUTH_GOOGLE_CLIENT_SECRET`
  - `OAUTH_GOOGLE_CALLBACK_URL`

### 5. **Login Template Updated**
- Modified `templates/auth/login.html.twig` to include Google login button
- The button links to `/auth/google` route

## Setup Instructions

### Step 1: Create Google OAuth Credentials

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project (or select existing)
3. Navigate to **APIs & Services** → **Credentials**
4. Click **Create Credentials** → **OAuth client ID**
5. Choose **Web application** and add:
   - **Authorized JavaScript origins**: 
     - `http://localhost:8000` (for development)
     - Your production domain
   - **Authorized redirect URIs**:
     - `http://localhost:8000/auth/google/callback`
     - Your production URL for callback
6. Copy the **Client ID** and **Client Secret**

### Step 2: Configure Environment Variables

Create or update `.env.local` file in your project root:

```env
OAUTH_GOOGLE_CLIENT_ID=your_client_id_here
OAUTH_GOOGLE_CLIENT_SECRET=your_client_secret_here
OAUTH_GOOGLE_CALLBACK_URL=http://localhost:8000/auth/google/callback
```

### Step 3: Run Database Migration

Execute the migration to add the `google_id` column:

```bash
php bin/console doctrine:migrations:migrate
```

### Step 4: Test the Installation

1. Start your Symfony development server:
   ```bash
   php bin/console server:run
   ```
   
2. Navigate to the login page:
   - http://localhost:8000/auth/login (for templates/auth/login.html.twig)
   - or http://localhost:8000/ (for templates/security/login.html.twig)

3. Click the Google button and follow the OAuth flow

## How It Works

1. **User clicks "Se connecter avec Google"** button on login page
2. **Redirected to Google OAuth consent screen** where user grants permissions
3. **Google redirects back** to `/auth/google/callback` with an authorization code
4. **Application exchanges** the code for an access token
5. **Application fetches** user info (email, name, etc.) from Google
6. **Application creates or finds** the user in the database
7. **User is logged in** and redirected to their dashboard

## User Creation from Google

When a user logs in with Google for the first time:
- A new user account is automatically created
- Email is taken from Google profile
- Name is taken from Google profile (first name only for now)
- Default role: `etudiant` (can be modified in `GoogleAuthController.php`)
- Phone number: Set to "0" as placeholder
- Existing users logging in via Google will be authenticated if email matches

## Security Features

- **State verification**: CSRF protection using OAuth state parameter
- **Email uniqueness**: Users are matched by email address
- **Automatic account creation**: New users are created with safe defaults
- **Google ID storage**: Prevents duplicate accounts

## Customization Options

### Change Default Role for New Google Users
Edit `src/Controller/GoogleAuthController.php`, line XX:
```php
$user->setRole('etudiant'); // Change this value
```

### Change Google Scopes
Edit `src/Controller/GoogleAuthController.php`, search for:
```php
'scope' => ['openid', 'email', 'profile']
```

### Change Callback Redirect
Edit `src/Controller/GoogleAuthController.php`, in `googleCallback()` method:
```php
return $this->redirectToRoute('app_redirect_user'); // Change target route
```

## Troubleshooting

### "Invalid state" error
- Clear browser cookies/cache
- Ensure `OAUTH_GOOGLE_CALLBACK_URL` matches exactly in both Google Console and `.env.local`

### "Client authentication failed" error
- Verify `OAUTH_GOOGLE_CLIENT_ID` and `OAUTH_GOOGLE_CLIENT_SECRET` are correct
- Check that credentials are in `.env.local` (not `.env`)

### User not being created
- Check database connection
- Verify `google_id` column exists in `user` table
- Check application logs for errors

## Next Steps (Optional)

1. **Update templates/security/login.html.twig** - Add the same Google button for consistency
2. **Add Microsoft OAuth** - Similar process with Microsoft Graph API
3. **Profile completion** - Require new OAuth users to complete phone number
4. **Email verification** - Verify email for OAuth users if needed
5. **Rate limiting** - Add rate limiting to OAuth endpoints

## Files Modified

- `src/Entity/User.php` - Added googleId field
- `src/Controller/GoogleAuthController.php` - New file for OAuth handling
- `config/services.yaml` - Added OAuth parameters
- `templates/auth/login.html.twig` - Updated with Google button
- `.env` - Added OAuth configuration variables
- `migrations/Version20260220000000.php` - Database migration

