# HNG Twig Ticket App

This is a simple ticket management application built with PHP and Twig. It provides a basic setup for handling tickets, user authentication, and a dashboard for viewing and managing tickets.

## Technologies Used

-   **PHP**: The core programming language for the backend logic.
-   **Twig**: A flexible, fast, and secure templating language for PHP.
-   **Composer**: A dependency manager for PHP, used to manage project libraries.
-   **JSON**: Used for data storage (users and tickets).

## Setup and Installation

To get this project up and running on your local machine, follow these steps:

1.  **Clone the repository:**
    ```bash
    git clone https://github.com/xieumar/HNG-Twig-Ticket-App.git
    cd HNG-Twig-Ticket-App
    ```

2.  **Install PHP dependencies:**
    Make sure you have Composer installed. Then, run:
    ```bash
    composer install
    ```

3.  **Configure your web server:**
    Point your web server (Apache, Nginx, or PHP's built-in server) to the `public` directory.

    **Using PHP's built-in server (for development):**
    ```bash
    php -S localhost:8000 -t public/
    ```
    Then, open your browser and navigate to `http://localhost:8000`.

## Usage

-   **Registration/Login**: Users can sign up for a new account or log in with existing credentials.
-   **Create Tickets**: Authenticated users can create new support tickets.
-   **View Tickets**: Users can view a list of their created tickets and their details.
-   **Dashboard**: A central place for users to manage their tickets.

## Project Structure

-   `public/`: Contains the `index.php` (front controller) and static assets (CSS, JS).
-   `src/`: Contains PHP source code, including data management classes.
-   `templates/`: Stores all Twig template files for rendering views.
-   `data/`: JSON files for storing application data (users, tickets).
-   `vendor/`: Composer dependencies.