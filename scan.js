const scanner = new Html5Qrcode("reader");
let scannedRollNumber = "";

if (typeof scanMode === "undefined") {
  alert(
    "❌ 'scanMode' not defined. Please set scanMode = 'in' or 'out' in your HTML."
  );
  throw new Error("Missing scanMode.");
}

const DOM = {
  status: document.getElementById("status-message"),
  result: document.getElementById("result"),
};

function updateStatus(message) {
  DOM.status.textContent = message;
}

function showAlert(type, message) {
  DOM.result.innerHTML = `<div class="alert alert-${type} text-center">${message}</div>`;
}

async function verifyAndAutoSelectItem(tag) {
  try {
    const res = await fetch("verify_tag.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `tag=${encodeURIComponent(tag)}`,
    });
    const data = await res.json();
    if (data.valid && data.item) {
      const itemSelect = document.getElementById("item");
      const options = itemSelect.options;
      for (let i = 0; i < options.length; i++) {
        if (options[i].value === data.item) {
          options[i].selected = true;
          break;
        }
      }
      return true;
    } else {
      alert("❌ Invalid tag or unavailable.");
      return false;
    }
  } catch {
    alert("❌ Error verifying tag. Please try again.");
    return false;
  }
}

async function handleQRCodeScan(qrCode) {
  console.log("QR Code detected:", qrCode);
  await scanner.stop();
  scannedRollNumber = qrCode.trim();
  updateStatus("User 👤 Verified ✅");

  try {
    const res = await fetch("scan_handler.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `roll_number=${encodeURIComponent(
        scannedRollNumber
      )}&mode=${encodeURIComponent(scanMode)}`,
    });

    const result = await res.json();
    const displayName = result.student?.name || "Unknown";
    const displayRoll = result.student?.roll_number || scannedRollNumber;

    if (scanMode === "in") {
      if (result.status !== "require_inputs") {
        showAlert("danger", result.message);
        updateStatus("📷 Looking for QR code...");
        setTimeout(startScanner, 3000);
        return;
      }

      DOM.result.innerHTML = `
        <div class="alert alert-success text-center mb-3">
          👤 <strong>${displayName}</strong> - ${displayRoll}. Proceed with Time In.
        </div>
        <form id="time-form">
          <label for="item">Select Item:</label>
          <select id="item" name="item" class="form-select mb-2" required>
            <option value="">-- Select Item --</option>
          </select>

          <label for="tag">Enter or Scan Tag:</label>
          <input type="text" id="tag" name="tag" class="form-control mb-2" required autocomplete="off">
          <div id="tag-reader" class="mt-3 border rounded shadow-sm" style="max-width: 300px; margin: 0 auto;"></div>
          <div class="text-muted text-center mt-1" style="font-size: 0.9rem;">Or scan tag QR code</div>

          <label for="location">Select Location:</label>
          <select id="location" name="location" class="form-select mb-2" required>
            <option value="">-- Select Location --</option>
          </select>

          <button type="submit" class="btn btn-primary w-100 mt-2">Record Time In</button>
        </form>
      `;

      const itemSelect = document.getElementById("item");
      result.items.forEach((item) => {
        itemSelect.innerHTML += `<option value="${item}">${item}</option>`;
      });

      const locationSelect = document.getElementById("location");
      result.locations.forEach((loc) => {
        locationSelect.innerHTML += `<option value="${loc}">${loc}</option>`;
      });

      const tagInput = document.getElementById("tag");
      const tagScanner = new Html5Qrcode("tag-reader");

      tagScanner.start(
        { facingMode: "environment" },
        { fps: 10, qrbox: 200 },
        async (code) => {
          const tag = code.trim();
          tagInput.value = tag;
          const valid = await verifyAndAutoSelectItem(tag);
          if (valid) await tagScanner.stop();
        }
      );

      tagInput.addEventListener("blur", async () => {
        const tag = tagInput.value.trim();
        if (tag) {
          await verifyAndAutoSelectItem(tag);
        }
      });

      document
        .getElementById("time-form")
        .addEventListener("submit", async (e) => {
          e.preventDefault();

          const formData = new URLSearchParams({
            roll_number: displayRoll,
            item: itemSelect.value,
            tag_number: tagInput.value.trim(),
            location: locationSelect.value,
            name: result.student?.name || "",
            email: result.student?.email || "",
            phone: result.student?.phone || "",
          });

          try {
            const submitRes = await fetch("time_in_handler.php", {
              method: "POST",
              headers: { "Content-Type": "application/x-www-form-urlencoded" },
              body: formData.toString(),
            });
            const data = await submitRes.json();
            showAlert(
              data.status === "success" ? "success" : "danger",
              data.message
            );

            if (data.status === "success" && data.redirect) {
              setTimeout(() => (window.location.href = data.redirect), 2000);
            }
          } catch {
            showAlert("danger", "❌ Network error.");
          }

          updateStatus("📷 Looking for QR code...");
          setTimeout(startScanner, 5000);
        });
    } else if (scanMode === "out") {
      if (result.status !== "ready_for_timeout") {
        showAlert("danger", result.message);
        updateStatus("📷 Looking for QR code...");
        setTimeout(startScanner, 3000);
        return;
      }

      DOM.result.innerHTML = `
        <div class="alert alert-success text-center mb-3">
          👤 <strong>${displayName}</strong> - ${displayRoll}. Proceed with Time Out.
        </div>
        <form id="timeout-form">
          <label for="tag">Enter or Scan Tag:</label>
          <input type="text" id="tag" name="tag" class="form-control mb-2" required autocomplete="off">
          <div id="tag-reader" class="mt-3 border rounded shadow-sm" style="max-width: 300px; margin: 0 auto;"></div>
          <div class="text-muted text-center mt-1" style="font-size: 0.9rem;">Or scan tag QR code</div>

          <button type="submit" class="btn btn-danger w-100 mt-2">Request Time Out</button>
        </form>
      `;

      const tagInput = document.getElementById("tag");
      const tagScanner = new Html5Qrcode("tag-reader");

      tagScanner.start(
        { facingMode: "environment" },
        { fps: 10, qrbox: 200 },
        async (code) => {
          const tag = code.trim();

          try {
            const verifyRes = await fetch("verify_tag.php", {
              method: "POST",
              headers: { "Content-Type": "application/x-www-form-urlencoded" },
              body: `tag=${encodeURIComponent(
                tag
              )}&roll_number=${encodeURIComponent(displayRoll)}`,
            });
            const verifyData = await verifyRes.json();

            if (verifyData.valid) {
              tagInput.value = tag;
              await tagScanner.stop();
            } else {
              alert("❌ Invalid tag or not linked to you. Please try again.");
              tagInput.value = "";
            }
          } catch {
            alert("❌ Error verifying tag. Please try again.");
          }
        }
      );

      document
        .getElementById("timeout-form")
        .addEventListener("submit", async (e) => {
          e.preventDefault();

          if (!tagInput.value.trim()) {
            alert("❌ Tag number is required.");
            return;
          }

          try {
            const res = await fetch("time_out_handler.php", {
              method: "POST",
              headers: { "Content-Type": "application/x-www-form-urlencoded" },
              body: `roll_number=${encodeURIComponent(
                displayRoll
              )}&tag=${encodeURIComponent(tagInput.value.trim())}`,
            });
            const data = await res.json();
            showAlert(
              data.status === "success" ? "success" : "danger",
              data.message
            );

            if (data.status === "success" && data.redirect) {
              setTimeout(() => (window.location.href = data.redirect), 2000);
            }
          } catch {
            showAlert("danger", "❌ Network error.");
          }

          updateStatus("📷 Looking for QR code...");
          setTimeout(startScanner, 5000);
        });
    }
  } catch (err) {
    console.error("Fetch error:", err);
    showAlert("danger", "❌ Server error.");
    updateStatus("📷 Looking for QR code...");
    setTimeout(startScanner, 3000);
  }
}

function startScanner() {
  updateStatus(
    `📷 Looking for QR code for Time ${scanMode === "out" ? "Out" : "In"}...`
  );
  scanner.start(
    { facingMode: "environment" },
    { fps: 10, qrbox: 250 },
    (qrCodeMessage) => handleQRCodeScan(qrCodeMessage),
    (error) => console.warn("QR scan error:", error)
  );
}

startScanner();
