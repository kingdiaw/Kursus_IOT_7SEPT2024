# IoT Project: Student Hostel Check-in/Check-out Monitoring
## Project Overview
Your role as a software engineer is to build an IoT project for monitoring student check-ins and check-outs at a hostel. The system will record the following information:

- **Name**
- **Matrix Number**
- **Room Number**
- **Date**
- **Timestamp**

**Hardware**: Key room's RFID card, ESP8266

**Database**: Using phpMyAdmin (testing by using XAMPP)
### Project Phases
The project will be divided into 3 phases:
1. Database Phase
2. API Phase
3. ESP8266 Phase

## Phase 1: Database Preparation
1. Open **phpMyAdmin** and create a database as follows:
**Database Creation**
The script checks if the `hostel_management` database exists and creates it if it doesn't.
```sql
-- Create the database if it doesn't exist
CREATE DATABASE IF NOT EXISTS hostel_management;

-- Use the created database
USE hostel_management;
```

**Table Creation**
- **student_checkin_checkout**: Stores check-in/check-out records with `matrix_number` as the primary key.
- **student_data**: Stores student information with `matrix_number` as the primary key.

```sql
-- Create a table to store student check-in/check-out records
CREATE TABLE IF NOT EXISTS student_checkin_checkout (
    matrix_number VARCHAR(20) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    room_number VARCHAR(10) NOT NULL,
    checkin_time DATETIME NOT NULL,
    checkout_time DATETIME
);

-- Optional: Insert some dummy data for testing purposes
INSERT INTO student_checkin_checkout (matrix_number, name, room_number, checkin_time)
VALUES ('18DTK23F1001', 'John Doe', 'A101', NOW());

-- Create the student_data table if it doesn't exist
CREATE TABLE IF NOT EXISTS student_data (
    matrix_number VARCHAR(20) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    room_number VARCHAR(10) NOT NULL
);

-- Sample insert for testing
INSERT INTO student_data (matrix_number, name, room_number)
VALUES 
('18DTK23F1001', 'John Doe', 'A101'),
('18DEP23F1045', 'Lucy Brown', 'B105'),
('18DEO22F1088', 'Emma Watson', 'B106');
```
## Phase 2: PHP Script for API
Create a PHP script in the `htdocs` directory with the following logic:
**GET Method**
1. Check for `matrix_number`:
   - The script checks if a `matrix_number` is provided in the query string `($_GET['matrix_number'])`.
   - If provided, it retrieves the specific student record from the `student_data` table.
2. Retrieve Specific Record:
   - If a matching record is found, the data is returned as a JSON response.
   - If no matching record is found, an error message is returned.
3. Error Message:
   - If `matrix_number` is not provided, the script returns an error message: `"Matrix number not provided."`

**POST Method**
1. Check for Existing Record:
   - The script first checks if a record with the given `matrix_number` exists.
2. Check-in/Check-out Logic:
   - **Check-in Time**: If `checkin_time` is empty, it updates the `checkin_time` with the current timestamp.
   - **Check-out Time**: If `checkin_time` is set and `checkout_time` is empty, it updates the `checkout_time` with the current timestamp.
3. Insert New Record:
- If the record does not exist, a new record is inserted with the provided `name`, `matrix_number`, `room_number`, and `checkin_time`.
4. Unsupported Method:
  - If a method other than GET or POST is used, an error message is returned indicating that the method is unsupported.

