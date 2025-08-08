# Deployment Files Summary

This document summarizes all the deployment files created for the ISP Billing & CRM system.

## Deployment Scripts

### 1. deploy.ps1
- PowerShell script for deploying files to remote SSH server
- Automatically creates remote directory
- Uploads all files and folders using SCP
- Sets proper permissions on remote server

### 2. deploy-run.bat
- Windows batch file to execute the PowerShell deployment script
- Bypasses PowerShell execution policy restrictions
- Provides a simple double-click deployment method

### 3. deploy.sh
- Bash shell script for Linux/Mac deployment
- Alternative deployment method using native SSH tools

### 4. deploy.bat
- Windows batch file for deployment using PuTTY tools
- Requires PuTTY to be installed separately

### 5. test-ssh.bat
- Simple script to test SSH connectivity to the server
- Helps verify server access before deployment

## Configuration Files

### 6. DEPLOYMENT.md
- Detailed deployment instructions
- Multiple deployment methods
- Server configuration steps
- Troubleshooting guide

### 7. README.md
- Updated with deployment instructions reference

## Usage Instructions

1. **For Windows users**: Double-click `deploy-run.bat` to start deployment
2. **For command-line users**: Run `powershell -ExecutionPolicy Bypass -File "deploy.ps1"`
3. **For Linux/Mac users**: Run `./deploy.sh` (make executable first with `chmod +x deploy.sh`)

## Server Details

- IP Address: 103.7.4.177
- Port: 22
- Username: root
- Password: Hp@206274
- Remote Directory: /root/hplinkbilling

## Post-Deployment Steps

After successful file transfer, you'll need to:

1. SSH into the server
2. Install PHP dependencies with `composer install`
3. Configure the `.env` file
4. Generate application key with `php artisan key:generate`
5. Run database migrations with `php artisan migrate`

## Security Notes

- Change the default password after initial deployment
- Configure SSL certificates for production use
- Set up proper firewall rules
- Regularly update system packages