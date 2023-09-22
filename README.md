<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

# CanBeAnything API

## Description

Welcome to the Can Be Anything API! This project provides a Laravel-based backend for the app with the wishlist It focuses on product listings and user management, providing you with all the necessary operations to build a front-end to your liking.

## Features

1. **User Management:** Register, login, update and manage user profiles.
2. **Product Listings:** Create, read, update, and delete product listings.
3. **Search and Filter:** Find specific products and filter results based on various parameters.
4. **Wishlist:** Add and remove products from your wishlist.
5. **Friends Interaction:** Friends can mark a gift as bought to warn to other friends to not buy it too.
## Getting Started

### Prerequisites

Ensure you have the following installed on your local development machine:

- PHP >= 8.1
- Composer

### Installation


1. Clone the repository to your local machine:
```bash
git clone https://github.com/SavioNicodemos/can-be-anything-api
```

2. Install dependencies via composer:
```bash
cd can-be-anything-api
composer install
```
3. Configure your `.env` file for the database connection. Use the `.env.example` as a template.

4. Run the database migrations:
```bash
php artisan migrate --seed
```
Now you should now be able to access the API via http://localhost:8000/, but all the API routes are served in  http://localhost:8000/api/v1

## Testing
We use PHPUnit for testing. To run the tests:
```bash
./vendor/bin/sail artisan test
```

## License
This project is open-sourced software licensed under the MIT license.

## Contact
If you have any questions, feel free to reach out to us.

## Acknowledgments
- Laravel
- Sanctum
- PHPUnit
