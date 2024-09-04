<?php

header("Content-Type: application/json");

// Set the default timezone to Kuala Lumpur, Malaysia
date_default_timezone_set('Asia/Kuala_Lumpur');

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
            $sql = "SELECT * FROM student_checkin_checkout WHERE matrix_number = '$matrix_number' ORDER BY checkin_time DESC LIMIT 1";
            $result = $conn->query($sql);
            
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                
                if (!empty($row['checkin_time']) && !is_null($row['checkout_time'])) {
                    // Insert a new record if both check-in and check-out are recorded
                    if (isset($_POST['name']) && isset($_POST['room_number'])) {
                        $name = $conn->real_escape_string($_POST['name']);
                        $room_number = $conn->real_escape_string($_POST['room_number']);
                        $checkin_time = date('Y-m-d H:i:s'); // Set new check-in time
                        
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
                        
                } elseif (is_null($row['checkout_time'])) {
                    // Update check-out time if check-in exists but check-out is empty
                    $checkout_time = date('Y-m-d H:i:s');
                    $update_sql = "UPDATE student_checkin_checkout 
                                   SET checkout_time = '$checkout_time'
                                   WHERE matrix_number = '$matrix_number' AND checkin_time = '{$row['checkin_time']}'";
                    if ($conn->query($update_sql) === TRUE) {
                        echo json_encode(array("status" => "success", "message" => "Check-out time updated successfully"));
                    } else {
                        echo json_encode(array("status" => "error", "message" => "Error: " . $conn->error));
                    }
                } else {
                    echo json_encode(array("status" => "error", "message" => "Unexpected condition met"));
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
