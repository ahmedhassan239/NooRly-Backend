# Authentication API

Base URL: `https://admin.noorly.net/api/v1`

All responses follow this format:
```json
{
  "status": true,
  "message": "Success message",
  "data": { ... },
  "meta": { ... }
}
```

## Endpoints

### 1. Register
Create a new user account via email/password.

- **URL**: `/auth/register`
- **Method**: `POST`
- **Body**:
  ```json
  {
    "email": "user@example.com",
    "password": "password123", // min 8 chars
    "name": "User Name",
    "gender": "male", // optional: male, female, other
    "birth_date": "1990-01-01", // optional: YYYY-MM-DD
    "locale": "en" // optional: en, ar
  }
  ```
- **Success Response (200)**:
  ```json
  {
    "status": true,
    "message": "Authenticated successfully",
    "data": {
      "token": "1|sanctum_token...",
      "token_type": "Bearer",
      "user": {
        "id": 1,
        "uuid": "...",
        "status": "active",
        "name": "User Name",
        "email": "user@example.com",
        "avatar": null,
        "profile": { ... }
      }
    },
    "meta": { ... }
  }
  ```

### 2. Login
Authenticate with email and password.

- **URL**: `/auth/login`
- **Method**: `POST`
- **Body**:
  ```json
  {
    "email": "user@example.com",
    "password": "password123"
  }
  ```
- **Success Response (200)**: Same as Register.

### 3. Social Login
Authenticate via social provider (Google, Apple, Facebook).

- **URL**: `/auth/social/{provider}`
- **Method**: `POST`
- **URL Params**: `provider` can be `google`, `facebook`, or `apple`.
- **Body**:
  - **Google**:
    ```json
    { "id_token": "eyJhb..." }
    ```
  - **Facebook**:
    ```json
    { "access_token": "EAAD..." }
    ```
  - **Apple**:
    ```json
    { "identity_token": "eyJhb..." }
    ```
- **Success Response (200)**: Same as Register.

### 4. Get Current User (Me)
Get details of the currently authenticated user.

- **URL**: `/me`
- **Method**: `GET`
- **Headers**: `Authorization: Bearer <token>`
- **Success Response (200)**:
  ```json
  {
    "status": true,
    "data": {
        "id": 1,
        "uuid": "...",
        "name": "User Name",
        "email": "user@example.com",
        "profile": { ... }
    }
  }
  ```

### 5. Logout
Revoke current token.

- **URL**: `/auth/logout`
- **Method**: `POST`
- **Headers**: `Authorization: Bearer <token>`
- **Success Response (200)**:
  ```json
  {
    "status": true,
    "message": "Logged out successfully",
    "data": null
  }
  ```

### 6. Health Check
Check API status.

- **URL**: `/health`
- **Method**: `GET`
- **Response**:
  ```json
  {
    "status": true,
    "data": { "status": "ok", ... }
  }
  ```

## Error Handling
Errors return 4xx/5xx status codes with `status: false`.

Example Validation Error (422):
```json
{
  "status": false,
  "message": "Validation failed",
  "data": null,
  "meta": {
    "errors": {
      "email": ["The email field is required."]
    }
  }
}
```
