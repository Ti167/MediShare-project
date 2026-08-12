# Medi-Share Donor Dashboard Backend - Context

## Overview
This document describes the PHP backend integration for the Medi-Share donor dashboard, including form processing and database retrieval.

## Files Created

### 1. add_medicine.php
Handles the POST request from the "Add New Medicine" form:
- Validates user session (must be logged in as donor)
- Validates all input fields are present and valid
- **Date Validation**: Parses expiry_date using DateTime and compares with today's date
- Uses PDO prepared statement with bound parameters
- Sets session flash messages for success/error feedback
- Redirects back to donor_dashboard.php

### 2. donor_dashboard.php
Complete dashboard with:
- Session security check (role verification)
- Database fetch using prepared statement filtered by $_SESSION['user_id']
- Statistics calculation (total, successful, pending)
- Dynamic table rendering with PHP foreach loop
- Conditional status badges with Tailwind CSS classes
- Expiry date highlighting (red for expired/expiring soon)
- Session flash message display

## Backend Integration Report

### PHP Date Validation for Expired Medicines

The validation in add_medicine.php uses PHP's DateTime class for robust date comparison. The code creates two DateTime objects: `$today` set to 'today' (midnight) and `$expiry` parsed from the form input. The condition `$expiry <= $today` ensures that only dates strictly greater than the current date are accepted. This prevents listing medicines that are either already expired or expire today, as both would be unreliable for donation. Using DateTime rather than string comparison is more reliable because it handles various date formats and accounts for timezone differences automatically. The validation occurs server-side, so users cannot bypass it by manipulating client-side JavaScript.

### Fetching Data via $_SESSION['user_id']

Fetching medicines using `$_SESSION['user_id']` in the WHERE clause provides essential security and data isolation. Since this value is set server-side during login (after password verification), it cannot be manipulated by users through URL parameters or form inputs. The prepared statement `WHERE donor_id = ?` ensures that only the authenticated donor can access their own inventory - the database query itself enforces this isolation at the data layer. This prevents horizontal privilege escalation where one donor could view or modify another donor's medicines. Even if a malicious user tries to inject different values through the session, PHP's session management makes this impossible since `$_SESSION` is server-controlled. This approach also ensures that each donor sees only their relevant data, creating a personalized dashboard experience.
