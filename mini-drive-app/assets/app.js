document.addEventListener("DOMContentLoaded", () => {
  const dropZone = document.getElementById("drop-zone");
  if (!dropZone) return;

  const folderId = dropZone.getAttribute("data-folder-id") || "";

  const uploadFilesAjax = (files) => {
    if (!files || files.length === 0) return;

    Array.from(files).forEach((file) => {
      const formData = new FormData();
      formData.append("action", "upload");
      formData.append("folder_id", folderId);
      formData.append("file", file);

      fetch("file_action.php", {
        method: "POST",
        body: formData,
        headers: { "X-Requested-With": "XMLHttpRequest" },
      })
        .then((res) => res.text())
        .then((text) => {
          if (text.startsWith("OK")) {
            window.location.reload();
          } else {
            alert("Upload gagal: " + text);
          }
        })
        .catch((err) => {
          console.error(err);
          alert("Terjadi kesalahan saat upload.");
        });
    });
  };

  dropZone.addEventListener("click", () => {
    const input = document.createElement("input");
    input.type = "file";
    input.multiple = true;
    input.onchange = (e) => uploadFilesAjax(e.target.files);
    input.click();
  });

  ["dragover", "dragenter"].forEach((eventName) => {
    dropZone.addEventListener(eventName, (e) => {
      e.preventDefault();
      e.stopPropagation();
      dropZone.classList.add("dragover");
    });
  });

  ["dragleave", "dragend"].forEach((eventName) => {
    dropZone.addEventListener(eventName, (e) => {
      e.preventDefault();
      e.stopPropagation();
      dropZone.classList.remove("dragover");
    });
  });

  dropZone.addEventListener("drop", (e) => {
    e.preventDefault();
    e.stopPropagation();
    dropZone.classList.remove("dragover");
    const dt = e.dataTransfer;
    if (dt && dt.files) {
      uploadFilesAjax(dt.files);
    }
  });
});
