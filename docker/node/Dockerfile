FROM node:16-alpine

# Install system dependencies
RUN apk add --no-cache bash

# Set working directory
WORKDIR /var/www/html

# Copy package files
COPY package*.json ./

# Install dependencies
RUN npm install

# Expose port
EXPOSE 3000

# Default command
CMD ["npm", "run", "dev"]