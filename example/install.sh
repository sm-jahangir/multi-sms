#!/bin/bash

# Multi-SMS Package Frontend Installation Script
# এই script সব files automatically copy করবে এবং setup complete করবে

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Function to check if we're in a Laravel project
check_laravel_project() {
    if [ ! -f "artisan" ] || [ ! -f "composer.json" ]; then
        print_error "This doesn't appear to be a Laravel project directory."
        print_error "Please run this script from your Laravel project root."
        exit 1
    fi
    print_success "Laravel project detected."
}

# Function to backup existing files
backup_existing_files() {
    print_status "Creating backup of existing files..."
    
    BACKUP_DIR="backup_$(date +%Y%m%d_%H%M%S)"
    mkdir -p "$BACKUP_DIR"
    
    # Backup routes if exists
    if [ -f "routes/sms.php" ]; then
        cp "routes/sms.php" "$BACKUP_DIR/sms.php.bak"
        print_warning "Existing routes/sms.php backed up to $BACKUP_DIR/"
    fi
    
    # Backup controllers if exist
    if [ -f "app/Http/Controllers/SmsController.php" ]; then
        cp "app/Http/Controllers/SmsController.php" "$BACKUP_DIR/SmsController.php.bak"
        print_warning "Existing SmsController.php backed up to $BACKUP_DIR/"
    fi
    
    if [ -f "app/Http/Controllers/SmsApiController.php" ]; then
        cp "app/Http/Controllers/SmsApiController.php" "$BACKUP_DIR/SmsApiController.php.bak"
        print_warning "Existing SmsApiController.php backed up to $BACKUP_DIR/"
    fi
    
    # Backup middleware if exists
    if [ -f "app/Http/Middleware/SmsMiddleware.php" ]; then
        cp "app/Http/Middleware/SmsMiddleware.php" "$BACKUP_DIR/SmsMiddleware.php.bak"
        print_warning "Existing SmsMiddleware.php backed up to $BACKUP_DIR/"
    fi
    
    print_success "Backup completed in $BACKUP_DIR/"
}

# Function to copy views
copy_views() {
    print_status "Copying view files..."
    
    # Create directories if they don't exist
    mkdir -p "resources/views/layouts"
    mkdir -p "resources/views/sms/templates"
    mkdir -p "resources/views/sms/campaigns"
    mkdir -p "resources/views/sms/autoresponders"
    mkdir -p "resources/views/sms/analytics"
    
    # Get the script directory
    SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
    
    # Copy layout
    if [ -f "$SCRIPT_DIR/views/layouts/app.blade.php" ]; then
        cp "$SCRIPT_DIR/views/layouts/app.blade.php" "resources/views/layouts/"
        print_success "Layout file copied."
    else
        print_warning "Layout file not found, skipping."
    fi
    
    # Copy SMS views
    if [ -f "$SCRIPT_DIR/views/sms/dashboard.blade.php" ]; then
        cp "$SCRIPT_DIR/views/sms/dashboard.blade.php" "resources/views/sms/"
        print_success "Dashboard view copied."
    fi
    
    # Copy template views
    if [ -f "$SCRIPT_DIR/views/sms/templates/index.blade.php" ]; then
        cp "$SCRIPT_DIR/views/sms/templates/index.blade.php" "resources/views/sms/templates/"
        print_success "Templates index view copied."
    fi
    
    if [ -f "$SCRIPT_DIR/views/sms/templates/create.blade.php" ]; then
        cp "$SCRIPT_DIR/views/sms/templates/create.blade.php" "resources/views/sms/templates/"
        print_success "Templates create view copied."
    fi
    
    # Copy campaign views
    if [ -f "$SCRIPT_DIR/views/sms/campaigns/index.blade.php" ]; then
        cp "$SCRIPT_DIR/views/sms/campaigns/index.blade.php" "resources/views/sms/campaigns/"
        print_success "Campaigns index view copied."
    fi
    
    if [ -f "$SCRIPT_DIR/views/sms/campaigns/create.blade.php" ]; then
        cp "$SCRIPT_DIR/views/sms/campaigns/create.blade.php" "resources/views/sms/campaigns/"
        print_success "Campaigns create view copied."
    fi
    
    # Copy autoresponder views
    if [ -f "$SCRIPT_DIR/views/sms/autoresponders/index.blade.php" ]; then
        cp "$SCRIPT_DIR/views/sms/autoresponders/index.blade.php" "resources/views/sms/autoresponders/"
        print_success "Autoresponders index view copied."
    fi
    
    if [ -f "$SCRIPT_DIR/views/sms/autoresponders/create.blade.php" ]; then
        cp "$SCRIPT_DIR/views/sms/autoresponders/create.blade.php" "resources/views/sms/autoresponders/"
        print_success "Autoresponders create view copied."
    fi
    
    # Copy analytics views
    if [ -f "$SCRIPT_DIR/views/sms/analytics/index.blade.php" ]; then
        cp "$SCRIPT_DIR/views/sms/analytics/index.blade.php" "resources/views/sms/analytics/"
        print_success "Analytics view copied."
    fi
    
    print_success "All view files copied successfully."
}

