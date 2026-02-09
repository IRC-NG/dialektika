# Dialektika Portal Berita

A PHP-based news portal application with support for articles, multimedia content, simulations, and more.

## Features

- Multi-category news management (Berita, Opini, Pengetahuan, Riset, Musik, Video, Buku)
- Multimedia support (images, videos, audio, documents)
- Admin panel with rich text editor
- User management system
- Site settings configuration
- Simulation catalog management
- Responsive design

## Technology Stack

- **Backend**: PHP 8.2
- **Web Server**: Apache 2.4
- **Database**: MySQL 8.0
- **Containerization**: Docker & Docker Compose
- **Reverse Proxy**: Traefik (external)

## Prerequisites

- Docker Engine 20.10+
- Docker Compose 2.0+
- Traefik reverse proxy (configured with `traefik-network`)
- Domain names configured in DNS

## Quick Start

### 1. Clone the Repository

```bash
git clone <your-repo-url>
cd dialektika
```

### 2. Configure Environment Variables

Copy the example environment file and update with your production values:

```bash
cp .env.example .env
```

Edit `.env` and update the following critical values:

```env
# Update these domains to match your setup
DOMAIN=dialektika.yourdomain.com
PMA_DOMAIN=pma.dialektika.yourdomain.com

# IMPORTANT: Change these passwords!
DB_PASSWORD=your_strong_password_here
DB_ROOT_PASSWORD=your_strong_root_password_here
```

### 3. Deploy with Docker Compose

```bash
# Build and start all services
docker-compose up -d

# Check service status
docker-compose ps

# View logs
docker-compose logs -f
```

### 4. Access the Application

- **Main Application**: https://dialektika.yourdomain.com
- **phpMyAdmin**: https://pma.dialektika.yourdomain.com
- **Admin Panel**: https://dialektika.yourdomain.com/admin.php

### 5. Default Admin Credentials

The database is initialized with a default admin user. Check `portal_berita.sql` for credentials.

**IMPORTANT**: Change the admin password immediately after first login!

## Architecture

### Services

#### 1. Web Service (dialektika-web)
- Built from Dockerfile
- Runs Apache + PHP 8.2
- Handles all web requests
- Mounts application code and uploads volume

#### 2. MySQL Service (dialektika-mysql)
- MySQL 8.0 official image
- Persistent data volume
- Auto-initialized with `portal_berita.sql`
- Health checks enabled

#### 3. phpMyAdmin Service (dialektika-phpmyadmin)
- Latest phpMyAdmin image
- Database management interface
- Accessible via Traefik

### Networks

- **dialektika-network**: Internal bridge network for service communication
- **traefik-network**: External network for Traefik integration (must exist)

### Volumes

- **mysql_data**: Persistent MySQL database storage
- **uploads_data**: Persistent user uploads storage

## Configuration

### Environment Variables

All configuration is managed through environment variables. See `.env.example` for complete documentation.

Key variables:

| Variable | Description | Default |
|----------|-------------|---------|
| `DOMAIN` | Main application domain | `dialektika.local` |
| `PMA_DOMAIN` | phpMyAdmin domain | `pma.dialektika.local` |
| `DB_HOST` | Database host | `mysql` |
| `DB_NAME` | Database name | `portal_berita` |
| `DB_USER` | Database user | `dialektika_user` |
| `DB_PASSWORD` | Database password | `CHANGE_ME` |
| `DB_ROOT_PASSWORD` | MySQL root password | `CHANGE_ROOT_PASSWORD` |
| `UPLOAD_DIR` | Upload directory | `uploads/` |
| `PHP_UPLOAD_MAX_FILESIZE` | Max upload size | `50M` |
| `PHP_MEMORY_LIMIT` | PHP memory limit | `256M` |

### Traefik Configuration

The docker-compose.yml includes Traefik labels for automatic routing:

- SSL/TLS via Let's Encrypt (certresolver: `letsencrypt`)
- HTTP to HTTPS redirect
- Entry points: `web` (80) and `websecure` (443)

Ensure your Traefik instance has:
1. A network named `traefik-network`
2. Entry points configured for `web` and `websecure`
3. Certificate resolver named `letsencrypt`

