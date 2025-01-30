document.addEventListener("DOMContentLoaded", () => {
    const dynamicFormContent = document.getElementById("postContent");

    const forms = {
        text: `<textarea class="form-control" rows="5" placeholder="Write your article here..." name="content"></textarea>`,
        photo: `
        <textarea class="form-control" rows="5" placeholder="Description(optional)..." name="content"></textarea>
            <div id="photoDropArea" class="drag-drop-area">
                <p class="drag-drop-text">Add photo to your post</p>
                <input type="file" id="photoUpload" accept="image/*" class="file-input" name="photo" multiple />
                <div id="photoPreviewContainer" class="preview-container"></div>
            </div>
             
        `,
        video: `
        <textarea class="form-control" rows="5" placeholder="Description(optional)..." name="content"></textarea>
            
            <div id="videoDropArea" class="drag-drop-area">
                <p class="drag-drop-text">Drag & drop your videos here or click to upload</p>
                <input type="file" id="videoUpload" accept="video/*" class="file-input" name="video" multiple />
                <div id="videoPreviewContainer" class="preview-container"></div>
            </div>
            <input type="hidden" value="" name="content" />
        `,
    };

    const updateModalContent = (type) => {
        dynamicFormContent.innerHTML = forms[type] || `<p>No content available</p>`;
        initializeDragAndDrop(type);
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
                    fileInput.files = files;
                    previewFiles(files, previewContainer, type);
                }
            });

            dropArea.addEventListener("click", () => {
                fileInput.click();
            });

            fileInput.addEventListener("change", () => {
                if (fileInput.files.length > 0) {
                    previewFiles(fileInput.files, previewContainer, type);
                }
            });
        }
    }

    function previewFiles(files, previewContainer, type) {
        previewContainer.innerHTML = ""; // Clear previous previews
        const maxFiles = 5; // Example: Admin-defined limit

        Array.from(files).slice(0, maxFiles).forEach((file, index) => {
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
                    const dataTransfer = new DataTransfer();
                    const updatedFiles = Array.from(fileInput.files).filter((_, i) => i !== index);
                    updatedFiles.forEach((updatedFile) => dataTransfer.items.add(updatedFile));
                    fileInput.files = dataTransfer.files;
                    previewFiles(fileInput.files, previewContainer, type);
                });

                previewItem.appendChild(removeButton);
                previewContainer.appendChild(previewItem);
            };

            reader.readAsDataURL(file);
        });
    }
});






// Comments Script

    function postComment(postId) {
        const commentCountElement = document.getElementById(`commentCount${postId}`);
        let count = parseInt(commentCountElement.innerText);
        count++;
        commentCountElement.innerText = count.toString();
    }

