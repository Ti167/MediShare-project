# Medi-Share NGO Dashboard Backend - Context

## Overview
This document describes the PHP backend logic for the Medi-Share NGO dashboard, including search functionality, JOIN queries, and transaction handling.

## Files Created

### 1. ngo_dashboard.php
Handles search and display of available medicines:
- Session and role verification (ngo only)
- Search via GET parameter with LIKE clause
- **JOIN Query**: Combines medicines and users tables to fetch donor details (name, address) in a single query
- Double-checks expiry_date > CURDATE() for data integrity
- Displays results in responsive Tailwind CSS cards
- Each card contains a form with hidden medicine_id for requesting

### 2. request_medicine.php
Handles the medicine request transaction:
- Validates medicine is still available and not expired before processing
- **PDO Transaction**: Uses beginTransaction(), commit(), and rollBack()
- Step 1: Inserts request record into requests table
- Step 2: Updates medicine status to 'requested'
- Atomic operation - both succeed or both fail
- Session flash messages for feedback

## Search & Transaction Logic Report

### Why Database Transactions Are Required

Database transactions are essential for maintaining data integrity when requesting a medicine because the operation involves multiple dependent database changes that must succeed together. The request process requires two distinct operations: inserting a new row into the requests table (creating a request record) and simultaneously updating the medicines table (marking the medicine as requested). Without a transaction, a scenario could occur where the request is inserted successfully but the status update fails, leaving the system in an inconsistent state where multiple NGOs might request the same medicine. The transaction ensures atomicity - either both operations complete successfully and the changes are persisted, or if any error occurs, the entire operation is rolled back and the database remains in its original state. This prevents orphaned request records and ensures that the medicine status always accurately reflects whether a pending request exists.

### How JOIN Retrieves Donor Location Efficiently

The JOIN statement in the search query efficiently retrieves donor location information without requiring a second query by combining data from multiple related tables in a single database operation. The query uses `JOIN users u ON m.donor_id = u.user_id` to link each medicine record with its corresponding donor's information from the users table. This allows us to directly select columns like `u.full_name` and `u.address` alongside the medicine data in one result set. Without JOIN, we would need to first fetch the medicine records, then loop through each result and execute additional queries to fetch donor details for each medicine - an N+1 query problem that significantly degrades performance as the number of medicines grows. The JOIN operation is performed by the database engine using indexes on the foreign key (donor_id), making it much faster than multiple separate queries. This approach also ensures data consistency, as the donor information is fetched at the same time as the medicine data, eliminating race conditions where donor details might change between separate queries.
