# Education Platform

A lightweight, self-hosted education platform built with PHP and MySQL. It provides a simple environment for managing courses, lessons, and exercises — designed to be easy to deploy on any LAMP/LEMP stack.

---

## Features

- User authentication (login / session management)
- Course and lesson browsing
- MySQL-backed data layer with schema and seed data
- Custom CSS styling and JavaScript interactions
- Modular source structure (config, auth, pages)

---

## Tech Stack

| Layer      | Technology              |
|------------|-------------------------|
| Backend    | PHP 8+                  |
| Database   | MySQL / MariaDB         |
| Frontend   | HTML, CSS, JavaScript   |
| Server     | Apache / Nginx (LAMP)   |

---

## Quick Start

### Prerequisites

- A LAMP or LEMP stack (Linux, Apache/Nginx, PHP 8+, MySQL/MariaDB)

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/bahasalah255/education-platforme.git
   cd education-platforme
   ```

2. **Configure your web server**

   Point your virtual host (or web root) to the `public/` directory.

   Example for Apache:
   ```apache
   DocumentRoot /var/www/education-platforme/public
   ```

3. **Set up the database**
   ```bash
   mysql -u root -p
   CREATE DATABASE education_platform;
   exit

   mysql -u root -p education_platform < sql/schema.sql
   mysql -u root -p education_platform < sql/seed.sql
   ```

4. **Configure the application**

   Edit `src/config.php` with your database credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'education_platform');
   define('DB_USER', 'your_user');
   define('DB_PASS', 'your_password');
   ```

5. **Open in your browser**

   Navigate to your configured URL or `http://localhost`.

   > Default admin credentials (seed data): username `admin`, password `password`.
   > Change these immediately in a production environment.

---

## Project Structure

```
education-platforme/
├── public/             # Web entry points (index, login, lesson pages)
├── src/
│   ├── config.php      # Database connection configuration
│   └── auth.php        # Authentication helpers
├── sql/
│   ├── schema.sql      # Database schema
│   └── seed.sql        # Sample/seed data
├── assets/             # CSS stylesheets and JavaScript files
├── scripts/            # Utility scripts
└── var/logs/           # Application logs
```

---

## Roadmap

- Teacher admin UI to create and manage modules and exercises
- Student progress tracking and dashboards
- Role-based access control (admin / teacher / student)
- Improved styling and accessibility (WCAG compliance)
- REST API for potential mobile client integration

---

## Contributing

Contributions are welcome. Feel free to open an issue or submit a pull request.

1. Fork the project
2. Create your feature branch (`git checkout -b feature/my-feature`)
3. Commit your changes (`git commit -m 'Add my feature'`)
4. Push to the branch (`git push origin feature/my-feature`)
5. Open a Pull Request

---

## License

This project is open source. See the repository for more details.

---

Built as part of a group project (Groupe 7) — ATutor-inspired lightweight education platform.
