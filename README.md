# Taskboard - Kanban Task Management

A modern Laravel kanban board application with drag-and-drop functionality, built for managing tasks between Sandi and Alex.

## Features

- 🔥 **Modern Kanban Board** with drag-and-drop (SortableJS)
- 📝 **Rich Task Management** - title, description (markdown), priority, assignee, due dates, tags
- 🎨 **Beautiful UI** - Tailwind CSS with dark/light mode toggle
- 🔐 **Simple Password Auth** - no user registration needed
- 🚀 **REST API** - for programmatic access (AI integration)
- 📱 **Mobile Responsive** - works great on all devices
- ⚡ **Real-time Updates** - Alpine.js for smooth interactions

## Stack

- **Backend:** Laravel 11 + MySQL
- **Frontend:** Tailwind CSS v4 + Alpine.js 3
- **Drag & Drop:** SortableJS
- **Markdown:** Marked.js for description rendering

## Installation

### Requirements

- PHP 8.2+
- MySQL 8.0+
- Node.js 18+
- Composer

### Local Development

1. **Clone the repository:**
   ```bash
   git clone <repository-url>
   cd taskboard
   ```

2. **Install dependencies:**
   ```bash
   composer install
   npm install
   ```

3. **Environment setup:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure `.env` file:**
   ```env
   APP_NAME=Taskboard
   APP_URL=http://localhost:8000
   APP_PASSWORD=your-secure-password
   API_TOKEN=your-api-token-here
   
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=taskboard
   DB_USERNAME=root
   DB_PASSWORD=your-mysql-password
   ```

5. **Database setup:**
   ```bash
   # Create database
   mysql -u root -p -e "CREATE DATABASE taskboard;"
   
   # Run migrations
   php artisan migrate
   ```

6. **Build assets:**
   ```bash
   npm run build
   ```

7. **Start development server:**
   ```bash
   php artisan serve
   ```

   Visit `http://localhost:8000` and login with your APP_PASSWORD.

## cPanel Deployment

### Step 1: File Upload

1. **Build production assets locally:**
   ```bash
   npm run build
   ```

2. **Create a ZIP of your project:**
   ```bash
   zip -r taskboard.zip . -x "node_modules/*" ".git/*" "tests/*"
   ```

3. **Upload via cPanel File Manager:**
   - Upload `taskboard.zip` to your domain's root or subdirectory
   - Extract the ZIP file
   - Move Laravel's `public` folder contents to your public_html directory
   - Move everything else to a directory outside public_html (e.g., `laravel_app`)

### Step 2: cPanel Configuration

1. **Database Setup:**
   - Create MySQL database via cPanel
   - Create database user with all privileges
   - Note the database name, username, and password

2. **Environment Configuration:**
   - Copy `.env.example` to `.env`
   - Update database credentials:
     ```env
     DB_CONNECTION=mysql
     DB_HOST=localhost
     DB_DATABASE=yourdomain_taskboard
     DB_USERNAME=yourdomain_user
     DB_PASSWORD=your-database-password
     ```
   - Set your app password and API token:
     ```env
     APP_PASSWORD=your-secure-password
     API_TOKEN=your-secure-api-token
     ```

3. **Update index.php:**
   Edit `public_html/index.php` to point to your Laravel app:
   ```php
   require __DIR__.'/../laravel_app/vendor/autoload.php';
   $app = require_once __DIR__.'/../laravel_app/bootstrap/app.php';
   ```

### Step 3: Final Steps

1. **Run migrations via terminal (if available):**
   ```bash
   cd laravel_app
   php artisan migrate
   ```
   
   If terminal access isn't available, you can create a temporary migration script.

2. **Set permissions:**
   - Storage folder: 755
   - Bootstrap/cache folder: 755

3. **Test the installation:**
   - Visit your domain
   - You should see the login page
   - Login with your APP_PASSWORD

## API Usage

The application provides a REST API for programmatic access (perfect for AI integration).

