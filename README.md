# Property Management System

## Project Overview
This is a comprehensive property management application built with PHP that handles both immovable and movable property records, including PDF generation capabilities.

## mPDF Requirements

### About mPDF
mPDF is a PHP library that generates PDF files from UTF-8 encoded HTML. It is used in this project for generating PDF reports for immovable and movable properties.

### Installation
mPDF has been installed via Composer. To install or update mPDF dependencies, run:

```bash
composer require mpdf/mpdf
```

### System Requirements for mPDF

#### PHP Version
- **Minimum PHP version:** 5.6.0
- **Recommended:** PHP 7.0 or higher

#### Required PHP Extensions
- `mbstring` - for multibyte string handling
- `gd` - for image processing (optional but recommended)
- `curl` - for remote file access (optional)

#### File System Requirements
- Write permissions in the `/tmp` directory or configured temp folder
- Sufficient disk space for temporary PDF files

### Composer Dependencies
mPDF requires the following packages (automatically installed):

```
- mpdf/mpdf - Main mPDF library
- setasign/fpdi - PDF manipulation library
- psr/log - PSR-3 logger interface
- paragonie/random_compat - Random bytes generation (PHP < 7.0)
- myclabs/deep-copy - Deep copying objects
```

View the complete dependency tree:
```bash
composer show mpdf/mpdf
```

## Project Structure

### Key Directories
- **api/** - Backend API endpoints for data operations
  - `generate_pdf_immovable.php` - PDF generation for immovable properties
  - `generate_pdf_movable.php` - PDF generation for movable properties
  - Other CRUD operations and session management

- **pages/** - Frontend pages and templates
  - `immovableForm.php` - Form for immovable property data
  - `movableForm.php` - Form for movable property data
  - Property viewing and management pages

- **js/** - JavaScript files for client-side functionality
  - `session-manager.js` - Session management
  - `immovable.js` & `movable.js` - Property-specific logic

- **connection/** - Database and session connection files
- **plugins/** - Third-party libraries and plugins
- **vendor/** - Composer dependencies (including mPDF)

## Database Requirements
- MySQL/MariaDB database
- PDO extension for PHP
- Proper connection configuration in `connection/db.php`

## Installation & Setup

1. **Clone/Download the project**
   ```bash
   cd c:\xampp\htdocs\property
   ```

2. **Install Composer Dependencies**
   ```bash
   composer install
   ```

3. **Configure Database**
   - Update `connection/db.php` with your database credentials

4. **Configure Session**
   - Ensure `connection/session_check.php` is properly configured

5. **Set File Permissions**
   - Ensure write permissions for temp directories needed by mPDF

## Usage

### Generating PDFs
The application uses mPDF to generate PDF reports:

- **Immovable Properties:** `api/generate_pdf_immovable.php`
- **Movable Properties:** `api/generate_pdf_movable.php`

### Example Usage in Code
```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

$mpdf = new \Mpdf\Mpdf();
$mpdf->WriteHTML('<h1>Property Report</h1>');
$mpdf->Output('property_report.pdf', 'D'); // Download the PDF
?>
```

## Development Tools

### Build Tools
- **Grunt** - Task automation (Gruntfile.js)
- **Less/SCSS** - CSS preprocessing (build/less/ and build/scss/)

### Package Management
- **Composer** - PHP dependency management
- **npm/Bower** - JavaScript and frontend dependency management

## Security Considerations

- Validate all user inputs before PDF generation
- Use parameterized queries for database operations
- Implement proper session management
- Verify user permissions before generating or viewing PDFs
- Sanitize HTML content before passing to mPDF

## Troubleshooting

### mPDF Issues

**Issue:** "Class 'Mpdf\Mpdf' not found"
- **Solution:** Run `composer install` to install dependencies

**Issue:** "Unable to write temp files"
- **Solution:** Ensure write permissions for temp directory and sufficient disk space

**Issue:** Image not displaying in PDF
- **Solution:** Use absolute paths for images, verify the image file exists and is accessible

**Issue:** UTF-8 characters not rendering correctly
- **Solution:** Ensure HTML is properly encoded as UTF-8 and appropriate fonts are configured in mPDF

## Support & Documentation

- **mPDF Documentation:** https://mpdf.github.io/
- **PHP Official Docs:** https://www.php.net/manual/

## License
See LICENSE file in the root directory.
