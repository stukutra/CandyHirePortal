# n8n Authentication

## Auto-generated Admin User

During setup, the scripts automatically create a default admin user in n8n:

**Credentials:**
- **Email**: `admin@candyhire.local`
- **Password**: `Admin123456`
- **Role**: Global Owner

## How It Works

1. After n8n container starts, the database is initialized with the n8n schema
2. n8n's official CLI command `user-management:reset` is used to create/reset the owner account
3. This ensures proper password hashing and all internal n8n initialization
4. If the user already exists, the password is reset to the default

## Why This Approach?

- **No manual registration required**: You can access n8n immediately after setup
- **Consistent credentials**: Same credentials across all environments (development)
- **Workflow import ready**: Having a user account ensures workflow imports work correctly
- **Database persistence**: Since we clean the database on each setup, the user is recreated automatically

## Security Notes

⚠️ **Production Warning**:
- Change these default credentials in production environments
- Use strong, unique passwords
- Consider implementing SSO or LDAP authentication for production

## Accessing n8n

1. Open browser: `http://localhost:5678`
2. Login with the credentials above
3. Start creating workflows!

## Basic Auth vs n8n Login

n8n has two authentication layers:

### 1. Basic HTTP Auth (Optional - Currently Enabled)
```env
N8N_BASIC_AUTH_ACTIVE=true
N8N_BASIC_AUTH_USER=admin
N8N_BASIC_AUTH_PASSWORD=candyhire_n8n_2024
```
This is HTTP-level authentication (browser popup).

### 2. n8n Internal Login (Always Required)
- Email: `admin@candyhire.local`
- Password: `CandyHire2024!`

This is the application-level authentication after you pass Basic Auth.

## Changing Credentials

### To Change Default Credentials

Edit the setup scripts ([setup.sh](setup.sh) or [setupUbuntu.sh](setupUbuntu.sh)):

```bash
N8N_ADMIN_EMAIL="your-email@example.com"
N8N_ADMIN_PASSWORD="YourSecurePassword123!"
N8N_ADMIN_FIRSTNAME="Your"
N8N_ADMIN_LASTNAME="Name"
```

### To Disable Basic Auth

Edit [.env](.env):
```env
N8N_BASIC_AUTH_ACTIVE=false
```

Then restart n8n:
```bash
docker compose restart n8n
```

## Troubleshooting

### Can't Login?

1. Check if user was created:
```bash
docker exec -e MYSQL_PWD="CandyHire2024Root" candyhire-portal-mysql mysql -uroot n8n -e "SELECT email, firstName, lastName, globalRole FROM user;"
```

2. If user doesn't exist, re-run setup or manually insert:
```bash
./setup.sh
# or
./setupUbuntu.sh
```

### Reset Password Manually

```bash
# Generate new hash (replace 'NewPassword123!' with your password)
NEW_HASH=$(docker exec candyhire-portal-php php -r "echo password_hash('NewPassword123!', PASSWORD_BCRYPT, ['cost' => 10]);")

# Update password
docker exec -e MYSQL_PWD="CandyHire2024Root" candyhire-portal-mysql mysql -uroot n8n -e "UPDATE user SET password = '$NEW_HASH' WHERE email = 'admin@candyhire.local';"
```

### Multiple Users?

To create additional users, use the n8n UI:
1. Login as admin
2. Go to Settings → Users
3. Click "Invite User"

Or insert directly into database (not recommended for production).