## Management

### Starting Services

```bash
docker-compose up -d
```

### Stopping Services

```bash
docker-compose down
```

### Restarting Services

```bash
docker-compose restart
```

### Viewing Logs

```bash
# All services
docker-compose logs -f

# Specific service
docker-compose logs -f web
docker-compose logs -f mysql
```

### Rebuilding After Code Changes

```bash
docker-compose up -d --build
```

### Database Backup

```bash
# Backup database
docker-compose exec mysql mysqldump -u root -p portal_berita > backup_$(date +%Y%m%d_%H%M%S).sql

# Restore database
docker-compose exec -T mysql mysql -u root -p portal_berita < backup.sql
```

### Accessing Containers

```bash
# Web container
docker-compose exec web bash

# MySQL container
docker-compose exec mysql bash
```

## File Structure

```
dialektika/
├── admin.php                 # Admin panel
├── config.php                # Database configuration (uses env vars)
├── detail.php                # Article detail page
├── index.php                 # Homepage
├── login.php                 # Admin login
├── upload_image.php          # Image upload handler
├── portal_berita.sql         # Database initialization
├── assets/                   # Static assets (CSS, JS, images)
├── uploads/                  # User uploads (Docker volume)
├── Dockerfile                # Web service container definition
├── docker-compose.yml        # Multi-container orchestration
├── docker-entrypoint.sh      # Container startup script
├── .env.example              # Environment variables template
├── .dockerignore             # Docker build exclusions
└── .gitignore                # Git exclusions
```

## Security Considerations

1. **Environment Variables**: Never commit `.env` file to version control
2. **Passwords**: Change all default passwords before production deployment
3. **Admin Access**: Change default admin credentials immediately
4. **File Permissions**: Upload directory has 777 permissions for Docker - consider more restrictive settings if mounting from host
5. **PHP Settings**: Error display is disabled in production mode
6. **Database**: Non-root user is used for application database access
7. **Traefik**: SSL/TLS certificates are automatically managed via Let's Encrypt

## Troubleshooting

### Database Connection Issues

```bash
# Check if MySQL is healthy
docker-compose ps

# View MySQL logs
docker-compose logs mysql

# Test database connection
docker-compose exec web php -r "new mysqli('mysql', 'dialektika_user', 'your_password', 'portal_berita');"
```

### Upload Issues

```bash
# Check uploads directory permissions
docker-compose exec web ls -la /var/www/html/uploads

# Fix permissions if needed
docker-compose exec web chown -R www-data:www-data /var/www/html/uploads
docker-compose exec web chmod -R 777 /var/www/html/uploads
```

### Traefik Routing Issues

```bash
# Check Traefik logs for routing issues
docker logs traefik

# Verify traefik-network exists
docker network ls | grep traefik

# Create traefik-network if missing
docker network create traefik-network
```

### Service Won't Start

```bash
# Check detailed logs
docker-compose logs -f service_name

# Rebuild without cache
docker-compose build --no-cache
docker-compose up -d
```

## Development

For local development without Traefik:

1. Comment out Traefik labels in `docker-compose.yml`
2. Add port mappings to web and phpmyadmin services:
   ```yaml
   web:
     ports:
       - "8080:80"
   phpmyadmin:
     ports:
       - "8081:80"
   ```
3. Access via http://localhost:8080

## Production Deployment Checklist

- [ ] Copy `.env.example` to `.env`
- [ ] Update `DOMAIN` and `PMA_DOMAIN` with actual domains
- [ ] Change `DB_PASSWORD` to a strong password
- [ ] Change `DB_ROOT_PASSWORD` to a strong password
- [ ] Ensure Traefik reverse proxy is running
- [ ] Verify `traefik-network` exists
- [ ] Configure DNS records for your domains
- [ ] Run `docker-compose up -d`
- [ ] Access admin panel and change default password
- [ ] Test file uploads functionality
- [ ] Set up automated database backups
- [ ] Configure monitoring and logging

## License

[Your License Here]

## Support

For issues and questions, please open an issue in the repository.
