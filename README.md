
# 🏋️‍♂️ Fitness Tracker App – PHP Backend

This is the backend API for the **Fitness Tracker App**, built using **PHP**, **MongoDB**, and **JWT**. It provides secure endpoints for managing user accounts, workouts, and fitness progress tracking.

---

## 🚀 Features

- ✅ JWT-based user authentication
- ✅ Secure login, registration, and logout
- ✅ Track and manage workouts
- ✅ MongoDB for fast and scalable data storage
- ✅ Token blacklisting for secure logout
- ✅ RESTful API design

---
| Method | Endpoint  | Description                |
| ------ | --------- | -------------------------- |
| POST   | /register | Register a new user        |
| POST   | /login    | Log in and get JWT token   |
| POST   | /logout   | Logout and blacklist token |
| GET    | /profile  | Get current user profile   |

---
| Method | Endpoint       | Description            |
| ------ | -------------- | ---------------------- |
| POST   | /workouts      | Add a new workout      |
| GET    | /workouts      | List all workouts      |
| GET    | /workouts/{id} | Get a specific workout |
| DELETE | /workouts/{id} | Delete a workout       |

## ⚙️ Setup & Installation

Follow these steps to prepare and run the PHP backend:

### 1. 📦 Clone the Repository

```bash
git clone https://github.com/bayedhaf/ Fitness-tracker-web-app-backend.git
cd Fitness-tracker-web-app-backendd

## 📁 Install Dependencies
```bash
composer install```


