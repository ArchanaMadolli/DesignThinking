<?php

require 'vendor/autoload.php'; // Load PHPSpreadsheet library
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Twilio library
require 'twilio-php-main/src/Twilio/autoload.php';
use Twilio\Rest\Client;

// Database connection
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'EzEssentials';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get form data and insert it into the database
$phone = $_POST['phone'];
$order_type = implode(", ", $_POST['order_type']);
$grocery_details = isset($_POST['grocery_details']) ? $_POST['grocery_details'] : NULL;
$medicine_attachment = isset($_FILES['attachment']['name']) ? $_FILES['attachment']['name'] : NULL;
$grocery_attachment = isset($_FILES['grocery_attachment']['name']) ? $_FILES['grocery_attachment']['name'] : NULL;
$breakfast = isset($_POST['breakfast']) ? implode(", ", $_POST['breakfast']) : NULL;
$lunch = isset($_POST['lunch']) ? implode(", ", $_POST['lunch']) : NULL;
$dinner = isset($_POST['dinner']) ? implode(", ", $_POST['dinner']) : NULL;
$order_date = isset($_POST['order_date']) ? $_POST['order_date'] : NULL;
$timestamp = date("Y-m-d H:i:s"); // Get current timestamp

// Upload files if any
if (!empty($medicine_attachment)) {
    move_uploaded_file($_FILES['attachment']['tmp_name'], "uploads/" . $medicine_attachment);
}
if (!empty($grocery_attachment)) {
    move_uploaded_file($_FILES['grocery_attachment']['tmp_name'], "uploads/" . $grocery_attachment);
}

// Insert data into the database
$sql = "INSERT INTO orders (phone, order_type, grocery_details, medicine_attachment, grocery_attachment, breakfast, lunch, dinner, order_date, timestamp)
        VALUES ('$phone', '$order_type', '$grocery_details', '$medicine_attachment', '$grocery_attachment', '$breakfast', '$lunch', '$dinner', '$order_date', '$timestamp')";

if ($conn->query($sql) === TRUE) {
    echo "Order placed successfully!";
    
    // Prepare the confirmation message
    $confirmationMessage = "Your order has been placed successfully! Order Type: $order_type. Order Date: $order_date. Timestamp: $timestamp.";

    // Send SMS
    sendSms($phone, $confirmationMessage);
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

// Now fetch the data from the `orders` table and export to Excel
$sql = "SELECT * FROM orders";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Create a new Spreadsheet object
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set column headers in Excel file (optional)
    $headers = array('ID', 'Phone', 'Order Type', 'Grocery Details', 'Medicine Attachment', 'Grocery Attachment', 'Breakfast', 'Lunch', 'Dinner', 'Order Date', 'Timestamp');
    $columnIndex = 1;
    foreach ($headers as $header) {
        $sheet->setCellValueByColumnAndRow($columnIndex++, 1, $header);
    }

    // Fetch rows and write them to the spreadsheet
    $rowIndex = 2; // Start writing data from the second row
    while ($row = $result->fetch_assoc()) {
        $columnIndex = 1;
        foreach ($row as $value) {
            $sheet->setCellValueByColumnAndRow($columnIndex++, $rowIndex, $value);
        }
        $rowIndex++;
    }

    // Save the file as Excel .xlsx
    $filename = 'EzEssentials.xlsx';
    $writer = new Xlsx($spreadsheet);
    $writer->save($filename);

    echo "Excel file created successfully: <a href='$filename'>Download</a>";
} else {
    echo "No data found!";
}

// SMS function to send confirmation message
function sendSms($phone_number, $message_body) {
    // Your Account SID and Auth Token from console.twilio.com
    $sid = "ACe0d3578fb2c0b39d4eeeb0b05d3b9c68";
    $token = "702d645598d9a3a9f24bbe6c292d6440";
    $client = new Client($sid, $token);

    try {
        // Use the Client to make requests to the Twilio REST API
        $client->messages->create(
            $phone_number, // The number you'd like to send the message to
            [
                'from' => '+16502004823', // A Twilio phone number you purchased
                'body' => $message_body // The body of the text message
            ]
        );
        echo "SMS sent successfully!";
    } catch (Exception $e) {
        echo "Failed to send SMS: " . $e->getMessage();
    }
}

// Close the database connection
$conn->close();
?>