# Function to copy routes
copy_routes() {
    print_status "Copying route files..."
    
    SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
    
    if [ -f "$SCRIPT_DIR/routes/web.php" ]; then
        cp "$SCRIPT_DIR/routes/web.php" "routes/sms.php"
        print_success "SMS routes copied to routes/sms.php"
    else
        print_error "Route file not found!"
        exit 1
    fi
}

# Function to copy controllers
copy_controllers() {
    print_status "Copying controller files..."
    
    # Create directory if it doesn't exist
    mkdir -p "app/Http/Controllers"
    
    SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
    
    # Copy controllers
    if [ -f "$SCRIPT_DIR/controllers/SmsController.php" ]; then
        cp "$SCRIPT_DIR/controllers/SmsController.php" "app/Http/Controllers/"
        print_success "SmsController copied."
    fi
    
    if [ -f "$SCRIPT_DIR/controllers/SmsApiController.php" ]; then
        cp "$SCRIPT_DIR/controllers/SmsApiController.php" "app/Http/Controllers/"
        print_success "SmsApiController copied."
    fi
    
    print_success "All controller files copied successfully."
}

# Function to copy middleware
copy_middleware() {
    print_status "Copying middleware files..."
    
    # Create directory if it doesn't exist
    mkdir -p "app/Http/Middleware"
    
    SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
    
    if [ -f "$SCRIPT_DIR/controllers/SmsMiddleware.php" ]; then
        cp "$SCRIPT_DIR/controllers/SmsMiddleware.php" "app/Http/Middleware/"
        print_success "SmsMiddleware copied."
    fi
}

# Function to copy assets
copy_assets() {
    print_status "Copying asset files..."
    
    # Create directories if they don't exist
    mkdir -p "public/assets/css"
    mkdir -p "public/assets/js"
    
    SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
    
    # Copy CSS
    if [ -f "$SCRIPT_DIR/assets/css/sms-dashboard.css" ]; then
        cp "$SCRIPT_DIR/assets/css/sms-dashboard.css" "public/assets/css/"
        print_success "CSS file copied."
    fi
    
    # Copy JS
    if [ -f "$SCRIPT_DIR/assets/js/sms-dashboard.js" ]; then
        cp "$SCRIPT_DIR/assets/js/sms-dashboard.js" "public/assets/js/"
        print_success "JavaScript file copied."
    fi
    
    print_success "All asset files copied successfully."
}

# Function to update web.php
update_web_routes() {
    print_status "Updating main routes file..."
    
    # Check if SMS routes are already included
    if grep -q "sms.php" "routes/web.php"; then
        print_warning "SMS routes already included in routes/web.php"
    else
        echo "" >> "routes/web.php"
        echo "// SMS Package Routes" >> "routes/web.php"
        echo "require __DIR__.'/sms.php';" >> "routes/web.php"
        print_success "SMS routes included in routes/web.php"
    fi
}

# Function to update Kernel.php
update_kernel() {
    print_status "Updating Kernel.php for middleware registration..."
    
    KERNEL_FILE="app/Http/Kernel.php"
    
    if [ ! -f "$KERNEL_FILE" ]; then
        print_error "Kernel.php not found!"
        return 1
    fi
    
    # Check if middleware is already registered
    if grep -q "'sms'" "$KERNEL_FILE"; then
        print_warning "SMS middleware already registered in Kernel.php"
    else
        # Create a backup
        cp "$KERNEL_FILE" "${KERNEL_FILE}.bak"
        
        # Add middleware registration
        sed -i.tmp "/protected \$routeMiddleware = \[/a\\
        // SMS Package Middleware\\
        'sms' => \\App\\Http\\Middleware\\SmsMiddleware::class," "$KERNEL_FILE"
        
        # Remove temporary file
        rm "${KERNEL_FILE}.tmp" 2>/dev/null || true
        
        print_success "SMS middleware registered in Kernel.php"
        print_warning "Backup created: ${KERNEL_FILE}.bak"
    fi
}

# Function to create sample .env entries
create_env_sample() {
    print_status "Creating sample environment configuration..."
    
    ENV_SAMPLE=".env.sms.example"
    
    cat > "$ENV_SAMPLE" << 'EOF'
# SMS Package Configuration
# Copy these to your .env file and update with your credentials

# Default SMS Driver
SMS_DEFAULT_DRIVER=log

# Twilio Configuration
TWILIO_SID=your_twilio_sid
TWILIO_TOKEN=your_twilio_token
TWILIO_FROM=your_twilio_phone_number

# Nexmo/Vonage Configuration
NEXMO_KEY=your_nexmo_key
NEXMO_SECRET=your_nexmo_secret
NEXMO_FROM=your_nexmo_from_number

# TextLocal Configuration
TEXTLOCAL_API_KEY=your_textlocal_api_key
TEXTLOCAL_FROM=your_textlocal_sender

# Clickatell Configuration
CLICKATELL_API_KEY=your_clickatell_api_key

# SMS Rate Limiting
SMS_RATE_LIMIT_PER_MINUTE=10
SMS_RATE_LIMIT_PER_HOUR=100
SMS_RATE_LIMIT_PER_DAY=1000

# SMS Queue Configuration
SMS_QUEUE_CONNECTION=database
SMS_QUEUE_NAME=sms

# SMS Logging
SMS_LOG_CHANNEL=daily
SMS_LOG_LEVEL=info
EOF

    print_success "Sample environment configuration created: $ENV_SAMPLE"
    print_warning "Please copy the relevant settings to your .env file"
}

