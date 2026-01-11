# Media Storage API

A Laravel-based web API for uploading and storing images and videos for multiple external websites and applications.

The service is intended to be used as a **centralized media storage backend** for content-driven platforms such as:
- Content Management Systems (CMS)
- E-commerce platforms (product images and videos)
- Marketing websites and landing pages
- Mobile and web applications requiring media uploads
- Internal company tools that need secure media storage

The API accepts media uploads together with metadata (title and description), stores the original file, and generates a thumbnail asynchronously to prevent request timeouts and ensure high availability.

---

## Features

- Upload images and videos via REST API
- Store title and description metadata
- Asynchronous media processing using Laravel queues
- Thumbnail generation:
    - Images: resized thumbnail (max 200x200)
    - Videos: thumbnail extracted from a video frame (max 200x200)
- Processing status endpoint
- API authentication using Laravel Sanctum
- Immediate `202 Accepted` response on upload

---

## Requirements

- PHP 8.2+
- Laravel 10+
- Database (MySQL, PostgreSQL, etc.)
- Queue worker running
- `ffmpeg` installed on the server (required for video thumbnails)

---

## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan storage:link
```

## Queue setup
This project uses the database queue driver.

```bash
php artisan queue:table
php artisan migrate
php artisan queue:work
```
Make sure the queue worker is running in the background (Supervisor or systemd in production).

---

## Authentication
This API is protected using Laravel Sanctum and requires a Personal Access Token.

## Create a user and token
Using Laravel Tinker:

```bash
php artisan tinker
```
```php
$user = \App\Models\User::create([
    'name' => 'API User',
    'email' => 'api@example.com',
    'password' => bcrypt('password'),
]);

$token = $user->createToken('media-api')->plainTextToken;
```
Save the generated token — it will be used for all API requests.

## Using the token
Send the token in the request header:

```makefile
Authorization: Bearer YOUR_API_TOKEN
```

---

## API Endpoints
### POST /api/media
Uploads an image or video file and starts asynchronous processing.


* **Authentication:** Required
* **Content-Type:** `multipart/form-data`

Request fields

| Field | Type | Required | Description         |
|--------|---|---|---------------------|
| title | string | yes | Media title         |
| description | string |no | Media description   |
| file | file | yes | Image or video file |

Supported MIME types:
* Images: image/jpeg, image/png, image/webp, image/gif
* Videos: video/mp4, video/quicktime, video/x-matroska, video/webm

### Response (202 Accepted)
```json
{
  "id": 1,
  "status": "processing",
  "status_url": "https://your-domain/api/media/1/status"
}
```

---

## GET `/api/media/{id}/status`
Returns the current processing status of the uploaded media.

**Authentication:** Required

## Response (200 OK)
```json
{
  "id": 1,
  "status": "ready",
  "type": "image",
  "original_path": "media/original/1/file.jpg",
  "thumbnail_path": "media/thumb/1/thumb.jpg",
  "error_message": null
}
```
**Possible status values**
* queued
* processing
* ready
* failed

---

## Thumbnail Generation
* **Images**
    Resized to fit within 200x200 pixels while maintaining aspect ratio.
* **Videos**
    A single frame is extracted (around the 1-second mark) using ffmpeg and resized to a maximum of 200x200 pixels.

Original files and thumbnails are stored separately, and both paths are saved in the database.

---

## Example Requests
### Upload media
```bash
curl -X POST http://localhost:8000/api/media \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Accept: application/json" \
  -F "title=Example media" \
  -F "description=Sample upload" \
  -F "file=@/path/to/file.mp4"
```
### Check status
```bash
curl -X GET http://localhost:8000/api/media/1/status \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Accept: application/json"
  ```
---

## Notes
* All media processing is handled asynchronously via Laravel queues.
* The upload endpoint never performs heavy processing.
* `ffmpeg` must be available in the system PATH for video thumbnails.
* The API is designed to be used by multiple external applications or websites.
* The API is intended for server-to-server communication and is not publicly accessible.

