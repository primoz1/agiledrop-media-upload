# Media Storage API

A robust Laravel 12 API designed for centralized media storage. It handles image and video uploads, metadata management, and asynchronous processing for multiple external applications.

The service is intended as a **centralized media storage backend** for:
- Content Management Systems (CMS)
- E-commerce platforms (product images and videos)
- Marketing websites and mobile applications
- Internal tools requiring secure media storage

The API accepts media uploads with metadata, stores the original file, and generates thumbnails asynchronously to ensure high availability and prevent request timeouts.

---

## Features

- **Asynchronous Processing**: Background jobs for media handling using Laravel Queues.
- **Thumbnail Generation**:
    - **Images**: Resized thumbnails (max 200x200) using Intervention Image.
    - **Videos**: Frame extraction (at 1s mark) using `ffmpeg`.
- **Status Monitoring**: Real-time tracking of processing states (`queued`, `processing`, `ready`, `failed`).
- **Secure Access**: Protected via **Laravel Sanctum** (Personal Access Tokens).
- **Docker Ready**: Fully containerized environment with Laravel Sail.

---

## Requirements

- **Docker Desktop** (Recommended for Sail)
- **PHP 8.2+** & **Composer** (if running locally)
- **ffmpeg** (Required for video thumbnail extraction)

---

## Installation & Setup

1. **Clone & Install:**
   ```bash
   git clone https://github.com/primoz1/agiledrop-media-upload.git
   cd agiledrop-media-upload
   composer install
   ```

2. **Environment Setup:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Start with Laravel Sail:**
   ```bash
   ./vendor/bin/sail up -d
   ./vendor/bin/sail artisan migrate
   ./vendor/bin/sail artisan storage:link
   ```

4. **Queue Worker:**
   To process thumbnails, start the worker:
   ```bash
   ./vendor/bin/sail artisan queue:work
   ```

---

## Authentication

### Authentication design

The API is protected using **Laravel Sanctum** with Personal Access Tokens.

This approach was chosen because it is:
- well suited for server-to-server communication
- simple to use for multiple external applications
- stateless and easy to rotate or revoke tokens

Each external application is expected to use its own API token, which must be
sent with every request using the `Authorization: Bearer <token>` header.

Unauthorized requests are rejected with HTTP 401.
Token abilities are enforced on endpoints (e.g. upload requires `media:upload`, status requires `media:status`).


### To generate a token for an external application:

1. Access Tinker: `./vendor/bin/sail artisan tinker`
2. Create user and token:
   ```php
   $user = \App\Models\User::create([
       'name' => 'API Client',
       'email' => 'api@example.com',
       'password' => bcrypt('secret-password'),
   ]);
   echo $user->createToken('media-api', ['media:upload', 'media:status'])->plainTextToken;
   ```

Include this token in your requests: `Authorization: Bearer YOUR_TOKEN`

---

## API Endpoints

### 1. Upload Media
`POST /api/media`

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| title | string | Yes | Max 255 chars |
| description | string | No | Optional details |
| file | file | Yes | image/jpeg, png, webp, gif or video/mp4, mov, mkv, webm |

**Response (202 Accepted):**
```json
{
  "id": 1,
  "status": "processing",
  "status_url": "http://localhost/api/media/1/status"
}
```

### 2. Check Status
`GET /api/media/{id}/status`
**Response (200 OK):**
```json
{
    "id": 1,
    "status": "ready",
    "type": "image",
    "original_url": "http://localhost/storage/media/original/1/file.jpg",
    "thumbnail_url": "http://localhost/storage/media/thumb/1/thumb.jpg",
    "error_message": null
}
```

## Running Tests
The project uses PHPUnit for Feature testing:
```bash
./vendor/bin/sail artisan test
```

## Tech Stack
* Framework: Laravel 12
* Database: MySQL
* Media: Intervention Image 3, FFmpeg
* Dev Ops: Laravel Sail (Docker)

**Note:** A development-only helper endpoint exists for generating API tokens locally.
It is disabled outside the local environment.
