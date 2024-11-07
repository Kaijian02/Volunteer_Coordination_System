<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_FILES['croppedImage'])) {
    if ($_FILES['croppedImage']['error'] === UPLOAD_ERR_OK) {
        // Directory to save the uploaded image
        $userId = $_SESSION['user_id'];
        $uploadDir = 'uploads/' . $userId . '/profile/';
        if (!is_dir('uploads')) {
            mkdir('uploads', 0777, true);
        }
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        // Get the uploaded file info
        $fileTmpPath = $_FILES['croppedImage']['tmp_name'];
        $fileName = $_FILES['croppedImage']['name'];
        $fileSize = $_FILES['croppedImage']['size'];
        $fileType = $_FILES['croppedImage']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        // Sanitize the file name
        $newFileName = md5(time() . $fileName) . '.' . $fileExtension;

        // Define the allowed file extensions
        $allowedfileExtensions = array('jpg', 'gif', 'png', 'jpeg');

        if (in_array($fileExtension, $allowedfileExtensions)) {
            // Directory path to save the uploaded file
            $dest_path = $uploadDir . $newFileName;


            // Move the file to the uploads directory
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                // File is successfully uploaded
                $message = 'File is successfully uploaded.';

                // Save the file path to the database
                $conn = new mysqli("localhost", "root", "", "volunteer_coordination_system");
                if ($conn->connect_error) {
                    die("Connection failed: " . $conn->connect_error);
                }

                // $userId = $_SESSION['user_id'];
                $filePath = $conn->real_escape_string($dest_path);

                $sql = "UPDATE users SET profile_image = '$filePath' WHERE id = $userId";
                if ($conn->query($sql) === TRUE) {
                    $message = 'File is successfully uploaded and saved to the database.';
                } else {
                    $message = 'Error saving file path to the database: ' . $conn->error;
                }

                $conn->close();
            } else {
                $message = 'There was an error moving the uploaded file.';
            }
        } else {
            $message = 'Upload failed. Allowed file types: ' . implode(',', $allowedfileExtensions) . '. File type: ' . $fileExtension;
        }
    } else {
        $message = 'Error code: ' . $_FILES['croppedImage']['error'];
    }
} else {
    $message = 'No file uploaded or there was an error uploading the file.';
}

echo $message;
