# Authentication Service (Microservice)

This microservice handles user credentials, login sessions, and JWT token issue/verification/refreshment. It sets secure, HTTP-only cookies (`access_token` and `refresh_token`) to manage stateful sessions for the stateless SPA.

## Tech Stack
- **Framework:** Laravel 13
- **PHP Version:** PHP 8.3
- **Database:** MySQL 8.4 (`db_auth`)
- **Authentication:** JWT (via `firebase/php-jwt`)

---

## API Endpoints Reference

All endpoints are prefixed with `/api`.

### Public Routes
- **`POST /api/login`**
  - Authenticates user credentials.
  - Sets HTTP-only cookies: `access_token` (expires in 15 mins) and `refresh_token` (expires in 30 days).
  - Body: `{ "email": "...", "password": "..." }`
- **`POST /api/refresh`**
  - Silently refreshes the `access_token` using a valid `refresh_token` cookie.

### Authenticated Routes (Requires JWT Cookie)
- **`GET /api/me`**
  - Retrieves current logged-in user profile.
- **`POST /api/logout`**
  - Revokes session tokens, blacklists JWT JTI, and clears browser cookies.
- **`GET /api/users`**
  - Lists users.
- **`GET /api/users/{id}`**
  - Retrieves a specific user profile by ID.

### Superadmin Restricted Routes (Requires JWT Cookie + Superadmin Role)
- **`POST /api/users`** - Registers a new user.
- **`PUT /api/users/{id}`** - Updates user details.
- **`DELETE /api/users/{id}`** - Deactivates/deletes a user.

---

## Environment Configuration

A `.env.example` file is provided. Key custom variables:
- `JWT_ACCESS_SECRET`: Secret key for signing and verifying JWT access tokens.
- `JWT_COOKIE_SECURE`: Set to `false` for local HTTP testing, and `true` in production (HTTPS).
- `DB_DATABASE`: Defaults to `db_auth`.
