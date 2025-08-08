# Deployment script for uploading files to remote SSH server using PowerShell
# Server details
$ServerIP = "103.7.4.177"
$ServerPort = "22"
$Username = "root"
$RemoteDir = "/root/hplinkbilling"

Write-Host "Starting deployment to $ServerIP:$ServerPort" -ForegroundColor Yellow

# Create remote directory if it doesn't exist
Write-Host "Creating remote directory $RemoteDir" -ForegroundColor Yellow
ssh -p $ServerPort $Username@$ServerIP "mkdir -p $RemoteDir"

# Get all files and directories in current folder
$Items = Get-ChildItem -Path . -Exclude "deploy.ps1" -Force

# Upload each item using SCP
Write-Host "Uploading files to remote server..." -ForegroundColor Yellow
foreach ($Item in $Items) {
    Write-Host "Uploading $($Item.Name)..." -ForegroundColor Cyan
    
    # Use SCP to upload the item
    $Command = "scp -P $ServerPort -r `"$($Item.FullName)`" $Username@$ServerIP`:$RemoteDir/"
    Invoke-Expression $Command
    
    if ($LASTEXITCODE -ne 0) {
        Write-Host "Failed to upload $($Item.Name)" -ForegroundColor Red
        exit 1
    }
}

Write-Host "Files successfully uploaded to $ServerIP:$RemoteDir" -ForegroundColor Green

# Set proper permissions
Write-Host "Setting proper permissions..." -ForegroundColor Yellow
ssh -p $ServerPort $Username@$ServerIP "chmod -R 755 $RemoteDir"
ssh -p $ServerPort $Username@$ServerIP "find $RemoteDir -type f -exec chmod 644 {} \;"
ssh -p $ServerPort $Username@$ServerIP "find $RemoteDir -name `"*.sh`" -exec chmod 755 {} \;"

Write-Host "Deployment completed successfully!" -ForegroundColor Green