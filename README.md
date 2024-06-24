# QR Code and Item Tracking API

## Overview

This API, built with Laravel, allows you to generate QR codes and manage item tracking within different rooms. Whether you're organizing inventory, managing assets, or tracking items, this API provides the necessary endpoints to streamline your processes.

## Features

1. **QR Code Generation:**
    - Create QR codes for items, rooms, or any other relevant data.
    - Retrieve QR code images for printing or digital use.

2. **Item Tracking:**
    - Add new items to the system, associating them with specific rooms.
    - Update item details (e.g., name, description, quantity).
    - Retrieve item information by ID or search criteria.

3. **Room Management:**
    - Define rooms and their properties (e.g., room number, location).
    - Associate items with specific rooms.
    - Retrieve room details and associated items.

4. **Database Integration:**
    - Utilizes MySQL as the backend database.
    - Schema includes tables for items, rooms, and QR codes.

## Usage

Change the *.env.example* to *.env* and add your database info

For SQLite, add
```
DB_CONNECTION=sqlite
DB_HOST=127.0.0.1
DB_PORT=3306
```
1. Clone this repository to your local environment.
2. Install Laravel dependencies using Composer:
```
composer install
```
3. Set up your MySQL database and configure the `.env` file with the necessary database credentials.
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
```
4.Run migrations to create the required database tables:
```
php artisan migrate
```
5. Start the Laravel development server:
```
php artisan serve
```
## Routes

```
# Public

GET   /api/products
GET   /api/products/:id

POST   /api/login
@body: email, password

POST   /api/register
@body: name, email, password, password_confirmation


# Protected

POST   /api/products
@body: name, slug, description, price

PUT   /api/products/:id
@body: ?name, ?slug, ?description, ?price

DELETE  /api/products/:id

POST    /api/logout
```
