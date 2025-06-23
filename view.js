$(document).ready(function () {
  function showToast(message, type = "success") {
    const toast = $(`
      <div class="toast align-items-center text-white bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
          <div class="toast-body">${message}</div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
      </div>
    `);
    $("#toastContainer").append(toast);
    const bsToast = new bootstrap.Toast(toast[0], { delay: 3000 });
    bsToast.show();
    toast.on("hidden.bs.toast", () => toast.remove());
  }

  const table = $("#attendanceTable").DataTable({
    dom: "Bfrtip",
    buttons: [
      { extend: "excelHtml5", title: "Attendance Records" },
      { extend: "csvHtml5", title: "Attendance Records" },
      {
        extend: "pdfHtml5",
        title: "Attendance Records",
        orientation: "landscape",
        pageSize: "A4",
      },
      { extend: "print", title: "Attendance Records" },
    ],
    ajax: {
      url: "fetch_attendance.php",
      type: "POST",
      data: function (d) {
        d.start_date = $("#start_date").val();
        d.end_date = $("#end_date").val();
        d.search = $("#search_roll_location").val();
        d.tag_number = $("#search_tag_number").val();
        d.pending_only = $("#pending_only").is(":checked") ? "1" : "0";
      },
      dataSrc: "data",
    },
    columns: [
      { data: "index" },
      { data: "date" },
      { data: "roll_number" },
      { data: "name" },
      { data: "email" },
      { data: "phone" },
      { data: "item" },
      { data: "tag_number" },
      { data: "location" },
      { data: "time_in", render: (data) => data || "--" },
      { data: "time_out", render: (data) => data || "--" },
      { data: "time_out_requested_at", render: (data) => data || "--" },
      {
        data: "status",
        render: function (status) {
          const classes = {
            Approved: "success",
            Rejected: "danger",
            Pending: "warning",
            Active: "info",
            Completed: "primary",
          };
          return `<span class="badge bg-${
            classes[status] || "secondary"
          }">${status}</span>`;
        },
      },
      { data: "approved_by", render: (data) => data || "--" },
      { data: "rejected_by", render: (data) => data || "--" },
      { data: "edited_by", render: (data) => data || "--" },
      {
        data: null,
        orderable: false,
        render: function (row) {
          let buttons = `<button class="btn btn-sm btn-outline-primary edit-btn" data-id="${row.id}">Edit</button> `;
          if (row.time_out_requested == 1 && row.time_out_approved == 0) {
            buttons += `
              <button class="btn btn-sm btn-outline-success approve-btn" data-id="${row.id}">Approve</button>
              <button class="btn btn-sm btn-outline-danger reject-btn" data-id="${row.id}">Reject</button>
            `;
          } else {
            buttons += `<span class="text-muted">No Action</span>`;
          }
          return buttons;
        },
      },
    ],
  });

  $("#filterBtn").on("click", () => table.ajax.reload());

  $("#resetBtn").on("click", function () {
    $("#start_date, #end_date, #search_roll_location, #search_tag_number").val(
      ""
    );
    $("#pending_only").prop("checked", false);
    table.ajax.reload();
  });

  function handleAction(endpoint, id, button, newStatus, successMessage) {
    const row = table.row(button.closest("tr"));
    const originalHTML = button.html();

    button
      .prop("disabled", true)
      .html(
        `<span class="spinner-border spinner-border-sm me-1"></span> ${newStatus}...`
      );

    $.post(
      endpoint,
      { id },
      function (res) {
        if (res.success === true) {
          showToast(res.message || successMessage);

          const rowData = row.data();
          rowData.status = newStatus;
          rowData.time_out = res.time_out || rowData.time_out || "--";
          rowData.approved_by =
            newStatus === "Approved"
              ? res.approved_by || "--"
              : rowData.approved_by;
          rowData.rejected_by =
            newStatus === "Rejected"
              ? res.rejected_by || "--"
              : rowData.rejected_by;
          rowData.time_out_requested = 0;
          rowData.time_out_approved = newStatus === "Approved" ? 1 : 0;
          row.data(rowData).invalidate().draw(false);
        } else {
          showToast(
            res.message || `❌ Failed to ${newStatus.toLowerCase()}.`,
            "danger"
          );
        }
      },
      "json"
    )
      .fail(() => {
        showToast(
          `❌ Network error during ${newStatus.toLowerCase()}.`,
          "danger"
        );
      })
      .always(() => {
        button.prop("disabled", false).html(originalHTML);
      });
  }

  $("#attendanceTable").on("click", ".approve-btn", function () {
    handleAction(
      "approve_attendance.php",
      $(this).data("id"),
      $(this),
      "Approved",
      "✅ Time Out approved."
    );
  });

  $("#attendanceTable").on("click", ".reject-btn", function () {
    handleAction(
      "reject_attendance.php",
      $(this).data("id"),
      $(this),
      "Rejected",
      "❌ Time Out rejected."
    );
  });

  // Helper function to extract HH:mm:ss from datetime string
  function extractTime(datetime) {
    if (!datetime) return "";
    const timePart = datetime.split(" ")[1];
    return timePart ? timePart.slice(0, 8) : "";
  }

  $("#attendanceTable").on("click", ".edit-btn", function () {
    const rowData = table.row($(this).closest("tr")).data();
    $("#edit_id").val(rowData.id);
    $("#edit_date").val(rowData.date);
    $("#edit_roll_number").val(rowData.roll_number);
    $("#edit_location").val(rowData.location);
    $("#edit_item").val(rowData.item);
    $("#edit_time_in").val(extractTime(rowData.time_in));
    $("#edit_time_out").val(extractTime(rowData.time_out));
    $("#editModal").modal("show");
  });

  $("#editForm").submit(function (e) {
    e.preventDefault();
    const formData = $(this).serialize();
    $.post(
      "edit_attendance.php",
      formData,
      function (res) {
        if (res.success) {
          showToast(res.message || "✅ Updated successfully");
          $("#editModal").modal("hide");
          table.ajax.reload(null, false);
        } else {
          showToast(res.message || "❌ Update failed", "danger");
        }
      },
      "json"
    ).fail(() => {
      showToast("❌ Network error during update.", "danger");
    });
  });
});
