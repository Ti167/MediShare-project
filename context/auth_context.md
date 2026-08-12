# Medi-Share Authentication System - Context

## Overview
This document describes the PHP authentication system created for the Medi-Share project.

## Files Created

### 1. db.php
- Database connection using PDO (PHP Data Objects)
- Enables exception mode for error handling
- Uses UTF-8 charset for internationalization support
- Connects to `medishare_db` database

### 2. register.php
- User registration with validation
- Password hashing using `password_hash()` with PASSWORD_DEFAULT
- Prepared statements to prevent SQL injection
- Duplicate email prevention
- Basic HTML form for testing

### 3. login.php
- Email/password authentication
- Password verification using `password_verify()`
- Session management with `session_regenerate_id(true)`
- Role-based redirection:
  - donor → donor_dashboard.php
  - ngo → ngo_dashboard.php
  - admin → admin_dashboard.php

### 4. logout.php
- Complete session destruction
- Redirects to login.php

## Security Features

1. **PDO Prepared Statements**: Prevents SQL injection attacks
2. **password_hash()**: Uses bcrypt algorithm for secure password storage
3. **session_regenerate_id(true)**: Prevents session fixation/hijacking
4. **Session-based authentication**: Server-side session management

## Technical Security Report

### Why password_hash() Instead of MD5?
MD5 is a cryptographic hash function that is now considered cryptographically broken. It produces a 128-bit hash value and can be cracked using rainbow tables or brute-force attacks in seconds. Modern hashing algorithms like bcrypt (used by password_hash() with PASSWORD_DEFAULT) incorporate salting automatically, making each hash unique even for identical passwords. Additionally, bcrypt includes a cost factor that slows down brute-force attacks, making it computationally expensive for attackers. PHP's password_hash() also includes the algorithm and cost in the hash string itself, allowing future algorithm upgrades without invalidating stored passwords.

### How session_regenerate_id(true) Prevents Session Hijacking?
Session hijacking occurs when an attacker steals a user's session ID to impersonate them. The function session_regenerate_id(true) generates a new session ID while simultaneously destroying the old session data. When called with `true` as the parameter, it deletes the old session file from the server. This prevents session fixation attacks where an attacker sets a known session ID before the user logs in. By regenerating the ID immediately after successful authentication, the attacker's known session ID becomes invalid, and the legitimate user receives a new, cryptographically random session ID. This ensures that only the authenticated user possesses the valid session identifier.