# Function to run composer and npm commands
run_dependencies() {
    print_status "Installing dependencies..."
    
    # Check if composer.json has multi-sms package
    if grep -q "multi-sms" "composer.json"; then
        print_success "Multi-SMS package already in composer.json"
    else
        print_warning "Multi-SMS package not found in composer.json"
        print_warning "Please run: composer require your-vendor/multi-sms"
    fi
    
    # Run composer install
    if command -v composer &> /dev/null; then
        print_status "Running composer install..."
        composer install --no-dev --optimize-autoloader
        print_success "Composer dependencies installed."
    else
        print_warning "Composer not found. Please install dependencies manually."
    fi
    
    # Run npm install if package.json exists
    if [ -f "package.json" ] && command -v npm &> /dev/null; then
        print_status "Running npm install..."
        npm install
        print_success "NPM dependencies installed."
    fi
}

# Function to run Laravel commands
run_laravel_commands() {
    print_status "Running Laravel commands..."
    
    # Clear caches
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    
    # Cache configurations
    php artisan config:cache
    php artisan route:cache
    
    print_success "Laravel caches cleared and rebuilt."
}

# Function to create database tables (if migrations exist)
run_migrations() {
    print_status "Checking for SMS migrations..."
    
    if ls database/migrations/*sms* 1> /dev/null 2>&1; then
        print_status "SMS migrations found. Running migrations..."
        php artisan migrate
        print_success "Migrations completed."
    else
        print_warning "No SMS migrations found. You may need to publish them first."
        print_warning "Run: php artisan vendor:publish --provider=\"YourVendor\\MultiSms\\MultiSmsServiceProvider\""
    fi
}

# Function to set permissions
set_permissions() {
    print_status "Setting file permissions..."
    
    # Set proper permissions for storage and cache
    chmod -R 775 storage
    chmod -R 775 bootstrap/cache
    
    # Set permissions for asset files
    chmod -R 644 public/assets
    
    print_success "File permissions set."
}

# Function to display final instructions
show_final_instructions() {
    echo ""
    echo "======================================"
    echo -e "${GREEN}Installation Completed Successfully!${NC}"
    echo "======================================"
    echo ""
    echo "Next Steps:"
    echo ""
    echo "1. Update your .env file with SMS provider credentials:"
    echo "   - Copy settings from .env.sms.example"
    echo ""
    echo "2. Publish SMS package migrations (if not done already):"
    echo "   php artisan vendor:publish --provider=\"YourVendor\\MultiSms\\MultiSmsServiceProvider\""
    echo ""
    echo "3. Run migrations:"
    echo "   php artisan migrate"
    echo ""
    echo "4. Seed sample data (optional):"
    echo "   php artisan db:seed --class=SmsSeeder"
    echo ""
    echo "5. Access the SMS dashboard:"
    echo "   http://your-domain/sms/dashboard"
    echo ""
    echo "6. Test SMS functionality:"
    echo "   - Go to SMS Dashboard"
    echo "   - Click 'Send Test SMS'"
    echo "   - Use 'log' driver for testing"
    echo ""
    echo "Files installed:"
    echo "- Views: resources/views/sms/"
    echo "- Controllers: app/Http/Controllers/Sms*"
    echo "- Middleware: app/Http/Middleware/SmsMiddleware.php"
    echo "- Routes: routes/sms.php"
    echo "- Assets: public/assets/css/ & public/assets/js/"
    echo ""
    echo "Documentation: Check README.md for detailed usage instructions"
    echo ""
    echo -e "${GREEN}Happy SMS sending! 🚀${NC}"
    echo ""
}

# Main installation function
main() {
    echo "======================================"
    echo "Multi-SMS Package Frontend Installer"
    echo "======================================"
    echo ""
    
    # Check if we're in a Laravel project
    check_laravel_project
    
    # Ask for confirmation
    echo "This will install SMS frontend files to your Laravel project."
    read -p "Do you want to continue? (y/N): " -n 1 -r
    echo ""
    
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        print_warning "Installation cancelled."
        exit 0
    fi
    
    # Create backup
    backup_existing_files
    
    # Copy files
    copy_views
    copy_routes
    copy_controllers
    copy_middleware
    copy_assets
    
    # Update configuration files
    update_web_routes
    update_kernel
    
    # Create sample environment file
    create_env_sample
    
    # Install dependencies
    run_dependencies
    
    # Run Laravel commands
    run_laravel_commands
    
    # Run migrations
    run_migrations
    
    # Set permissions
    set_permissions
    
    # Show final instructions
    show_final_instructions
}

# Run the main function
main "$@"