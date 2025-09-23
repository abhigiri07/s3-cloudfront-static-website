<?php
// ============================
// DB CONFIGURATION
// ============================
$host = "localhost";
$user = "your-db-user";
$pass = "your-db-password";
$dbname = "facebook";

// Connect to MySQL
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("MySQL Connection failed: " . $conn->connect_error);
}

// ============================
// AWS CONFIGURATION
// ============================
$bucketName = "your-s3-bucket";
$cloudfrontDomain = "https://your-cloudfront-domain.cloudfront.net";

require 'vendor/autoload.php';
use Aws\S3\S3Client;

// Initialize S3 Client
$s3 = new S3Client([
    'version' => 'latest',
    'region'  => 'us-east-1', // Change to your AWS region
    'credentials' => [
        'key'    => 'your-aws-access-key',
        'secret' => 'your-aws-secret-key',
    ]
]);

// ============================
// FILE UPLOAD HANDLER
// ============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $fileName = basename($_FILES['image']['name']);
    $fileTmp = $_FILES['image']['tmp_name'];

    try {
        // Upload to S3
        $result = $s3->putObject([
            'Bucket' => $bucketName,
            'Key'    => $fileName,
            'SourceFile' => $fileTmp,
            'ACL'    => 'public-read'
        ]);

        $s3Url = $result['ObjectURL'];
        $cloudfrontUrl = $cloudfrontDomain . '/' . $fileName;

        // Save into MySQL
        $stmt = $conn->prepare("INSERT INTO images (image_name, s3_url, cloudfront_url) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $fileName, $s3Url, $cloudfrontUrl);
        $stmt->execute();

        echo "<h2>✅ Upload Successful!</h2>";
        echo "<p>S3 URL: <a href='$s3Url' target='_blank'>$s3Url</a></p>";
        echo "<p>CloudFront URL: <a href='$cloudfrontUrl' target='_blank'>$cloudfrontUrl</a></p>";

    } catch (Exception $e) {
        echo "❌ Upload failed: " . $e->getMessage();
    }
}
?>
