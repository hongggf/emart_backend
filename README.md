<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

A RESTful backend API built with **Laravel** and **Laravel Sanctum** for authentication.  
This API is designed to serve a **Flutter mobile application** with secure token-based authentication.

---

## ✨ Features

- User Authentication (Register / Login / Logout)
- Token-based authentication using Laravel Sanctum
- Protected API routes
- RESTful API structure
- Product management (CRUD)
- Clean and scalable architecture
- Ready for mobile frontend integration

---

## 🛠 Tech Stack

- Laravel
- Laravel Sanctum
- MySQL
- REST API


Step 1: Clone the Repository
```
git clone https://github.com/hongggf/emart_backend.git
cd laravel_ecommerce_api
```
Step 2: Install Dependencies
```
composer install
```
Step 3: Environment Configuration
```
cp .env.example .env
php artisan key:generate
```

Update .env with your database credentials.

Step 4: Run Migrations
```
php artisan migrate
```

Step 5: Start the Server
php artisan serve
