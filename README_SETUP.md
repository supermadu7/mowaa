# Travel Request System - Setup Instructions

This document provides step-by-step instructions to set up the Travel Request System with file upload processing and database storage.

## 📋 Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- GD extension for image processing
- Ghostscript (optional, for PDF compression)

## 🚀 Installation Steps

### 1. Database Setup

1. Create a new MySQL database:
   ```sql
   CREATE DATABASE mowaa_db;
   ```

2. Import the database schema:
   ```bash
   mysql -u root -p mowaa_db < database/travel_request_tables.sql
   ```

   Or run the SQL commands manually in phpMyAdmin/MySQL Workbench.

### 2. Directory Permissions

Create and set permissions for upload directories:

```bash
# Create directories
mkdir -p uploads/travel-requests
mkdir -p logs
mkdir -p backups

# Set permissions (Linux/Mac)
chmod 755 uploads/travel-requests
chmod 755 logs
chmod 755 backups

# For Windows, ensure the web server has write access to these folders
```

### 3. Configuration

1. Edit `config/config.php` and update database credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   define('DB_NAME', 'mowaa_db');
   ```

2. Update other settings as needed (file sizes, email configuration, etc.)

### 4. PHP Extensions

Ensure these PHP extensions are enabled:
- `gd` (for image processing)
- `pdo_mysql` (for database)
- `fileinfo` (for file type detection)
- `exif` (for image metadata)

Check with:
```bash
php -m | grep -E "(gd|pdo_mysql|fileinfo|exif)"
```

### 5. Optional: Ghostscript for PDF Compression

Install Ghostscript for better PDF compression:

**Ubuntu/Debian:**
```bash
sudo apt-get install ghostscript
```

**Windows:**
Download from: https://www.ghostscript.com/download/gsdnld.html

**macOS:**
```bash
brew install ghostscript
```

### 6. Web Server Configuration

Ensure your web server can handle file uploads. Update `php.ini`:

```ini
upload_max_filesize = 10M
post_max_size = 15M
max_file_uploads = 20
memory_limit = 256M
max_execution_time = 300
```

Restart your web server after changes.

## 🔧 File Structure

```
mowaa/
├── app/
│   ├── travel-request.php          # Form page
│   ├── process-travel-request.php  # Form processor
│   └── assets/                     # CSS, JS, images
├── config/
│   └── config.php                  # Configuration settings
├── database/
│   └── travel_request_tables.sql   # Database schema
├── uploads/
│   └── travel-requests/            # Uploaded files
├── logs/
│   └── travel-request.log          # Application logs
└── backups/                        # Database backups
```

## 📝 Features

### ✅ Implemented Features

1. **Form Validation**
   - Client-side validation with Bootstrap
   - Server-side validation for all fields
   - Email format validation
   - Future date validation for travel dates

2. **File Upload & Processing**
   - Automatic file compression to ~1MB
   - Support for PDF, JPG, JPEG, PNG, GIF files
   - Image resizing and quality optimization
   - PDF compression (with Ghostscript)
   - Unique file naming to prevent conflicts

3. **Database Storage**
   - Complete form data storage
   - File metadata tracking
   - Request status management
   - Approval workflow support

4. **User Experience**
   - Success/error pages with clear messages
   - Request ID generation for tracking
   - Responsive design
   - Progress feedback

5. **Security**
   - File type validation
   - Input sanitization
   - SQL injection prevention
   - XSS protection

### 🔄 Workflow

1. User fills out the travel request form
2. Form validates required fields
3. User confirms submission via modal
4. Files are uploaded and compressed
5. Data is stored in database
6. User receives confirmation with Request ID
7. Email notifications sent (if configured)

## 🛠️ Customization

### Adding New File Types

Edit `config/config.php`:
```php
define('ALLOWED_FILE_TYPES', ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'docx']);
```

### Changing File Size Limits

Edit `config/config.php`:
```php
define('MAX_FILE_SIZE', 2 * 1024 * 1024); // 2MB
```

### Adding Email Notifications

Update SMTP settings in `config/config.php` and implement email sending in the processor.

## 🧪 Testing

1. Test with different file types and sizes
2. Verify database entries are created correctly
3. Check file compression is working
4. Test form validation (both client and server-side)
5. Ensure error handling works properly

## 🐛 Troubleshooting

### Common Issues

1. **File upload fails**
   - Check directory permissions
   - Verify PHP upload settings
   - Check available disk space

2. **Database connection fails**
   - Verify credentials in config.php
   - Ensure MySQL service is running
   - Check database exists

3. **Images not compressing**
   - Ensure GD extension is installed
   - Check PHP memory limit
   - Verify file permissions

4. **PDF compression not working**
   - Install Ghostscript
   - Check shell_exec is not disabled
   - Verify Ghostscript is in PATH

### Log Files

Check `logs/travel-request.log` for detailed error messages and debugging information.

## 🔒 Security Considerations

1. **File Upload Security**
   - Validate file types and sizes
   - Store files outside web root if possible
   - Scan files for malware (implement antivirus)

2. **Database Security**
   - Use prepared statements (already implemented)
   - Regular database backups
   - Restrict database user permissions

3. **Application Security**
   - Keep PHP and extensions updated
   - Use HTTPS in production
   - Implement proper session management

## 📈 Production Deployment

1. **Environment Configuration**
   - Set `error_reporting(0)` in production
   - Use environment variables for sensitive data
   - Enable HTTPS

2. **Performance Optimization**
   - Enable opcache
   - Use CDN for static assets
   - Implement caching where appropriate

3. **Monitoring**
   - Set up log rotation
   - Monitor disk space
   - Track upload failures

## 📧 Support

For issues or questions:
1. Check the log files first
2. Verify configuration settings
3. Test with minimal data
4. Contact system administrator

## 🔄 Updates

To update the system:
1. Backup database and files
2. Test changes in development
3. Deploy during maintenance window
4. Verify functionality post-deployment
