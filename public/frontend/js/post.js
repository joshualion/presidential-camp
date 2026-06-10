document.addEventListener("DOMContentLoaded", () => {
    const MAX_CONTENT_LENGTH = 750;
    const MAX_IMAGE_SIZE = 1 * 1024 * 1024; // 1MB
    const MAX_VIDEO_SIZE = 20 * 1024 * 1024; // 20MB
    const modalElement = document.getElementById("postModal");
    const allowedAudience =
        modalElement && modalElement.dataset && modalElement.dataset.allowedAudience
            ? modalElement.dataset.allowedAudience
            : "pu";
    const allowedRankValue =
        modalElement && modalElement.dataset && modalElement.dataset.allowedRank
            ? Number(modalElement.dataset.allowedRank)
            : NaN;
    const allowedRank = Number.isNaN(allowedRankValue) ? undefined : allowedRankValue;
    const audienceRank = { public: 5, region: 4, state: 3, lga: 2, ward: 1, pu: 0 };
    const audienceLabels = {
        public: "Nationwide Audience",
        region: `${modalElement && modalElement.dataset && modalElement.dataset.regionName ? modalElement.dataset.regionName : "Region"} Audience`,
        state: `${modalElement && modalElement.dataset && modalElement.dataset.stateName ? modalElement.dataset.stateName : "State"} Audience`,
        lga: `${modalElement && modalElement.dataset && modalElement.dataset.lgaName ? modalElement.dataset.lgaName : "LGA"} Audience`,
        ward: `Ward: ${modalElement && modalElement.dataset && modalElement.dataset.wardName ? modalElement.dataset.wardName : ""} Audience`,
        pu: `PU: ${modalElement && modalElement.dataset && modalElement.dataset.puName ? modalElement.dataset.puName : ""} Audience`,
    };

    const dynamicFormContent = document.getElementById("postContent");

    const forms = {
        text: `
        <label class="form-label small text-muted">Message (max ${MAX_CONTENT_LENGTH} characters)</label>
        <textarea class="form-control" rows="5" placeholder="Share what's on your mind..." name="content" maxlength="${MAX_CONTENT_LENGTH}"></textarea>
        <div class="text-end small text-muted mt-1"><span class="content-count">0</span> / ${MAX_CONTENT_LENGTH}</div>
        `,
        photo: `
        <label class="form-label small text-muted">Caption (optional, max ${MAX_CONTENT_LENGTH} characters)</label>
        <textarea class="form-control" rows="5" placeholder="Description (optional)..." name="content" maxlength="${MAX_CONTENT_LENGTH}"></textarea>
        <div class="text-end small text-muted mt-1"><span class="content-count">0</span> / ${MAX_CONTENT_LENGTH}</div>
            <div id="photoDropArea" class="drag-drop-area mt-3">
                <p class="drag-drop-text mb-1">Add photo to your post</p>
                <small class="text-muted d-block mb-2">Images up to 1MB each. JPG, PNG, GIF, WEBP.</small>
                <input type="file" id="photoUpload" accept="image/*" class="file-input" name="photo" multiple />
                <div id="photoPreviewContainer" class="preview-container"></div>
            </div>
             
        `,
        video: `
        <label class="form-label small text-muted">Caption (optional, max ${MAX_CONTENT_LENGTH} characters)</label>
        <textarea class="form-control" rows="5" placeholder="Description (optional)..." name="content" maxlength="${MAX_CONTENT_LENGTH}"></textarea>
        <div class="text-end small text-muted mt-1"><span class="content-count">0</span> / ${MAX_CONTENT_LENGTH}</div>
            
            <div id="videoDropArea" class="drag-drop-area mt-3">
                <p class="drag-drop-text mb-1">Drag & drop your videos here or click to upload</p>
                <small class="text-muted d-block mb-2">Videos up to 20MB. MP4, MOV, or WEBM.</small>
                <input type="file" id="videoUpload" accept="video/*" class="file-input" name="video" multiple />
                <div id="videoPreviewContainer" class="preview-container"></div>
            </div>
            <input type="hidden" value="" name="content" />
        `,
    };

    const showValidationMessage = (message, type = "danger") => {
        const alertBox = document.getElementById("postValidation");
        if (!alertBox) return;
        alertBox.textContent = message;
        alertBox.classList.remove("d-none", "alert-danger", "alert-warning", "alert-success");
        alertBox.classList.add(`alert-${type}`);
    };

    const hideValidationMessage = () => {
        const alertBox = document.getElementById("postValidation");
        if (!alertBox) return;
        alertBox.textContent = "";
        alertBox.classList.add("d-none");
    };

    const setupCharacterCounters = () => {
        const textareas = dynamicFormContent.querySelectorAll('textarea[name="content"]');
        textareas.forEach((textarea) => {
            const counter = textarea.parentElement.querySelector(".content-count");
            const updateCount = () => {
                if (counter) counter.textContent = textarea.value.length;
            };
            textarea.addEventListener("input", () => {
                updateCount();
                if (textarea.value.length >= MAX_CONTENT_LENGTH) {
                    showValidationMessage(`You've reached the ${MAX_CONTENT_LENGTH}-character limit.`);
                } else {
                    hideValidationMessage();
                }
            });
            updateCount();
        });
    };

    const updateModalContent = (type) => {
        dynamicFormContent.innerHTML = forms[type] || `<p>No content available</p>`;
        hideValidationMessage();
        initializeDragAndDrop(type);
        setupCharacterCounters();
    };

    const triggerButtons = document.querySelectorAll('[data-bs-target="#postModal"]');
    triggerButtons.forEach((button) => {
        button.addEventListener("click", () => {
            const type = button.getAttribute("data-type");
            updateModalContent(type);
        });
    });

    const footerButtons = document.querySelectorAll("#postModal .modal-footer button[data-type]");
    footerButtons.forEach((button) => {
        button.addEventListener("click", () => {
            const type = button.getAttribute("data-type");
            updateModalContent(type);
        });
    });

    function filterFilesBySize(files, type) {
        const validFiles = [];
        const overLimitNames = [];

        Array.from(files).forEach((file) => {
            if (type === "photo" && file.type.startsWith("image/")) {
                if (file.size <= MAX_IMAGE_SIZE) {
                    validFiles.push(file);
                } else {
                    overLimitNames.push(`${file.name} (over 1MB)`);
                }
            } else if (type === "video" && file.type.startsWith("video/")) {
                if (file.size <= MAX_VIDEO_SIZE) {
                    validFiles.push(file);
                } else {
                    overLimitNames.push(`${file.name} (over 20MB)`);
                }
            }
        });

        if (overLimitNames.length) {
            const message =
                type === "photo"
                    ? `Image limit is 1MB. Please remove: ${overLimitNames.join(", ")}.`
                    : `Video limit is 20MB. Please remove: ${overLimitNames.join(", ")}.`;
            showValidationMessage(message, "warning");
        } else {
            hideValidationMessage();
        }

        return validFiles;
    }

    function initializeDragAndDrop(type) {
        const dropArea = document.getElementById(`${type}DropArea`);
        const fileInput = document.getElementById(`${type}Upload`);
        const previewContainer = document.getElementById(`${type}PreviewContainer`);

        if (dropArea && fileInput && previewContainer) {
            dropArea.addEventListener("dragover", (event) => {
                event.preventDefault();
                dropArea.classList.add("drag-over");
            });

            dropArea.addEventListener("dragleave", () => {
                dropArea.classList.remove("drag-over");
            });

            dropArea.addEventListener("drop", (event) => {
                event.preventDefault();
                dropArea.classList.remove("drag-over");
                const files = event.dataTransfer.files;

                if (files.length > 0) {
                    previewFiles(files, previewContainer, type, fileInput);
                }
            });

            dropArea.addEventListener("click", () => {
                fileInput.click();
            });

            fileInput.addEventListener("change", () => {
                if (fileInput.files.length > 0) {
                    previewFiles(fileInput.files, previewContainer, type, fileInput);
                }
            });
        }
    }

    function previewFiles(files, previewContainer, type, fileInput) {
        previewContainer.innerHTML = ""; // Clear previous previews
        const maxFiles = 5; // Example: Admin-defined limit
        const validFiles = filterFilesBySize(files, type).slice(0, maxFiles);

        const dataTransfer = new DataTransfer();
        validFiles.forEach((file) => dataTransfer.items.add(file));
        if (fileInput) {
            fileInput.files = dataTransfer.files;
        }

        validFiles.forEach((file, index) => {
            const reader = new FileReader();

            reader.onload = (e) => {
                const previewItem = document.createElement("div");
                previewItem.classList.add("file-preview-item", "mb-3", "me-3");
                previewItem.style.display = "inline-block";
                previewItem.style.position = "relative";

                if (file.type.startsWith("image/")) {
                    previewItem.innerHTML = `
                        <img src="${e.target.result}" alt="Preview" style="width: 100px; height: 100px; object-fit: cover; border: 1px solid #ddd; border-radius: 8px;">
                    `;
                } else if (file.type.startsWith("video/")) {
                    previewItem.innerHTML = `
                        <video src="${e.target.result}" controls style="width: 100px; height: 100px; border: 1px solid #ddd; border-radius: 8px;"></video>
                    `;
                } else {
                    previewItem.innerHTML = `
                        <div style="width: 100px; height: 100px; display: flex; align-items: center; justify-content: center; border: 1px solid #ddd; border-radius: 8px; font-size: 12px;">
                            ${file.name}
                        </div>
                    `;
                }

                // Add remove button
                const removeButton = document.createElement("button");
                removeButton.innerHTML = "&times;";
                removeButton.style.position = "absolute";
                removeButton.style.top = "-5px";
                removeButton.style.right = "-5px";
                removeButton.style.background = "#ff4d4f";
                removeButton.style.color = "#fff";
                removeButton.style.border = "none";
                removeButton.style.borderRadius = "50%";
                removeButton.style.cursor = "pointer";
                removeButton.style.padding = "3px 6px";

                removeButton.addEventListener("click", () => {
                    const transfer = new DataTransfer();
                    const updatedFiles = Array.from(fileInput.files).filter((_, i) => i !== index);
                    updatedFiles.forEach((updatedFile) => transfer.items.add(updatedFile));
                    fileInput.files = transfer.files;
                    previewFiles(fileInput.files, previewContainer, type, fileInput);
                });

                previewItem.appendChild(removeButton);
                previewContainer.appendChild(previewItem);
            };

            reader.readAsDataURL(file);
        });
    }

    window.validatePostForm = (form) => {
        if (!form) return { valid: true };
        const audienceInput = form.querySelector('input[name="audience"]');
        const selectedAudience = (audienceInput && audienceInput.value) || allowedAudience;
        const maxRank = typeof allowedRank === "number" ? allowedRank : audienceRank[allowedAudience] || 0;
        const selectedRank = audienceRank[selectedAudience] || 0;
        if (selectedRank > maxRank) {
            showValidationMessage(
                `You can share up to ${audienceLabels[allowedAudience] || "your allowed audience"}.`,
                "warning"
            );
            if (audienceInput) {
                audienceInput.value = allowedAudience;
            }
            return { valid: false };
        }

        const contentField = form.querySelector('textarea[name="content"]');
        const text = contentField ? contentField.value.trim() : "";
        if (text.length > MAX_CONTENT_LENGTH) {
            showValidationMessage(
                `Posts are limited to ${MAX_CONTENT_LENGTH} characters. You are ${text.length - MAX_CONTENT_LENGTH} over.`
            );
            return { valid: false };
        }

        const fileInputs = form.querySelectorAll('input[type="file"]');
        for (const input of fileInputs) {
            const files = Array.from(input.files || []);
            for (const file of files) {
                if (file.type.startsWith("image/") && file.size > MAX_IMAGE_SIZE) {
                    showValidationMessage(`Images must be 1MB or less. "${file.name}" is too large.`, "warning");
                    return { valid: false };
                }
                if (file.type.startsWith("video/") && file.size > MAX_VIDEO_SIZE) {
                    showValidationMessage(`Videos must be 20MB or less. "${file.name}" is too large.`, "warning");
                    return { valid: false };
                }
            }
        }

        hideValidationMessage();
        return { valid: true };
    };
});
