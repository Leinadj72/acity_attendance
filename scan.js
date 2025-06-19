const scanner = new Html5Qrcode("reader");
let scannedRollNumber = "";
let scannerTimeout;

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

async function handleQRCodeScan(qrCode) {
  console.log("QR Code detected:", qrCode);
  await scanner.stop();
  scannedRollNumber = qrCode.trim();
  updateStatus("⌛ Verifying roll number...");

  try {
    const res = await fetch("scan_handler.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `roll_number=${encodeURIComponent(
        scannedRollNumber
      )}&mode=${encodeURIComponent(scanMode)}`,
    });

    const result = await res.json();

    if (scanMode === "in") {
      if (result.status !== "require_inputs") {
        showAlert("danger", result.message);
        updateStatus("📷 Looking for QR code...");
        setTimeout(startScanner, 3000);
        return;
      }

      DOM.result.innerHTML = `
        <div class="alert alert-success text-center mb-3">
          👤 User QR recognized. Proceed with Time In.
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
        const opt = document.createElement("option");
        opt.value = item;
        opt.textContent = item;
        itemSelect.appendChild(opt);
      });

      const locationSelect = document.getElementById("location");
      result.locations.forEach((loc) => {
        const opt = document.createElement("option");
        opt.value = loc;
        opt.textContent = loc;
        locationSelect.appendChild(opt);
      });

      const tagInput = document.getElementById("tag");
      const tagScanner = new Html5Qrcode("tag-reader");

      tagScanner.start(
        { facingMode: "environment" },
        { fps: 10, qrbox: 200 },
        async (code) => {
          const tag = code.trim();
          await tagScanner.stop();

          try {
            const verifyRes = await fetch("verify_tag.php", {
              method: "POST",
              headers: { "Content-Type": "application/x-www-form-urlencoded" },
              body: `tag=${encodeURIComponent(tag)}&item=${encodeURIComponent(
                itemSelect.value
              )}`,
            });

            const verifyData = await verifyRes.json();
            if (verifyData.valid) {
              tagInput.value = tag;
            } else {
              alert("❌ Invalid tag for selected item.");
              tagInput.value = "";
              tagScanner.start();
            }
          } catch (err) {
            alert("❌ Error verifying tag.");
          }
        }
      );

      document
        .getElementById("time-form")
        .addEventListener("submit", async (e) => {
          e.preventDefault();

          const formData = new URLSearchParams({
            token: scannedRollNumber,
            item: itemSelect.value,
            tag_number: tagInput.value.trim(),
            location: locationSelect.value,
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
              setTimeout(() => {
                window.location.href = data.redirect;
              }, 2000);
              return;
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
          👤 User QR recognized. Scan or enter tag to Time Out.
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
          await tagScanner.stop();

          try {
            const verifyRes = await fetch("verify_tag.php", {
              method: "POST",
              headers: { "Content-Type": "application/x-www-form-urlencoded" },
              body: `tag=${encodeURIComponent(
                tag
              )}&roll_number=${encodeURIComponent(result.roll_number)}`,
            });

            const verifyData = await verifyRes.json();
            if (verifyData.valid) {
              tagInput.value = tag;
            } else {
              alert("❌ Invalid tag or not linked to you.");
              tagInput.value = "";
              tagScanner.start();
            }
          } catch {
            alert("❌ Error verifying tag.");
          }
        }
      );

      document
        .getElementById("timeout-form")
        .addEventListener("submit", async (e) => {
          e.preventDefault();

          try {
            const res = await fetch("time_out_handler.php", {
              method: "POST",
              headers: { "Content-Type": "application/x-www-form-urlencoded" },
              body: `roll_number=${encodeURIComponent(
                result.roll_number
              )}&tag=${encodeURIComponent(tagInput.value.trim())}`,
            });

            const data = await res.json();
            showAlert(
              data.status === "success" ? "success" : "danger",
              data.message
            );

            if (data.status === "success" && data.redirect) {
              setTimeout(() => {
                window.location.href = data.redirect;
              }, 2000);
              return;
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
    (qrCodeMessage) => {
      clearTimeout(scannerTimeout);
      handleQRCodeScan(qrCodeMessage);
    },
    (error) => {
      console.warn("QR scan error:", error);
    }
  );
}

startScanner();
