# NooRly - Backend API

[![Laravel](https://img.shields.io/badge/Laravel-12.0-FF2D20?style=for-the-badge&logo=laravel)](https://laravel.com)
[![Filament](https://img.shields.io/badge/Filament-3.2-FFA500?style=for-the-badge&logo=filament)](https://filamentphp.com)
[![License](https://img.shields.io/badge/License-MIT-blue.svg?style=for-the-badge)](LICENSE)

NooRly is the backend engine powering the **New Muslim Path** platform. It provides a robust API and a comprehensive administration panel for managing educational content, daily tasks, and spiritual guidance for new Muslims.

## 🚀 Features

- **Multilingual Support**: Built-in support for multiple languages with a focus on English and Arabic.
- **Educational Modules**: Manage lessons, content, and progress tracking.
- **Daily Tasks**: Scheduled spiritual and practical tasks for users.
- **Islamic Resources**: Integrated Duas (supplications) and FAQs.
- **Secure Authentication**: Social login (Google, Facebook, Apple) and guest access via Laravel Sanctum.
- **Admin Panel**: Powerful administration dashboard powered by Filament v3.

## 🛠 Tech Stack

- **Framework**: [Laravel 12](https://laravel.com)
- **Admin Dashboard**: [Filament v3](https://filamentphp.com)
- **Authentication**: [Sanctum](https://laravel.com/docs/sanctum) & [Socialite](https://laravel.com/docs/socialite)
- **Database**: MySQL / SQLite
- **API Documentation**: Postman Collection included

## 📋 Prerequisites

- PHP ^8.2
- Composer
- Node.js & NPM
- MySQL or SQLite

## ⚙️ Installation & Setup

1. **Clone the repository**:
   ```bash
   git clone <repository-url>
   cd NooRly-Backend
   ```

2. **Run the setup script**:
   The project includes a convenient setup script defined in `composer.json`:
   ```bash
   composer run setup
   ```
   *This will install dependencies, create the `.env` file, generate the app key, and run migrations.*

3. **Configure Environment**:
   Update your `.env` file with your database and social provider credentials.

4. **Start the development server**:
   ```bash
   composer run dev
   ```

## 🔐 API Endpoints

The API is versioned (`v1`) and documented in the provided Postman collection.

- **Auth**: `/api/v1/auth/*` (Login, Register, Social, Guest)
- **Lessons**: `/api/v1/lessons`
- **Daily Tasks**: `/api/v1/daily-tasks`
- **Utility**: `/api/v1/languages`

## 📂 Documentation

- [Postman Collection](POSTMAN_README.md)
- [Database Migration Guide](DATABASE_MIGRATION_GUIDE.md)
- [I18N Guide](I18N_GUIDE.md)
- [Filament Tabs Guide](FILAMENT_TABS_GUIDE.md)

## 📄 License

This project is open-sourced software licensed under the [MIT license](LICENSE).
