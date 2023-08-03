# Laravel and Angular Chat Project

This project is a chat application built using Laravel on the backend and Angular on the frontend. It utilizes the Chatify backend and Pusher to provide real-time messaging functionality.

For the Laravel backend, we have used a custom boilerplate project that provides a solid foundation for building web applications with Laravel. You can find the Laravel boilerplate project in the following repository:

[Laravel Boilerplate Project](https://github.com/your-username/your-laravel-boilerplate-repo)

Please follow the instructions in the repository's README.md file to set up and run the Laravel backend.

The Angular frontend for this chat application can be found in the following repository:

[Angular Chat Frontend Repository](https://github.com/your-username/your-angular-chat-frontend-repo)

Please follow the instructions in the repository's README.md file to set up and run the Angular frontend.

## Prerequisites

Before you begin, ensure you have the following software installed:

- Node: 18.10.0
- Angular CLI: 15.2.8
- Package Manager: npm 8.19.2
- PHP: 8.1.6
- Composer

## Features

- **Laravel 10.7.1 and PHP 8.1.6**: The project is built on the latest versions of Laravel and PHP, ensuring access to the latest features, performance improvements, and security updates.
- **Repository Pattern Implementation**: The project incorporates the `andersao/l5-repository` package, which simplifies the implementation of the Repository pattern. This pattern promotes separation of concerns by providing a layer between the application's data layer and business logic, making it easier to manage and test database operations.
- **Debugging with Laravel Debugbar**: The `barryvdh/laravel-debugbar` package is included to facilitate debugging during development. It provides a toolbar that displays useful information such as query execution times, memory usage, and log messages, helping you identify and resolve issues more efficiently.
- **Easy Installation and Setup**: The installation process is straightforward, thanks to the provided step-by-step instructions. By following the installation guide, you can quickly set up the project on your local machine and get started with development.
- **Database Migrations**: Laravel's built-in database migration feature allows you to manage database schema changes in a convenient and version-controlled manner. The project includes predefined migrations, making it easy to create and modify database tables as your application evolves.

## Getting Started

Follow the steps below to set up and run the project:

### 1. Clone the Repository

```bash
git clone [repository-url]
```

### 2. Set Up the Laravel Backend

Follow the instructions in the [Laravel Boilerplate Project](https://github.com/your-username/your-laravel-boilerplate-repo) repository to set up and run the Laravel backend.

- After setting up, you should configure pusher configuration in the .env file.

### 3. Set Up the Angular Frontend

Follow the instructions in the [Angular Chat Frontend Repository](https://github.com/your-username/your-angular-chat-frontend-repo) repository to set up and run the Angular frontend.

- After setting up, you should configure pusher configuration in the environment file.


### 4. Customization

- to customize request actions `app/Http/Controllers/vendor/Chatify/Api/MessagesController.php`
- to overrides the default implementation of the Chatify repository class in `app/Repositories/ChatifyCustom.php`

Feel free to customize and extend the provided boilerplate code to suit your specific chat project needs. Happy coding!
