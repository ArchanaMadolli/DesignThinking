<?php
include('sms-api.php'); // Include the SMS API
require 'vendor/autoload.php'; // Load PHPSpreadsheet library

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Database connection
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'EzEssentials';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get form data and process checkboxes
$phone = $_POST['phone'];
$order_type = isset($_POST['order_type']) ? implode(", ", $_POST['order_type']) : '';
$grocery_details = isset($_POST['grocery_details']) ? $_POST['grocery_details'] : NULL;

// File Upload Handling
$medicine_attachment = isset($_FILES['attachment']['name']) ? $_FILES['attachment']['name'] : NULL;
$grocery_attachment = isset($_FILES['grocery_attachment']['name']) ? $_FILES['grocery_attachment']['name'] : NULL;
if (!empty($medicine_attachment)) {
    move_uploaded_file($_FILES['attachment']['tmp_name'], "uploads/" . $medicine_attachment);
}
if (!empty($grocery_attachment)) {
    move_uploaded_file($_FILES['grocery_attachment']['tmp_name'], "uploads/" . $grocery_attachment);
}

// Checkbox arrays for breakfast, lunch, and dinner
$breakfast = isset($_POST['breakfast']) ? implode(", ", $_POST['breakfast']) : NULL;
$lunch = isset($_POST['lunch']) ? implode(", ", $_POST['lunch']) : NULL;
$dinner = isset($_POST['dinner']) ? implode(", ", $_POST['dinner']) : NULL;

// Insert data into the database
$sql = "INSERT INTO orders (phone, order_type, grocery_details, medicine_attachment, grocery_attachment, breakfast, lunch, dinner)
        VALUES ('$phone', '$order_type', '$grocery_details', '$medicine_attachment', '$grocery_attachment', '$breakfast', '$lunch', '$dinner')";

if ($conn->query($sql) === TRUE) {
    // Send SMS after successful order placement
    $message = "Jai Sri Ram! Your order has been placed successfully. Order details: \nPhone: $phone\nOrder Type: $order_type";
    
    // Call the sendSms function from sms-api.php
    sendSms($phone, $message);

    echo "Order placed successfully!";
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
    $headers = array('ID', 'Phone', 'Order Type', 'Grocery Details', 'Medicine Attachment', 'Grocery Attachment', 'Breakfast', 'Lunch', 'Dinner');
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

// Close the database connection
$conn->close();
?>
