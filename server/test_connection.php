   <?php
   require_once 'config.php';
   
   if ($conn->connect_error) {
       echo "Connection failed: " . $conn->connect_error;
   } else {
       echo "Connected successfully to database: " . DB_NAME;
   }
   $conn->close();
   ?>