```php
<?php

header("Content-Type: application/json");

// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "hostel_management";

// Create connection to the hostel_management database
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(array("status" => "error", "message" => "Connection failed: " . $conn->connect_error)));
}

// Get the HTTP method (GET or POST)
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['matrix_number'])) {
            $matrix_number = $conn->real_escape_string($_GET['matrix_number']);

            // Query to get a specific student record from student_data
            $sql = "SELECT * FROM student_data WHERE matrix_number = '$matrix_number'";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                $student_data = $result->fetch_assoc();
                echo json_encode($student_data);
            } else {
                echo json_encode(array("status" => "error", "message" => "Record not found for the provided matrix number"));
            }
        } else {
            // Return an error message if matrix_number is not provided
            echo json_encode(array("status" => "error", "message" => "Matrix number not provided"));
        }
        break;

    case 'POST':
        if (isset($_POST['matrix_number'])) {
            $matrix_number = $conn->real_escape_string($_POST['matrix_number']);

            // Check if the record for this matrix number exists
            $sql = "SELECT * FROM student_checkin_checkout WHERE matrix_number = '$matrix_number'";
            $result = $conn->query($sql);
            
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                if (empty($row['checkin_time'])) {
                    // Update check-in time if it's empty
                    $checkin_time = date('Y-m-d H:i:s');
                    $update_sql = "UPDATE student_checkin_checkout 
                                   SET checkin_time = '$checkin_time'
                                   WHERE matrix_number = '$matrix_number'";
                    if ($conn->query($update_sql) === TRUE) {
                        echo json_encode(array("status" => "success", "message" => "Check-in time updated successfully"));
                    } else {
                        echo json_encode(array("status" => "error", "message" => "Error: " . $conn->error));
                    }
                } elseif (!empty($row['checkin_time']) && empty($row['checkout_time'])) {
                    // Update check-out time if check-in exists but check-out is empty
                    $checkout_time = date('Y-m-d H:i:s');
                    $update_sql = "UPDATE student_checkin_checkout 
                                   SET checkout_time = '$checkout_time'
                                   WHERE matrix_number = '$matrix_number'";
                    if ($conn->query($update_sql) === TRUE) {
                        echo json_encode(array("status" => "success", "message" => "Check-out time updated successfully"));
                    } else {
                        echo json_encode(array("status" => "error", "message" => "Error: " . $conn->error));
                    }
                } else {
                    echo json_encode(array("status" => "error", "message" => "Check-out time already recorded"));
                }
            } else {
                // Insert new record if none exists
                if (isset($_POST['name']) && isset($_POST['room_number'])) {
                    $name = $conn->real_escape_string($_POST['name']);
                    $room_number = $conn->real_escape_string($_POST['room_number']);
                    $checkin_time = date('Y-m-d H:i:s'); // Set check-in time
                    
                    $insert_sql = "INSERT INTO student_checkin_checkout (name, matrix_number, room_number, checkin_time)
                                   VALUES ('$name', '$matrix_number', '$room_number', '$checkin_time')";

                    if ($conn->query($insert_sql) === TRUE) {
                        echo json_encode(array("status" => "success", "message" => "New record created and check-in time set"));
                    } else {
                        echo json_encode(array("status" => "error", "message" => "Error: " . $conn->error));
                    }
                } else {
                    echo json_encode(array("status" => "error", "message" => "Name or room number not provided"));
                }
            }
        } else {
            echo json_encode(array("status" => "error", "message" => "Matrix number not provided"));
        }
        break;

    default:
        echo json_encode(array("status" => "error", "message" => "Unsupported HTTP method"));
        break;
}

// Close the database connection
$conn->close();

?>
```
## Phase 3: Arduino Code for ESP8266
Write an Arduino code for the ESP8266 that reads the RFID card's unique ID and sends it to the database through the provided API.

**Required Libraries**
- MFRC522: For interfacing with the RFID module.
- WiFi: To connect to the WiFi network.
- HTTPClient: To make HTTP requests to the API.

You can install these libraries via the Arduino Library Manager.
**Arduino Code**
```cpp
#include <WiFi.h>
#include <HTTPClient.h>
#include <MFRC522.h>
#include <SPI.h>

// Replace these with your network credentials
const char* ssid = "your_SSID";
const char* password = "your_PASSWORD";

// RFID setup
#define RST_PIN 9
#define SS_PIN 10
MFRC522 rfid(SS_PIN, RST_PIN);

void setup() {
  Serial.begin(115200);
  SPI.begin();
  rfid.PCD_Init();

  // Connect to Wi-Fi
  WiFi.begin(ssid, password);
  while (WiFi.status() != WL_CONNECTED) {
    delay(1000);
    Serial.println("Connecting to WiFi...");
  }
  Serial.println("Connected to WiFi");
}

void loop() {
  if (rfid.PICC_IsNewCardPresent() && rfid.PICC_ReadCardSerial()) {
    String matrix_number = "";
    for (byte i = 0; i < rfid.uid.size; i++) {
      matrix_number += String(rfid.uid.uidByte[i], HEX);
    }

    // Send matrix number to server
    if (WiFi.status() == WL_CONNECTED) {
      HTTPClient http;
      http.begin("http://your-server-ip-address/your-php-script.php?matrix_number=" + matrix_number);
      
      int httpResponseCode = http.GET();
      if (httpResponseCode > 0) {
        String response = http.getString();
        Serial.println("Server Response: " + response);
      } else {
        Serial.println("Error in sending request");
      }
      http.end();
    }
    
    // Halt PICC to prevent repeated reads
    rfid.PICC_HaltA();
  }
  delay(1000);
}
```
You can copy and paste this code into your respective project files and start implementing your project.
