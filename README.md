🔐 My Account

   

My Account is a lightweight, secure, and modern personal account dashboard built using PHP and JSON files (no database). It allows users to manage their personal data such as profile, notes, tasks, bookmarks, expenses, uploads, and settings — all from one place.

This project is ideal for learning PHP, student projects, and shared hosting environments.


---

📑 Table of Contents

Demo & Screenshots

Features

Tech Stack

Project Structure

Installation

Security

Usage

Roadmap

Contributing

License



---

📸 Demo & Screenshots

> Screenshots will be added soon.




---

✨ Features

🔐 Authentication

User registration & login

Password hashing (password_hash)

Session-based authentication

CSRF protection

Role-based access (Admin / User)


📊 Dashboard

User overview

Notes / Tasks / Links / Upload count

Expense summary


👤 Profile

Update profile info

Profile picture upload

Change password

Theme preference


📝 Notes

Create, edit, delete notes

Markdown support

Tags & search


✅ To‑Do List

Task management

Completion tracking

Filters


🔖 Bookmarks

Save useful links

Categories & search


💰 Expense Tracker

Income & expense logging

Monthly summary

Charts


📁 File Uploads

Secure uploads

Image thumbnails


⚙️ Settings

Dark / Light mode

Language preference


🛠 Admin Panel

Manage users

Reset passwords

Activity logs



---

🧱 Tech Stack

Layer	Technology

Backend	PHP 8.1+
Storage	JSON Files
Frontend	HTML5, CSS3, JavaScript
UI	Tailwind CSS / Custom CSS
Charts	Chart.js (optional)



---

📁 Project Structure

MyAccount/
│── index.php
│── login.php
│── register.php
│── logout.php
│── config.php
│── .htaccess
│
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
│
├── includes/
│   ├── auth.php
│   ├── functions.php
│   ├── csrf.php
│   ├── header.php
│   └── footer.php
│
├── data/
│   ├── users.json
│   ├── settings.json
│   ├── logs.json
│   ├── users/
│   │   └── <user_id>/
│   │       ├── profile.json
│   │       ├── notes.json
│   │       ├── todo.json
│   │       ├── links.json
│   │       ├── expense.json
│   │       └── uploads/
│   └── users_archive/
│
├── pages/
│   ├── dashboard.php
│   ├── profile.php
│   ├── notes.php
│   ├── todo.php
│   ├── links.php
│   ├── expense.php
│   └── settings.php
│
└── admin/
    ├── index.php
    ├── users.php
    └── logs.php


---

⚙️ Installation

Requirements

PHP 8.1+

Apache / Nginx / PHP built-in server

Writable data/ directory


Setup

git clone https://github.com/your-username/my-account.git
cd my-account

1. Move project to htdocs or www


2. Ensure data/ is writable


3. Run seed_admin.php to create admin


4. Open browser and visit:



http://localhost/MyAccount


---

🔐 Security

Password hashing (bcrypt)

CSRF token validation

Input sanitization

Session regeneration

File upload restrictions



---

▶️ Usage

Register a new account

Login to dashboard

Manage notes, tasks, links, and expenses

Upload files securely

Admin can manage users



---

🛣 Roadmap

[ ] REST API

[ ] Email verification

[ ] Two-factor authentication

[ ] Mobile app sync

[ ] Database (MySQL) support



---

🤝 Contributing

Contributions are welcome!

1. Fork the repository


2. Create your feature branch


3. Commit changes


4. Open a Pull Request




---

📜 License

This project is licensed under the MIT License.


---

👤 Author

Developed as a learning & portfolio project using PHP + JSON.

⭐ If you like this project, give it a star!

✅ Done!
I’ve converted your README into a GitHub-style professional README with:

Badges (PHP version, storage type, status, license)

Clean Table of Contents

Feature highlights in GitHub format

Proper Installation, Usage, Security, and Roadmap

Contribution & License sections

Portfolio-ready layout ⭐
