<?php
// Enable error logging AND display errors temporarily
error_reporting(E_ALL);
ini_set('display_errors', 1);  // Changed to 1 to see errors
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

require_once 'config.php';

// Start output buffering to catch any stray output
ob_start();

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");

session_start();

// Get the content type of the request
$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';

// If it's a JSON request, decode the input
if (strcasecmp($contentType, 'application/json') == 0) {
    $content = trim(file_get_contents("php://input"));
    $decoded = json_decode($content, true);

    if (!is_array($decoded)) {
        ob_end_clean();
        echo json_encode(["success" => false, "message" => "Invalid JSON"]);
        exit;
    }
} else {
    $decoded = $_POST;
}

// Log all incoming requests
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Request Data: " . print_r($decoded, true));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($decoded['action']) ? $decoded['action'] : null;

    if ($action) {
        if ($action === 'register') {
            $username = $conn->real_escape_string($decoded['username']);
            $email = $conn->real_escape_string($decoded['email']);
            $password = password_hash($decoded['password'], PASSWORD_DEFAULT);

            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM usertblaccounts WHERE email = ?");
            if (!$stmt) {
                error_log("Prepare failed: " . $conn->error);
                ob_end_clean();
                echo json_encode(["success" => false, "message" => "Database error: " . $conn->error]);
                exit;
            }
            
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows > 0) {
                ob_end_clean();
                echo json_encode(["success" => false, "message" => "Email already exists"]);
                exit;
            }
            $stmt->close();

            // Handle profile image as base64
            $profile_image = null;
            if (isset($decoded['profile_image']) && !empty($decoded['profile_image'])) {
                $profile_image = $decoded['profile_image'];
            }

            // Generate verification token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));

            // Start transaction
            $conn->begin_transaction();

            try {
                // Insert user with verified set to 0
                $stmt = $conn->prepare("INSERT INTO usertblaccounts (username, email, password, profile_image, verified) VALUES (?, ?, ?, ?, 0)");
                if (!$stmt) {
                    throw new Exception("Prepare insert failed: " . $conn->error);
                }
                
                $stmt->bind_param("ssss", $username, $email, $password, $profile_image);
                
                if (!$stmt->execute()) {
                    throw new Exception("Execute insert failed: " . $stmt->error);
                }
                
                $userId = $conn->insert_id;
                $stmt->close();

                // Insert verification token
                $stmt = $conn->prepare("INSERT INTO email_verification (user_id, token, expires_at) VALUES (?, ?, ?)");
                if (!$stmt) {
                    throw new Exception("Prepare verification failed: " . $conn->error);
                }
                
                $stmt->bind_param("iss", $userId, $token, $expires);
                
                if (!$stmt->execute()) {
                    throw new Exception("Execute verification failed: " . $stmt->error);
                }
                $stmt->close();

                // Send verification email
                require_once 'includes/email_helper.php';
                if (sendVerificationEmail($email, $token, $username)) {
                    $conn->commit();
                    ob_end_clean();
                    echo json_encode([
                        "success" => true,
                        "message" => "Registration successful! Please check your email to verify your account.",
                        "user_id" => $userId
                    ]);
                } else {
                    throw new Exception("Failed to send verification email");
                }
                
            } catch (Exception $e) {
                $conn->rollback();
                error_log("Registration error: " . $e->getMessage());
                ob_end_clean();
                echo json_encode(["success" => false, "message" => $e->getMessage()]);
            }
        } elseif ($action === 'login') {
            $email = $conn->real_escape_string($decoded['email']);
            $password = $decoded['password'];
            
            $stmt = $conn->prepare("SELECT * FROM usertblaccounts WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($user = $result->fetch_assoc()) {
                if (password_verify($password, $user['password'])) {
                    if (!$user['verified']) {
                        ob_end_clean();
                        echo json_encode([
                            "success" => false, 
                            "message" => "Please verify your email before logging in."
                        ]);
                        exit;
                    }

                    $_SESSION['user_id'] = $user['id'];
                    unset($user['password']);
                    ob_end_clean();
                    echo json_encode([
                        "success" => true, 
                        "user" => $user
                    ]);
                } else {
                    ob_end_clean();
                    echo json_encode([
                        "success" => false, 
                        "message" => "Invalid credentials"
                    ]);
                }
            } else {
                ob_end_clean();
                echo json_encode([
                    "success" => false, 
                    "message" => "Invalid credentials"
                ]);
            }
            $stmt->close();
        } else {
            ob_end_clean();
            echo json_encode(["success" => false, "message" => "Invalid action"]);
        }
    } else {
        ob_end_clean();
        echo json_encode(["success" => false, "message" => "Action not specified"]);
    }
} else {
    ob_end_clean();
    echo json_encode(["success" => false, "message" => "Invalid request method"]);
}