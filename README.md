# DPCRM2 - Simple CRM Application

A simple Customer Relationship Management (CRM) application built for a small team of 3 people. This application allows tracking of client/prospect accounts, actions to be taken, and interaction history, all while being secure and easy to use.

## Technology Stack

- **Backend Framework**: Symfony 7
- **Database**: PostgreSQL
- **Template Engine**: Twig
- **CSS Framework**: Bootstrap 5 (via CDN)
- **JavaScript**: Vanilla JS or Stimulus for interactivity

## Features

- **Security and Authentication**
  - Standard login with email and password
  - Two-Factor Authentication (2FA) support
  - Protected pages accessible only to authenticated users

- **Accounts Management**
  - View and manage client/prospect accounts
  - Track account priorities and next steps
  - Assign account ownership to team members

- **Actions & History**
  - Track interactions with clients
  - Record action types (calls, emails, meetings)
  - Maintain a history of all interactions

- **User Management**
  - Manage application users
  - Assign roles and permissions

## Data Model

The application is built around four main entities:

1. **User**: Represents an application user
2. **Account**: Represents a client or prospect
3. **Action**: Represents a task or specific interaction linked to an account
4. **History**: Tracks a modification record or note on an Action

## Setup Instructions

### Prerequisites

- PHP 8.2 or higher
- Composer
- PostgreSQL

### Installation

1. Clone the repository:
   ```
   git clone https://github.com/yourusername/dpcrm2.git
   ```

2. Install dependencies:
   ```
   composer install
   ```

3. Configure your database in `.env` file

4. Create the database:
   ```
   php bin/console doctrine:database:create
   ```

5. Run migrations:
   ```
   php bin/console doctrine:migrations:migrate
   ```

6. Create an admin user:
   ```
   php bin/console app:create-user
   ```

7. Start the Symfony server:
   ```
   symfony server:start
   ```

## License

[Your license information here]

## Contributors

[List of contributors]
