<?php

require 'vendor/autoload.php'; // Load PHPSpreadsheet library
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Optionally, load the Twilio SDK if you're using Twilio for SMS (install via composer)
// require 'path/to/vendor/autoload.php'; // Twilio
// use Twilio\Rest\Client; 

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

// Send SMS confirmation
$confirmationMessage = "Your order has been placed successfully!\n"
                     . "Order Type: $order_type.\n"
                     . "Order Date: $order_date.\n"
                     . "Timestamp: $timestamp.";
sendSms($phone, $confirmationMessage);
// You can use an SMS API to send this message
// Here's an example using Twilio (if you have Twilio installed):
// $sid = 'your_twilio_sid';
// $token = 'your_twilio_auth_token';
// $twilio = new Client($sid, $token);
// $message = $twilio->messages->create(
//     $phone, // Send to this phone number
//     array(
//         'from' => 'your_twilio_number',
//         'body' => $confirmationMessage
//     )
// );

// Example if using another SMS API
// sendSMS($phone, $confirmationMessage);

echo "Confirmation message sent to $phone.";

// Close the database connection
$conn->close();
?>