### Authentication

Include the API token in the Authorization header:
```bash
Authorization: Bearer your-api-token-here
```

### Endpoints

- **GET** `/api/tasks` - List all tasks
  - Query params: `status`, `priority`, `assigned_to`
- **POST** `/api/tasks` - Create task
- **GET** `/api/tasks/{id}` - Get specific task
- **PUT** `/api/tasks/{id}` - Update task
- **DELETE** `/api/tasks/{id}` - Delete task (soft delete)
- **POST** `/api/tasks/positions` - Update task positions for drag & drop

### Example API Calls

**Create a task:**
```bash
curl -X POST https://yourdomain.com/api/tasks \
  -H "Authorization: Bearer your-api-token" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Fix bug in login",
    "description": "User reported login issues on mobile devices",
    "status": "todo",
    "priority": "high",
    "assigned_to": "alex",
    "due_date": "2024-02-15",
    "tags": ["bug", "mobile", "urgent"]
  }'
```

**Get tasks by status:**
```bash
curl -X GET "https://yourdomain.com/api/tasks?status=in_progress" \
  -H "Authorization: Bearer your-api-token"
```

## Task Management

### Task Properties

- **Title:** Required, max 255 characters
- **Description:** Optional, supports markdown
- **Status:** `backlog`, `todo`, `in_progress`, `done`
- **Priority:** `low` 🟢, `medium` 🟡, `high` 🔴
- **Assigned To:** `sandi` 👩‍💼, `alex` 🤖, or unassigned
- **Due Date:** Optional date
- **Tags:** Array of string labels
- **Position:** Auto-managed for drag & drop ordering

### Kanban Columns

1. **📝 Backlog** - Ideas and future tasks
2. **🔵 To Do** - Ready to work on
3. **🟡 In Progress** - Currently being worked on
4. **✅ Done** - Completed tasks

## Authentication

- **Web Interface:** Single password authentication via `APP_PASSWORD`
- **API Access:** Bearer token authentication via `API_TOKEN`
- **Session-based:** Web login persists across browser sessions

## Security

- CSRF protection on all web forms
- SQL injection protection via Eloquent ORM
- Bearer token validation for API access
- Soft deletes for data recovery
- Input validation and sanitization

## Customization

### Adding New Users

To add new assignees beyond Sandi and Alex:

1. Update the `assigned_to` enum in the migration
2. Update validation rules in controllers
3. Update the frontend dropdown options
4. Add new avatar colors/initials in the Task model

### Theming

The app uses Tailwind CSS v4. To customize:

1. Edit `resources/css/app.css` for custom styles
2. Update the color scheme in task cards and UI components
3. Modify dark mode colors by updating CSS classes

## Troubleshooting

### Common Issues

1. **Database connection errors:**
   - Check `.env` database credentials
   - Ensure MySQL service is running
   - Verify database exists

2. **CSS/JS not loading:**
   - Run `npm run build` to compile assets
   - Check file permissions on public directory

3. **Login not working:**
   - Verify `APP_PASSWORD` in `.env`
   - Clear browser cache and cookies

4. **API authentication failing:**
   - Check `API_TOKEN` in `.env`
   - Ensure Authorization header format: `Bearer your-token`

### Logs

- Application logs: `storage/logs/laravel.log`
- Web server logs: Check cPanel error logs

## Development

### Adding Features

1. **New migration:** `php artisan make:migration`
2. **New model:** `php artisan make:model`
3. **New controller:** `php artisan make:controller`
4. **Frontend changes:** Edit resources/views and resources/js

### Testing

```bash
# Run PHP tests
php artisan test

# Run JavaScript linting
npm run lint

# Build for development
npm run dev
```

## License

This project is open-source software licensed under the [MIT license](LICENSE).

## Support

For issues and questions, please check the troubleshooting section above or review the application logs.

---

**Built with ❤️ for efficient task management between Sandi and Alex.**