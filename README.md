# 🎬 Tagalog-Dubbed-Anime

A minimalist and professional video streaming platform built with **PHP
(XAMPP)**, **Bootstrap 5**, **jQuery**, and **SweetAlert2**.

This project allows public users to browse and watch anime episodes,
while only authenticated administrators can upload, edit, and delete
content.

------------------------------------------------------------------------

## 📸 Screenshot

> Replace the image below with your actual screenshot.

![Homepage Screenshot](assets/screenshot.png)

------------------------------------------------------------------------

## 🚀 Features

### Public Access

-   Browse series by title
-   View episodes per series
-   Watch videos
-   Clean, minimalist dark UI
-   Responsive layout

### Admin Only

-   Secure admin login (modal-based)
-   Upload video with real-time progress bar
-   Automatic thumbnail extraction using FFmpeg
-   Edit episode metadata
-   Delete episodes (auto cleanup files)
-   Duplicate episode prevention (Season + Episode validation)

------------------------------------------------------------------------

## 🛠 Tech Stack

-   PHP 8+
-   MySQL (InnoDB)
-   Bootstrap 5
-   jQuery
-   SweetAlert2
-   Font Awesome
-   FFmpeg (thumbnail extraction)
-   Apache (mod_rewrite enabled)

------------------------------------------------------------------------

## 📦 Installation (Local Setup via XAMPP)

### 1️⃣ Clone Repository

git clone https://github.com/yourusername/Tagalog-Dubbed-Anime.git

Move the project into:

C:`\xampp`{=tex}`\htdocs`{=tex}\

------------------------------------------------------------------------

### 2️⃣ Create Database

Create a database named:

tagalog_dubbed_anime

Import the provided SQL schema.

------------------------------------------------------------------------

### 3️⃣ Configure FFmpeg

Example path inside save_upload.php:

\$ffmpeg = "C:\\ffmpeg\\ffmpeg-6.x-essentials_build\\bin\\ffmpeg.exe";

------------------------------------------------------------------------

### 4️⃣ Enable Apache Rewrite

Make sure: - mod_rewrite is enabled - AllowOverride All is enabled

------------------------------------------------------------------------

### 5️⃣ Create Admin Account

Temporarily open:

http://localhost/Tagalog-Dubbed-Anime/create_admin.php

Then delete create_admin.php immediately.

------------------------------------------------------------------------

## 🌐 Clean URLs

-   `/` → Homepage\
-   `/upload` → Upload (Admin only)\
-   `/series/{id}` → Series episodes\
-   `/watch/{id}` → Watch episode\
-   `/edit/{id}` → Edit episode\
-   `/delete/{id}` → Delete episode

------------------------------------------------------------------------

## 🔒 Security

-   Passwords hashed using password_hash()
-   Admin routes protected via session validation
-   Duplicate episode protection enforced at DB level

------------------------------------------------------------------------

## 📁 Project Structure

Tagalog-Dubbed-Anime/ │ ├── assets/ ├── uploads/ ├── config.php ├──
db.php ├── auth.php ├── index.php ├── upload.php ├── save_upload.php ├──
series.php ├── watch.php ├── edit.php ├── update.php ├── delete.php ├──
login.php ├── logout.php ├── .htaccess └── README.md

------------------------------------------------------------------------

## 📜 License

Educational / Portfolio project.
