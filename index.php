<!DOCTYPE html>
<html>
<head>
    <title>Simple APK Builder</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        input[type="text"], input[type="file"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
        .error {
            color: red;
            margin-bottom: 10px;
        }
        .success {
            color: green;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <h1>Simple APK Builder</h1>
    <?php
    require_once('generate_apk.php');

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $targetDir = "uploads/";
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $apkName = $_POST["apk_name"];
        $uploadOk = 1;
        $imageFileType = strtolower(pathinfo($_FILES["apk_logo"]["name"], PATHINFO_EXTENSION));
        
        // Check if image file is actual image
        if(isset($_FILES["apk_logo"])) {
            $check = getimagesize($_FILES["apk_logo"]["tmp_name"]);
            if($check === false) {
                echo "<div class='error'>File is not an image.</div>";
                $uploadOk = 0;
            }
        }
        
        // Check file size (5MB max)
        if ($_FILES["apk_logo"]["size"] > 5000000) {
            echo "<div class='error'>Sorry, your file is too large (max 5MB).</div>";
            $uploadOk = 0;
        }
        
        // Allow certain file formats
        if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg") {
            echo "<div class='error'>Sorry, only JPG, JPEG & PNG files are allowed.</div>";
            $uploadOk = 0;
        }

        if ($uploadOk == 1) {
            $targetFile = $targetDir . basename($_FILES["apk_logo"]["name"]);
            if (move_uploaded_file($_FILES["apk_logo"]["tmp_name"], $targetFile)) {
                $result = generateApk($apkName, $targetFile);
                
                if ($result['success']) {
                    $apkFileName = basename($result['apk_path']);
                    echo "<div class='success'>" . $result['message'] . "</div>";
                    echo "<div class='success'>Download your APK: <a href='" . $result['apk_path'] . "'>" . $apkFileName . "</a></div>";
                } else {
                    echo "<div class='error'>" . $result['message'] . "</div>";
                }
            } else {
                echo "<div class='error'>Sorry, there was an error uploading your file.</div>";
            }
        }
    }
    ?>
    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="apk_name">APK Name:</label>
            <input type="text" id="apk_name" name="apk_name" required pattern="[a-zA-Z0-9\s]+" title="Only letters, numbers and spaces allowed">
        </div>
        <div class="form-group">
            <label for="apk_logo">APK Logo (JPG, PNG):</label>
            <input type="file" id="apk_logo" name="apk_logo" accept="image/jpeg,image/png" required>
        </div>
        <button type="submit">Generate APK</button>
    </form>
</body>
</html>
