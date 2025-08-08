# Docker Containerization Setup

## Overview
This document outlines the Docker containerization setup for the ISP Billing & CRM system. The setup includes multiple containers for different services, environment configuration, volume management, and network setup.

## Container Architecture

### Services
1. **Nginx** - Web server and reverse proxy
2. **PHP-FPM** - Laravel application server
3. **PostgreSQL** - Primary database
4. **Redis** - Cache and queue backend
5. **Supervisor** - Process control for queue workers
6. **Node.js** - Frontend asset compilation

## Directory Structure

```
docker/
├── nginx/
│   ├── Dockerfile
│   └── nginx.conf
├── php/
│   ├── Dockerfile
│   ├── php.ini
│   └── supervisord.conf
├── postgres/
│   ├── Dockerfile
│   └── init-scripts/
├── redis/
│   └── Dockerfile
├── node/
│   └── Dockerfile
├── docker-compose.yml
├── docker-compose.dev.yml
├── docker-compose.prod.yml
└── .env.example
```

## Docker Compose Configuration

### Base docker-compose.yml

```yaml
version: '3.8'

services:
  nginx:
    build:
      context: ./docker/nginx
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./src:/var/www/html
      - ./docker/nginx/nginx.conf:/etc/nginx/nginx.conf
      - ./storage/logs/nginx:/var/log/nginx
    depends_on:
      - php
    networks:
      - app-network

  php:
    build:
      context: ./docker/php
    volumes:
      - ./src:/var/www/html
      - ./storage/logs/php:/var/log/php
    networks:
      - app-network
    depends_on:
      - postgres
      - redis

  postgres:
    image: postgres:13
    environment:
      POSTGRES_DB: hplinkbilling
      POSTGRES_USER: hplink
      POSTGRES_PASSWORD: secret
    volumes:
      - postgres_data:/var/lib/postgresql/data
      - ./docker/postgres/init-scripts:/docker-entrypoint-initdb.d
    ports:
      - "5432:5432"
    networks:
      - app-network

  redis:
    image: redis:6-alpine
    ports:
      - "6379:6379"
    volumes:
      - redis_data:/data
    networks:
      - app-network

  node:
    build:
      context: ./docker/node
    volumes:
      - ./src:/var/www/html
    working_dir: /var/www/html
    command: npm run dev

volumes:
  postgres_data:
  redis_data:

networks:
  app-network:
    driver: bridge
```

### Development Override (docker-compose.dev.yml)

```yaml
version: '3.8'

services:
  nginx:
    ports:
      - "8000:80"
    volumes:
      - ./src:/var/www/html:cached
      - ./docker/nginx/nginx.dev.conf:/etc/nginx/nginx.conf

  php:
    volumes:
      - ./src:/var/www/html:cached
      - ./storage/logs/php:/var/log/php
    environment:
      APP_ENV: local
      APP_DEBUG: "true"

  postgres:
    ports:
      - "5432:5432"
    volumes:
      - postgres_data:/var/lib/postgresql/data

  redis:
    ports:
      - "6379:6379"

  node:
    volumes:
      - ./src:/var/www/html:cached
    command: npm run watch
```

### Production Override (docker-compose.prod.yml)

```yaml
version: '3.8'

services:
  nginx:
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./src:/var/www/html:ro
      - ./docker/nginx/nginx.prod.conf:/etc/nginx/nginx.conf
      - ./storage/logs/nginx:/var/log/nginx
      - ./storage/ssl:/etc/ssl/certs:ro
    environment:
      - VIRTUAL_HOST=*.example.com
      - LETSENCRYPT_HOST=*.example.com
      - LETSENCRYPT_EMAIL=admin@example.com

  php:
    volumes:
      - ./src:/var/www/html:ro
      - ./storage/logs/php:/var/log/php
      - ./storage/app:/var/www/html/storage/app
      - ./storage/framework:/var/www/html/storage/framework
      - ./storage/logs:/var/www/html/storage/logs
    environment:
      APP_ENV: production
      APP_DEBUG: "false"

  postgres:
    volumes:
      - postgres_data:/var/lib/postgresql/data
      - ./backups:/backups

  redis:
    volumes:
      - redis_data:/data

volumes:
  postgres_data:
    driver: local
  redis_data:
    driver: local
```

## Container Configurations

### Nginx Configuration

#### Dockerfile
```dockerfile
FROM nginx:alpine

RUN apk update && apk upgrade && apk add --no-cache bash

COPY nginx.conf /etc/nginx/nginx.conf

EXPOSE 80 443

CMD ["nginx", "-g", "daemon off;"]
```

#### nginx.conf (Base)
```nginx
events {
    worker_connections 1024;
}

http {
    include       /etc/nginx/mime.types;
    default_type  application/octet-stream;

    log_format main '$remote_addr - $remote_user [$time_local] "$request" '
                    '$status $body_bytes_sent "$http_referer" '
                    '"$http_user_agent" "$http_x_forwarded_for"';

    access_log /var/log/nginx/access.log main;
    error_log /var/log/nginx/error.log;

    sendfile on;
    tcp_nopush on;
    tcp_nodelay on;
    keepalive_timeout 65;
    types_hash_max_size 2048;

    gzip on;

    upstream php-upstream {
        server php:9000;
    }

    server {
        listen 80;
        server_name _;
        root /var/www/html/public;
        index index.php index.html index.htm;

        location / {
            try_files $uri $uri/ /index.php?$query_string;
        }

        location ~ \.php$ {
            fastcgi_pass php-upstream;
            fastcgi_index index.php;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
            include fastcgi_params;
        }

        location ~ /\.ht {
            deny all;
        }
    }
}
```

