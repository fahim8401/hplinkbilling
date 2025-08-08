@echo off
REM Deployment script for uploading files to remote SSH server
REM Server details
set SERVER_IP=103.7.4.177
set SERVER_PORT=22
set USERNAME=root
set REMOTE_DIR=/root/hplinkbilling

echo Starting deployment to %SERVER_IP%:%SERVER_PORT%

REM Create remote directory if it doesn't exist
echo Creating remote directory %REMOTE_DIR%
plink -P %SERVER_PORT% %USERNAME%@%SERVER_IP% "mkdir -p %REMOTE_DIR%"

REM Upload all files and directories using SCP
echo Uploading files to remote server...
pscp -P %SERVER_PORT% -r * %USERNAME%@%SERVER_IP%:%REMOTE_DIR%/

REM Check if upload was successful
if %ERRORLEVEL% EQU 0 (
    echo Files successfully uploaded to %SERVER_IP%:%REMOTE_DIR%
    
    REM Set proper permissions
    echo Setting proper permissions...
    plink -P %SERVER_PORT% %USERNAME%@%SERVER_IP% "chmod -R 755 %REMOTE_DIR%"
    plink -P %SERVER_PORT% %USERNAME%@%SERVER_IP% "find %REMOTE_DIR% -type f -exec chmod 644 {} \;"
    plink -P %SERVER_PORT% %USERNAME%@%SERVER_IP% "find %REMOTE_DIR% -name "*.sh" -exec chmod 755 {} \;"
    
    echo Deployment completed successfully!
) else (
    echo Upload failed!
    exit /b 1
)