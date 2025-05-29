<?php
include 'db.php';
date_default_timezone_set("Africa/Accra");


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['token'])) {
    $token = $_POST['token'];
    $today = date("Y-m-d");

    // 1. Fetch QR token info
    $stmt = $conn->prepare("SELECT * FROM qr_tokens WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $qr = $stmt->get_result();

    if ($qr->num_rows !== 1) {
        echo "<div class='alert alert-danger'>❌ Invalid QR code</div>";
        exit;
    }

    $qr_row = $qr->fetch_assoc();

    if ($qr_row['status'] !== 'active') {
        echo "<div class='alert alert-danger'>❌ This QR code is no longer active.</div>";
        exit;
    }

    if ((int)$qr_row['usage_count'] >= (int)$qr_row['max_usage']) {
        echo "<div class='alert alert-danger'>❌ QR code has been used up.</div>";
        exit;
    }

    $item = $qr_row['item'];
    $roll = $qr_row['roll_number'];
    $location = $qr_row['location'];

    // 2. If first usage (Time In), ask for tag
    if ((int)$qr_row['usage_count'] === 0) {
        if (!isset($_POST['tag_number'])) {
            echo <<<FORM
                <form method="post">
                    <input type="hidden" name="token" value="{$token}">
                    <label>Enter Tag Number:</label>
                    <input type="text" name="tag_number" required>
                    <button type="submit">Submit</button>
                </form>
            FORM;
            exit;
        }

        $tag_number = trim($_POST['tag_number']);

        // 3. Check if tag exists for this item and is available
        $stmt = $conn->prepare("SELECT * FROM item_tags WHERE tag_code = ? AND item_name = ? AND is_available = 1");
        $stmt->bind_param("ss", $tag_number, $item);
        $stmt->execute();
        $tag_check = $stmt->get_result();

        if ($tag_check->num_rows === 0) {
            echo "<div class='alert alert-danger'>❌ Invalid or already used tag for this item.</div>";
            exit;
        }

        // 4. Check if tag already used today
        $stmt = $conn->prepare("SELECT * FROM attendance WHERE tag_number = ? AND date = ?");
        $stmt->bind_param("ss", $tag_number, $today);
        $stmt->execute();
        $already_used = $stmt->get_result();

        if ($already_used->num_rows > 0) {
            echo "<div class='alert alert-danger'>❌ Tag has already been used today.</div>";
            exit;
        }

        // 5. Record Time In
        $timeIn = date("H:i:s");
        $stmt = $conn->prepare("INSERT INTO attendance (token_id, date, roll_number, location, item, tag_number, time_in, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssssss", $token, $today, $roll, $location, $item, $tag_number, $timeIn);
        $stmt->execute();

        // 6. Update QR code usage
        $stmt = $conn->prepare("UPDATE qr_tokens SET usage_count = usage_count + 1, time_in = ? WHERE token = ?");
        $stmt->bind_param("ss", $timeIn, $token);
        $stmt->execute();

        // 7. Mark tag as unavailable
        $stmt = $conn->prepare("UPDATE items_tags SET is_available = 0 WHERE tag_code = ?");
        $stmt->bind_param("s", $tag_number);
        $stmt->execute();

        echo "<div class='alert alert-success'>✅ Time In recorded for <strong>$roll</strong> at <strong>$timeIn</strong> with tag <strong>$tag_number</strong>.</div>";

    } elseif ((int)$qr_row['usage_count'] === 1) {
        // Time Out Request
        $stmt = $conn->prepare("UPDATE attendance SET time_out_requested = 1 WHERE token_id = ? AND date = ? AND time_out IS NULL");
        $stmt->bind_param("ss", $token, $today);
        $stmt->execute();

        $stmt = $conn->prepare("UPDATE qr_tokens SET usage_count = usage_count + 1, status = 'pending_approval' WHERE token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();

        echo "<div class='alert alert-warning'>⏳ Time Out requested. Awaiting admin approval.</div>";
        exit;
    }
}
?>
