# Doxa CMS

An admin panel with built-in user authentication and Socket.IO servers.

## Overview

Doxa CMS provides a complete admin interface solution with user authentication and real-time communication capabilities through Socket.IO. This package is currently in development and not publicly published.

## Installation

### 1. Clone the Repository

```bash
git clone https://github.com/dtchkoidze/pdox.git
```

### 2. Install Dependencies

Navigate to the cloned directory and install all required dependencies:

```bash
cd pdox
npm i-all
```

### 3. Configure Your Consumer Project

In your consumer project (the one that will use this package), add the following to your `composer.json`:

```json
"repositories": [
  {
    "type": "path",
    "url": "/absolute/path/to/pdox",
    "options": {
      "symlink": true
    }
  }
]
```

### 4. Set Minimum Stability

Since Doxa CMS is still in development, set the minimum stability in your consumer project's `composer.json`:

```json
"minimum-stability": "dev"
```

### 5. Require the Package

From the root of your consumer project, run:

```bash
composer require doxa/doxa-cms
```

### 6. Environment Configuration

In the Doxa CMS clone:
- Copy the contents of `.env.example` to a new file called `.env`
- Set the `VITE_CONSUMER_PROJECT_PATH` to the absolute path of your consumer project root
  - Example: `/home/username/sites/site.loc`

### 7. Start the Development Server

```bash
npm run dev-all
```

### 8. Access the Admin Panel

You're all set! You can now access the admin panel by navigating to `/admin` in your browser.

## Development

You can now start working on or with Doxa CMS.

## Current Status

This project is currently under development and not considered stable. It's not published publicly yet.