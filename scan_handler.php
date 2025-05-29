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
            // Show tag input form
            echo <<<FORM
                <div class="alert alert-success">✅ QR Code scanned successfully. Please enter your tag number.</div>
                <form method="post" onsubmit="submitTag(event)">
                    <input type="hidden" name="token" value="{$token}">
                    <label>Enter Tag Number:</label>
                    <input type="text" name="tag_number" id="tag_number" required>
                    <button type="submit">Submit Tag</button>
                </form>
                <script>
                    function submitTag(event) {
                        event.preventDefault();
                        const tag = document.getElementById('tag_number').value;
                        const token = "{$token}";

                        fetch('scan_handler.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: 'token=' + encodeURIComponent(token) + '&tag_number=' + encodeURIComponent(tag)
                        })
                        .then(response => response.text())
                        .then(data => {
                            document.getElementById('result').innerHTML = data;
                        });
                    }
                </script>
            FORM;
            exit;
        }

        $tag_number = trim($_POST['tag_number']);

        // 3. Check if tag exists and is available for this item
        $stmt = $conn->prepare("SELECT * FROM item_tags WHERE tag_code = ? AND item_name = ? AND is_available = 1");
        $stmt->bind_param("ss", $tag_number, $item);
        $stmt->execute();
        $tag_check = $stmt->get_result();

        if ($tag_check->num_rows === 0) {
            echo "<div class='alert alert-danger'>❌ Invalid tag or not available for this item.</div>";
            exit;
        }

        // 4. Check if tag already used today
        $stmt = $conn->prepare("SELECT * FROM attendance WHERE tag_number = ? AND date = ?");
        $stmt->bind_param("ss", $tag_number, $today);
        $stmt->execute();
        $already_used = $stmt->get_result();

        if ($already_used->num_rows > 0) {
            echo "<div class='alert alert-danger'>❌ Tag code already used today.</div>";
            exit;
        }

        // 5. Record Time In
        $timeIn = date("H:i:s");
        $stmt = $conn->prepare("INSERT INTO attendance (token_id, date, roll_number, location, item, tag_number, time_in, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssssss", $token, $today, $roll, $location, $item, $tag_number, $timeIn);
        $stmt->execute();

        // 6. Update QR token
        $stmt = $conn->prepare("UPDATE qr_tokens SET usage_count = usage_count + 1, time_in = ? WHERE token = ?");
        $stmt->bind_param("ss", $timeIn, $token);
        $stmt->execute();

        // 7. Mark tag unavailable
        $stmt = $conn->prepare("UPDATE item_tags SET is_available = 0 WHERE tag_code = ?");
        $stmt->bind_param("s", $tag_number);
        $stmt->execute();

        echo "<div class='alert alert-success'>✅ Time In recorded for <strong>$roll</strong> at <strong>$timeIn</strong> with tag <strong>$tag_number</strong>.</div>";
        echo "<script>setTimeout(() => startScanner(), 2000);</script>"; // restart scanner
        exit;
    }

    // 8. Time Out flow
    if ((int)$qr_row['usage_count'] === 1) {
        $stmt = $conn->prepare("UPDATE attendance SET time_out_requested = 1 WHERE token_id = ? AND date = ? AND time_out IS NULL");
        $stmt->bind_param("ss", $token, $today);
        $stmt->execute();

        $stmt = $conn->prepare("UPDATE qr_tokens SET usage_count = usage_count + 1, status = 'pending_approval' WHERE token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();

        echo "<div class='alert alert-warning'>⏳ Time Out requested. Awaiting admin approval.</div>";
        echo "<script>setTimeout(() => startScanner(), 3000);</script>"; // restart scanner
        exit;
    }
}
?>
