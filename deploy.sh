#!/bin/bash

# Deployment script for uploading files to remote SSH server
# Server details
SERVER_IP="103.7.4.177"
SERVER_PORT="22"
USERNAME="root"
REMOTE_DIR="/root/hplinkbilling"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}Starting deployment to $SERVER_IP:$SERVER_PORT${NC}"

# Create remote directory if it doesn't exist
echo -e "${YELLOW}Creating remote directory $REMOTE_DIR${NC}"
ssh -p $SERVER_PORT $USERNAME@$SERVER_IP "mkdir -p $REMOTE_DIR"

# Upload all files and directories using SCP
echo -e "${YELLOW}Uploading files to remote server...${NC}"

# Upload the entire project directory
scp -P $SERVER_PORT -r ./* $USERNAME@$SERVER_IP:$REMOTE_DIR/

# Check if upload was successful
if [ $? -eq 0 ]; then
    echo -e "${GREEN}Files successfully uploaded to $SERVER_IP:$REMOTE_DIR${NC}"
    
    # Set proper permissions
    echo -e "${YELLOW}Setting proper permissions...${NC}"
    ssh -p $SERVER_PORT $USERNAME@$SERVER_IP "chmod -R 755 $REMOTE_DIR"
    ssh -p $SERVER_PORT $USERNAME@$SERVER_IP "find $REMOTE_DIR -type f -exec chmod 644 {} \;"
    ssh -p $SERVER_PORT $USERNAME@$SERVER_IP "find $REMOTE_DIR -name "*.sh" -exec chmod 755 {} \;"
    
    echo -e "${GREEN}Deployment completed successfully!${NC}"
else
    echo -e "${RED}Upload failed!${NC}"
    exit 1
fi