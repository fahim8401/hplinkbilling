# Deployment Instructions

This document provides instructions for deploying the ISP Billing & CRM application to your remote SSH server.

## Prerequisites

1. Windows 10/11 with OpenSSH client installed (comes by default in recent versions)
2. PowerShell 5.1 or later
3. Network access to the remote server (103.7.4.177:22)

## Deployment Methods

### Method 1: Using PowerShell Script (Recommended)

1. Double-click on `deploy-run.bat` to execute the PowerShell deployment script
2. When prompted, enter the password: `Hp@206274`
3. The script will automatically:
   - Create the remote directory `/root/hplinkbilling`
   - Upload all files and folders
   - Set proper permissions

### Method 2: Using Command Line

1. Open Command Prompt or PowerShell
2. Run the following command:
   ```
   powershell -ExecutionPolicy Bypass -File "deploy.ps1"
   ```
3. Enter the password when prompted

### Method 3: Manual SCP Transfer

You can also manually transfer files using SCP:
```
scp -P 22 -r * root@103.7.4.177:/root/hplinkbilling/
```

## Server Configuration

After deployment, you'll need to configure the server:

1. SSH into your server:
   ```
   ssh root@103.7.4.177
   ```

2. Navigate to the project directory:
   ```
   cd /root/hplinkbilling
   ```

3. Install PHP dependencies:
   ```
   composer install
   ```

4. Set proper permissions:
   ```
   chmod -R 755 storage bootstrap/cache
   chown -R www-data:www-data storage bootstrap/cache
   ```

5. Generate application key:
   ```
   php artisan key:generate
   ```

6. Configure your database in `.env` file

7. Run database migrations:
   ```
   php artisan migrate
   ```

## Troubleshooting

If you encounter any issues during deployment:

1. Ensure SSH access is working:
   ```
   ssh root@103.7.4.177
   ```

2. Check if SCP is available:
   ```
   scp --help
   ```

3. Verify firewall settings allow connections on port 22

4. Ensure sufficient disk space on the remote server

## Security Notes

- Change the default password after initial deployment
- Configure SSL certificates for production use
- Set up proper firewall rules
- Regularly update system packages