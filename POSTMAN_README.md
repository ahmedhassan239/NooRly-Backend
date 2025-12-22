# Postman API Testing Instructions - Noorly

This package includes everything you need to test the Noorly API v1 endpoints.

## Files Included
1. `Noorly_API_v1.postman_collection.json` - The collection of API requests.
2. `Noorly_Local.postman_environment.json` - Environment configuration for local testing.
3. `google_auth_test.html` - Helper tool to get Google Auth tokens.

## Step 1: Import into Postman
1. Open Postman.
2. Click **Import** (top left).
3. Drag and drop `Noorly_API_v1.postman_collection.json` and `Noorly_Local.postman_environment.json`.
4. Import them.

## Step 2: Configure Environment
1. In Postman, look at the top right dropdown for environments.
2. Select **Noorly Local**.
3. Click the "Edit" (eye icon) button next to it.
4. Ensure `base_url` is correct (default: `http://localhost:8000`).
5. (Optional) Set `test_email` and `test_password` if you want to test Login directly without registering first.

## Step 3: Run the Tests
We have automated token handling. You don't need to copy/paste Bearer tokens manually.

### A. Health Check
- Run **Health Check** to ensure your API is reachable.

### B. Standard Auth Flow
1. Run **Auth / Guest Login**. checks:
   - Sets `token` in environment automatically.
   - Sets `app_user_id`.
2. Access protected routes (e.g., **Home / Get Home Data**) immediately after. It will use the new token.

### C. Registration Flow
1. Run **Auth / Register**.
   - It generates a random email (e.g., `testuser_1740...@example.com`).
   - Sets the new `token`.

### D. Google Auth Testing
Since Postman cannot open a browser webview to login to Google securely:
1. Open `google_auth_test.html` in your browser (Chrome/Firefox).
2. **Edit the file first**: Replace `YOUR_GOOGLE_CLIENT_ID_HERE` with your actual Google Client ID.
3. Click "Sign in with Google".
4. Copy the generated **ID Token**.
5. In Postman, edit the **Noorly Local** environment.
6. Paste the token into `google_id_token`.
7. Save.
8. Run **Auth / Google Auth** request in Postman.

## Automated Scripts
- **Pre-request**: Adds a `random` timestamp variable to every request (useful for unique emails).
- **Tests**: 
  - Automatically captures `token`, `user.id`, `lesson_id`, `task_id`, etc., from responses.
  - Saves them to the Environment for subsequent requests to use.

## Troubleshooting
- If "Health Check" fails, ensure your Laravel server is running (`php artisan serve`).
- If "Unauthenticated", run a Login or Register request first to refresh the `token`.
