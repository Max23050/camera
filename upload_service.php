<?php
$conn = new mysqli("localhost", "root", "", "camera_db");

if ($conn->connect_error) {
    die("Error: " . $conn->connect_error);
}

if (isset($_POST["submit"])) {
    $serial_number = $_POST["serial_number"];
    $service_date = $_POST["service_date"];

    $check_sql = "SELECT serial_start, serial_end FROM cameras WHERE ? BETWEEN serial_start AND serial_end";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("i", $serial_number);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $exists_sql = "SELECT pdf_file FROM camera_info WHERE serial_number = ?";
        $stmt = $conn->prepare($exists_sql);
        $stmt->bind_param("i", $serial_number);
        $stmt->execute();
        $result = $stmt->get_result();

        $upload_dir = "uploads/pdfs/";
        $file_path = "";

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if (!empty($row["pdf_file"]) && file_exists($row["pdf_file"])) {
                unlink($row["pdf_file"]);
            }
        }

        if (isset($_FILES["pdf_file"]) && $_FILES["pdf_file"]["error"] == 0) {
            $file_name = time() . "_" . basename($_FILES["pdf_file"]["name"]);
            $file_path = $upload_dir . $file_name;
            move_uploaded_file($_FILES["pdf_file"]["tmp_name"], $file_path);
        }

        if ($result->num_rows > 0) {
            $update_sql = "UPDATE camera_info SET service_date = ?, pdf_file = ? WHERE serial_number = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("ssi", $service_date, $file_path, $serial_number);
            $stmt->execute();
            echo "Service record updated!";
        } else {
            $insert_sql = "INSERT INTO camera_info (serial_number, service_date, pdf_file) 
                           VALUES (?, ?, ?)";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("iss", $serial_number, $service_date, $file_path);
            $stmt->execute();
            echo "New service record added!";
        }
    } else {
        echo "Error: Serial number not found in database.";
    }
}

$conn->close();
?>

<form action="upload_service.php" method="POST" enctype="multipart/form-data">
    <input type="number" name="serial_number" placeholder="Serial number" required>
    <input type="date" name="service_date" required>
    <input type="file" name="pdf_file" accept="application/pdf">
    <button type="submit" name="submit">Add</button>
</form>
