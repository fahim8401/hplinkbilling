@echo off
echo Testing SSH connection to 103.7.4.177:22
echo Please enter password: Hp@206274 when prompted
ssh -p 22 root@103.7.4.177 "echo Connection successful"
pause