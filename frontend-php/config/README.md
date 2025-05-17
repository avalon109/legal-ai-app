# Tenant Rights Assistant

A web-based application that helps tenants understand their rights and get answers to common questions about tenant-landlord relationships.

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)

## Installation

1. Clone the repository
```bash
git clone [your-repo-url]
```

2. Configure your environment
- Copy `config/environments/development.example.php` to `config/environments/development.php`
- Update the database credentials in your environment file

3. Set up the database
- Create a new MySQL database
- Import the database schema (if provided)

4. Configure your web server
- Point your web server to the project root directory
- Ensure PHP has write permissions for the `uploads` directory

## Development

- The application uses Bootstrap 5.3 for styling
- Main application logic is in `includes/`
- Configuration files are in `config/`
- Frontend assets are in `assets/`

## Production Deployment

1. Set up production environment file
2. Configure secure database credentials
3. Enable HTTPS
4. Set appropriate file permissions
5. Configure production-grade web server settings

## Security Notes

- Database credentials are stored in environment-specific configuration files
- Session security measures are implemented
- HTTPS is required for production use 