### PHP Configuration

#### Dockerfile
```dockerfile
FROM php:8.1-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    supervisor

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Node.js
RUN curl -sL https://deb.nodesource.com/setup_16.x | bash -
RUN apt-get install -y nodejs

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Supervisor
RUN mkdir -p /var/log/supervisor

# Copy configuration files
COPY php.ini /usr/local/etc/php/conf.d/app.ini
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Set working directory
WORKDIR /var/www/html

# Copy existing application directory contents
COPY . /var/www/html

# Install dependencies
RUN composer install --optimize-autoloader --no-dev
RUN npm install
RUN npm run production

# Set permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 9000

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
```

#### php.ini
```ini
upload_max_filesize = 20M
post_max_size = 20M
memory_limit = 512M
max_execution_time = 300
max_input_vars = 3000
```

#### supervisord.conf
```ini
[supervisord]
nodaemon=true
user=root
logfile=/var/log/supervisor/supervisord.log
pidfile=/var/run/supervisord.pid

[program:php-fpm]
command=/usr/local/sbin/php-fpm
autostart=true
autorestart=true
stdout_logfile=/var/log/supervisor/php-fpm.log
stderr_logfile=/var/log/supervisor/php-fpm.error.log

[program:laravel-worker]
command=php /var/www/html/artisan queue:work --sleep=3 --tries=3
process_name=%(program_name)s_%(process_num)02d
numprocs=8
autostart=true
autorestart=true
stdout_logfile=/var/log/supervisor/laravel-worker.log
stderr_logfile=/var/log/supervisor/laravel-worker.error.log
user=www-data
```

### PostgreSQL Configuration

#### Dockerfile
```dockerfile
FROM postgres:13

# Copy initialization scripts
COPY init-scripts/ /docker-entrypoint-initdb.d/

# Set environment variables
ENV POSTGRES_DB=hplinkbilling
ENV POSTGRES_USER=hplink
ENV POSTGRES_PASSWORD=secret

EXPOSE 5432
```

#### Initialization Scripts
```sql
-- init-scripts/01-create-database.sql
CREATE DATABASE hplinkbilling OWNER hplink;
```

### Redis Configuration

#### Dockerfile
```dockerfile
FROM redis:6-alpine

# Copy Redis configuration
COPY redis.conf /usr/local/etc/redis/redis.conf

CMD ["redis-server", "/usr/local/etc/redis/redis.conf"]
```

### Node.js Configuration

#### Dockerfile
```dockerfile
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
```

## Environment Configuration

### .env.example
```env
# Application
APP_NAME="ISP Billing & CRM"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

# Database
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=hplinkbilling
DB_USERNAME=hplink
DB_PASSWORD=secret

# Redis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

# Queue
QUEUE_CONNECTION=redis

# Session
SESSION_DRIVER=redis
SESSION_LIFETIME=120

# Cache
CACHE_DRIVER=redis

# Mail
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@example.com"
MAIL_FROM_NAME="${APP_NAME}"

# MikroTik
MIKROTIK_ENCRYPTION_KEY=base64:your-encryption-key-here

# SMS
SMS_DEFAULT_GATEWAY=primary

# Multi-tenancy
TENANCY_MODE=domain
```

## Volume Management

### Persistent Volumes
1. **postgres_data** - PostgreSQL data persistence
2. **redis_data** - Redis data persistence
3. **app_storage** - Laravel storage directory
4. **ssl_certs** - SSL certificates for HTTPS

### Mount Points
- Application code: `/var/www/html`
- Logs: `/var/log/nginx`, `/var/log/php`, `/var/log/supervisor`
- Storage: `/var/www/html/storage`
- SSL certificates: `/etc/ssl/certs`

## Network Setup

### Custom Network
- **app-network** - Bridge network for container communication
- Isolated from host network
- Secure internal communication

### Port Mapping
- 80:80 (HTTP)
- 443:443 (HTTPS)
- 5432:5432 (PostgreSQL)
- 6379:6379 (Redis)

## Build Process

### Development Workflow
1. Build base images: `docker-compose build`
2. Start services: `docker-compose up -d`
3. Install dependencies: `docker-compose exec php composer install`
4. Run migrations: `docker-compose exec php php artisan migrate`
5. Seed database: `docker-compose exec php php artisan db:seed`

### Production Deployment
1. Build optimized images: `docker-compose -f docker-compose.yml -f docker-compose.prod.yml build`
2. Start services: `docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d`
3. Run migrations: `docker-compose exec php php artisan migrate --force`
4. Optimize application: `docker-compose exec php php artisan optimize:clear && php artisan optimize`

## Deployment Considerations

### Multi-tenancy
- Domain-based routing through Nginx
- SSL certificate management for multiple domains
- Database connection pooling

### Scaling
- Horizontal scaling of PHP workers
- Load balancing with multiple Nginx instances
- Database read replicas

### Monitoring
- Log aggregation with ELK stack
- Application performance monitoring
- Container resource monitoring

### Backup Strategy
- Automated database backups
- Application code versioning
- Configuration backup

### Security
- SSL/TLS encryption
- Secure credential management
- Regular security updates
- Firewall configuration

## CI/CD Integration

### Build Pipeline
1. Code checkout
2. Docker image build
3. Security scanning
4. Testing
5. Image push to registry
6. Deployment

### Testing Environment
- Separate docker-compose for testing
- Isolated database for tests
- Mock services for external dependencies

## Maintenance

### Regular Tasks
- Log rotation
- Database optimization
- Cache clearing
- Security updates

### Monitoring
- Container health checks
- Application performance metrics
- Error rate tracking
- Resource utilization