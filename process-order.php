<?php 

require 'vendor/autoload.php'; // Load PHPSpreadsheet library
require 'twilio-php-main/src/Twilio/autoload.php'; // Load Twilio library

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
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

// Get form data
$phone = $_POST['phone'];
$order_type = isset($_POST['order_type']) ? implode(", ", $_POST['order_type']) : '';
$grocery_details = isset($_POST['grocery_details']) ? $_POST['grocery_details'] : NULL;
$breakfast = isset($_POST['breakfast']) ? implode(", ", $_POST['breakfast']) : NULL;
$lunch = isset($_POST['lunch']) ? implode(", ", $_POST['lunch']) : NULL;
$dinner = isset($_POST['dinner']) ? implode(", ", $_POST['dinner']) : NULL;
$order_date = $_POST['order_date'] ?? NULL;
$timestamp = date("Y-m-d H:i:s"); // Current timestamp

// File uploads
$medicine_attachment = $_FILES['attachment']['name'] ?? NULL;
$grocery_attachment = $_FILES['grocery_attachment']['name'] ?? NULL;
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

    // Send SMS confirmation with each sentence on a new line
    $confirmationMessage = "Your order has been placed successfully!\n"
                         . "Order Type: $order_type.\n"
                         . "Order Date: $order_date.\n"
                         . "Timestamp: $timestamp.";
    sendSms($phone, $confirmationMessage);
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

// Export to Excel
$sql = "SELECT * FROM orders";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    $headers = ['ID', 'Phone', 'Order Type', 'Grocery Details', 'Medicine Attachment', 'Grocery Attachment', 'Breakfast', 'Lunch', 'Dinner', 'Order Date', 'Timestamp'];
    $columnIndex = 1;
    foreach ($headers as $header) {
        $sheet->setCellValueByColumnAndRow($columnIndex++, 1, $header);
    }

    $rowIndex = 2; 
    while ($row = $result->fetch_assoc()) {
        $columnIndex = 1;
        foreach ($row as $value) {
            $sheet->setCellValueByColumnAndRow($columnIndex++, $rowIndex, $value);
        }
        $rowIndex++;
    }

    $filename = 'EzEssentials.xlsx';
    $writer = new Xlsx($spreadsheet);
    $writer->save($filename);
    echo "Excel file created successfully: <a href='$filename'>Download</a>";
} else {
    echo "No data found!";
}

// Function to send SMS
function sendSms($phone_number, $message_body) {
    $sid = "ACe0d3578fb2c0b39d4eeeb0b05d3b9c68";
    $token = "702d645598d9a3a9f24bbe6c292d6440";
    $client = new Client($sid, $token);
    
    try {
        $client->messages->create(
            $phone_number,
            [
                'from' => '+16502004823',
                'body' => $message_body
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
