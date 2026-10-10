# 🛍️ ShopFlow PHP

### Clothing Inventory & Order Management System

ShopFlow is a PHP and MySQL-based web application designed to help clothing businesses manage product inventory, track stock, process customer orders, and generate invoices through a simple dashboard.

## ✨ Features

- 🔐 **User Authentication** — Login and logout with session-based access control.
- 🔑 **Password Recovery** — Reset passwords using an email-based reset link.
- 📧 **Email Integration** — Send emails through the Brevo API.
- 📦 **Inventory Management** — Add and manage clothing products.
- 👕 **Product Variants** — Organize products by size and colour.
- 📊 **Stock Tracking** — Monitor available stock and identify low-stock items.
- 🛒 **Order Management** — Create customer orders and track sales.
- 🧾 **Invoice Generation** — View, print, or save invoices as PDF through the browser's print dialog.
- 🗂️ **Category Management** — Organize products using standard or custom categories.
- 🗄️ **MySQL Integration** — Store application data in a relational database.
- 👤 **Profile Dropdown** — Access account actions from the dashboard.
- 🚪 **Logout Confirmation** — Confirm before ending the current session.

## 🧰 Tech Stack

| Technology | Purpose |
|---|---|
| PHP | Backend logic and API |
| MySQL | Database |
| JavaScript | Interactive frontend |
| HTML5 | Page structure |
| CSS3 | Styling and layout |
| XAMPP | Local development environment |
| Git & GitHub | Version control and source hosting |
| Brevo API | Email delivery |

## 🚀 Getting Started

### Prerequisites

- Windows computer
- XAMPP with Apache and MySQL
- Web browser
- Git (optional)
- A Brevo account and verified sender email for email features

### Installation

1. Clone or download this repository into your XAMPP `htdocs` directory.
2. Start **Apache** and **MySQL** from the XAMPP Control Panel.
3. Create the required MySQL database using phpMyAdmin.
4. Configure the database connection in your local `config.php`.
5. Import the database schema if a schema file is available. If the schema is not included in the repository, use the schema from your local development setup.
6. Configure the email settings in your local `mail_config.php`.
7. Open the project in your browser. For example:

   `http://localhost/ShopFlow-PHP/`

### Configuration and Security

The application uses local configuration files for database and email credentials.

- Keep `config.php` and `mail_config.php` private if they contain credentials.
- Never commit API keys, database passwords, or other secrets to GitHub.
- Keep `.env` and other sensitive configuration files out of version control.
- Use strong password hashing and session-based access control.
- For public deployment, configure HTTPS, secure session cookies, environment-based secrets, and production database credentials.

**Important:** Email delivery requires a valid Brevo API key and a verified sender address. Do not place real API keys in this README.

## 📁 Project Structure

```text
ShopFlow-PHP/
├── assets/
│   ├── app.js
│   └── styles.css
├── .gitignore
├── .htaccess
├── api.php
├── forgot_password.php
├── index.php
├── login.php
├── logout.php
├── mail_helper.php
├── reset_password.php
├── config.php          # Local configuration; keep private
├── mail_config.php     # Local email credentials; keep private
├── LICENSE
└── README.md
```

*Note: The exact files in your repository may differ from this example. Private configuration files may exist locally without being tracked by Git.*

## 🎯 Project Goals

ShopFlow was developed as a practical web development project to explore:

- PHP backend development
- MySQL database integration
- Authentication and session management
- Inventory and order workflows
- Frontend interaction with JavaScript
- API integration for email delivery
- Version control using Git and GitHub

## 🔮 Future Improvements

Potential enhancements include:

- Sales reports and analytics
- Advanced inventory search and filtering
- Exportable inventory reports
- Automated testing
- Deployment to a secure production environment
- Role-based access control

These are possible future enhancements, not currently claimed features.

## 👨‍💻 Author

**Jeet Gorani**

GitHub: [@JeetGorani](https://github.com/JeetGorani)

## 📄 License

See the [LICENSE](LICENSE) file for license details.