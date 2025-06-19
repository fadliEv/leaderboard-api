# **API Documentation: Leaderboard API & Assessment System**

## **Overview**
This API is designed to handle the functionality of a **Leaderboard** for a game system. It allows users to submit scores, retrieve leaderboard rankings, and interact with an external system for assessment. The API is built using **Laravel 9+ (PHP 8+)** and uses **MySQL** for data storage and **Redis** for caching.

### **Key Features**:
- **Submit Score**: Allows players to submit their scores for a given level.
- **Leaderboard**: Retrieves the leaderboard with the highest scores for each level, supports pagination and caching.
- **Assessment**: Interacts with an external system using a secure signature for API requests.

---

## **Table of Contents**
1. [API Routes](#api-routes)
2. [Data Model](#data-model)
3. [How to Use](#how-to-use)

---

## **API Routes**

### **1. Submit Score**
**POST** `/api/submit-history`  
Allows players to submit their scores.

#### **Request Body**:
```json
{
    "user_id": "int",    // User ID of the player (must exist in users table)
    "level": "int",      // Level of the game (1-10)
    "score": "int"       // Score submitted for the level
}
```

#### **Response Body**:
```json
{
    "status": {
        "code": 201,
        "description": "success"
    },
    "data": {
        "id": "int",       // History submit score ID
        "user_id": "int",  // User ID
        "level": "int",    // Level
        "score": "int"     // Submitted score
    }
}
```

### **2. Get Leaderboard**
**POST** `/api/leaderboard`  
Fetches the leaderboard with optional pagination. Optionally, filter by username.

**Query Parameters:**
- `page` : The page number for pagination (default is 1).
- `size` : Number of records per page (default is 10).
- `username` : (Optional) Filter leaderboard by username.

#### **Response Body**:
```json
{
    "status": {
        "code": 200,
        "description": "success get data"
    },
    "data": [
        {
            "ranking": "int",        // Ranking of the player
            "username": "string",    // Username of the player
            "last_level": "int",     // Last level reached by the player
            "total_score": "int"     // Total score accumulated by the player
        },
        ...
    ],
    "pagination": {
        "page": 1,
        "rows_per_page": 10,
        "total_rows": 1000,
        "total_pages": 100
    }
}

```

### **3. Assessment API**
**POST** `/api/assesment`  
Sends an assessment request to an external system, with the signature to ensure security.

**Request Headers:**
- `X-Nonce` :  Randomly generated string (different on each request).
- `X-API-Signature` : SHA256 encoded string generated using the formula `nonce + timestamp + secret_key`.

#### **Request Body**:
```json
{
    "timestamp": "int"  // 13-digit epoch timestamp
}
```

#### **Response Body**:
```json
{
    "message": "Request sent successfully!",
    "data": {
        "success": true,
        "request_ts": "int", // Request timestamp
        "message": "OK"
    }
}
```

---

## **Data Model**

### **1. User Model**
The User model is the default Laravel model for users but is extended with a `username` attribute.
- **username**: Unique identifier for the user (used in the leaderboard).

### **2. HistorySubmitScore Model**
The HistorySubmitScore model stores the score history for users at each level.
- `user_id` : Foreign key referencing the users table.
- `level` : The level at which the score was submitted.
- `score` : The score submitted for that level.


---

## **How to Use**
### **1. Install Dependencies :**
Run `composer install` to install the necessary dependencies.

### **2. Env Configuration :**
Set up your .env file with your database connection credentials. For the example : 
```env
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=game_leaderboard
DB_USERNAME=laraveluser
DB_PASSWORD=password

REDIS_HOST=localhost
REDIS_PASSWORD=null
REDIS_PORT=6379
CACHE_DRIVER=redis
REDIS_CLIENT=phpredis 
REDIS_PREFIX=laravel_database_
CACHE_PREFIX=laravel_cache_
LEADERBOARD_CACHE_DURATION=300
LEADERBOARD_CACHE_PREFIX=leaderboard_

SECRET_KEY=
API_URL=
```

### **3. Migrate and Seed Database :**
Run the following commands to migrate and seed 10,000 users and their respective score history:
- Migration
```bash
php artisan migrate
```
- init seeder data
```bash
php artisan db:seed
```