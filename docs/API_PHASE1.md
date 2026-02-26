# API Phase 1 - Authentication & Core Services

## Authentication

### POST /api/auth/login

Logs in an admin using credentials.

### POST /api/auth/sign-redirect

Signs a redirect path for secure redirection during Step-Up flows.

**Headers:**
- `Cookie`: Valid `auth_token` required.

**Body:**
- `path`: (string) The internal path to redirect to after verification.

**Response:**
```json
{
  "token": "...",
  "redirect_url": "/2fa/verify?r=..."
}
```

## System

### GET /api/system/health

Returns system health status.
