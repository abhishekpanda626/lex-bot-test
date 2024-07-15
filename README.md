# Chat Interaction with Amazon Lex

This project integrates an Amazon Lex bot into a Laravel application using Filament for the frontend. Users can interact with the Lex bot via predefined intents, and their interactions are logged and displayed on a chat page.After the conversation ends the emotional index will be fetched from the Gemini API

## Features

- Users are presented with two random intents from a predefined set.
- Conversations with the bot flow from one intent to the next.
- Once the chat ended the emotional index will be fetched and displayed on chat page

## Prerequisites

- PHP 8.2+
- Composer
- Laravel 10+
- AWS Account with Lex bot set up
- Filament Admin Panel

## Installation

1. **Clone the repository:**

    ```sh
    git clone <repository-url>
    cd <repository-folder>
    ```

2. **Install dependencies:**

    ```sh
    composer install
    npm install && npm run dev
    ```

3. **Set up environment variables:**

    Create a `.env` file from the example:

    ```sh
    cp .env.example .env
    ```

    Update the `.env` file with your database and AWS and Gemini credentials:

    ```env
    AWS_ACCESS_KEY_ID=your-access-key-id
    AWS_SECRET_ACCESS_KEY=your-secret-access-key
    AWS_DEFAULT_REGION=your-region
    AWS_LEX_BOT_NAME=your-bot-name
    AWS_LEX_BOT_ALIAS=your-bot-alias

    GOOGLE_API_KEY=your-api-key
    GEMINI_API_URL=gemini-api-url
    ```

4. **Run migrations:**

    ```sh
    php artisan migrate
    ```

5. **Run the application:**

    ```sh
    php artisan serve
    ```

## Usage

1. **Access the chat page:**

    Navigate to `http://localhost:8000/chat-page` to start interacting with the Lex bot.

2. **Submit a message:**

    The page will start the chat with a random intent.. Once the conversation for the current intent ends, another random intent will be presented.



## Project Structure

- **`app/Filament/Pages/ChatPage.php`:** The main Filament page class that handles the chat logic.
- **`app/Services/LexService.php`:** A service class for interacting with the Amazon Lex bot.
- **`app/Services/GeminiService.php`:** A service class for interacting with Gemini AI for emotional index of the user.
- **`resources/views/filament/pages/chat-page.blade.php`:** The Blade view for the chat page.
- **`app/Models/ChatInteraction.php`:** Eloquent model for saving chat interactions.
