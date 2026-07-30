<?php
/**
 * Admin Product Image Manager Template
 *
 * Manages product images: upload, delete, reorder, set primary
 */

if (!isset($productId)) {
    echo '<div class="alert alert-error">Product not found</div>';
    return;
}
?>

<div class="image-manager">
    <h3>Product Images</h3>

    <!-- Upload Section -->
    <div class="upload-section">
        <h4>Upload New Images</h4>
        <form id="imageUploadForm" enctype="multipart/form-data" class="upload-form">
            <input type="hidden" name="product_id" value="<?php echo $productId; ?>">

            <div class="form-group">
                <label for="imageInput">Select Images (JPG, PNG, WebP - Max 10MB each)</label>
                <input
                    type="file"
                    id="imageInput"
                    name="images[]"
                    multiple
                    accept="image/jpeg,image/png,image/webp"
                    class="form-control"
                >
                <small class="text-muted">You can select multiple images at once</small>
            </div>

            <div class="upload-progress" id="uploadProgress" style="display: none;">
                <div class="progress">
                    <div class="progress-bar" id="progressBar" style="width: 0%"></div>
                </div>
                <span id="progressText">0%</span>
            </div>

            <button type="submit" class="btn btn-primary" id="uploadBtn">Upload Images</button>
        </form>

        <div id="uploadMessage" class="alert" style="display: none; margin-top: 1rem;"></div>
    </div>

    <!-- Current Images Section -->
    <div class="images-section">
        <h4>Current Images</h4>

        <div id="imagesContainer" class="images-grid">
            <!-- Images will be loaded here -->
            <p class="text-muted">Loading images...</p>
        </div>
    </div>
</div>

<style>
.image-manager {
    background: #f9f9f9;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.image-manager h3 {
    margin-top: 0;
    border-bottom: 2px solid #007bff;
    padding-bottom: 0.5rem;
    margin-bottom: 1.5rem;
}

.image-manager h4 {
    margin-top: 1.5rem;
    margin-bottom: 1rem;
    font-size: 1.1rem;
}

.upload-section {
    background: white;
    padding: 1rem;
    border-radius: 6px;
    border: 1px solid #e0e0e0;
    margin-bottom: 2rem;
}

.upload-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #333;
}

.form-control {
    padding: 0.75rem;
    border: 2px solid #ddd;
    border-radius: 4px;
    font-size: 1rem;
    cursor: pointer;
}

.form-control:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

.upload-progress {
    margin-top: 1rem;
}

.progress {
    height: 2rem;
    background: #e0e0e0;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 0.5rem;
}

.progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #007bff, #0056b3);
    transition: width 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.85rem;
    font-weight: bold;
}

.small {
    font-size: 0.85rem;
}

.text-muted {
    color: #999;
}

.btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 4px;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 600;
}

.btn-primary {
    background: #007bff;
    color: white;
}

.btn-primary:hover:not(:disabled) {
    background: #0056b3;
    box-shadow: 0 2px 8px rgba(0, 123, 255, 0.3);
}

.btn-primary:disabled {
    background: #ccc;
    cursor: not-allowed;
}

.alert {
    padding: 1rem;
    border-radius: 4px;
    border-left: 4px solid;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border-left-color: #28a745;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border-left-color: #f5c6cb;
}

.alert-warning {
    background: #fff3cd;
    color: #856404;
    border-left-color: #ffc107;
}

.images-section {
    background: white;
    padding: 1rem;
    border-radius: 6px;
    border: 1px solid #e0e0e0;
}

.images-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 1rem;
}

.image-card {
    position: relative;
    border: 2px solid #e0e0e0;
    border-radius: 6px;
    overflow: hidden;
    background: #f5f5f5;
    transition: all 0.3s ease;
    cursor: move;
}

.image-card:hover {
    border-color: #007bff;
    box-shadow: 0 4px 12px rgba(0, 123, 255, 0.2);
}

.image-card.dragging {
    opacity: 0.5;
    background: #e0e0e0;
}

.image-thumbnail {
    width: 100%;
    height: 150px;
    object-fit: cover;
    display: block;
}

.image-actions {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.image-card:hover .image-actions {
    opacity: 1;
}

.action-btn {
    background: #007bff;
    color: white;
    border: none;
    padding: 0.5rem 0.75rem;
    border-radius: 3px;
    cursor: pointer;
    font-size: 0.8rem;
    transition: background 0.2s ease;
}

.action-btn:hover {
    background: #0056b3;
}

.action-btn.danger {
    background: #dc3545;
}

.action-btn.danger:hover {
    background: #c82333;
}

.image-info {
    padding: 0.5rem;
    font-size: 0.8rem;
    color: #666;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.primary-marker {
    position: absolute;
    top: 0.25rem;
    left: 0.25rem;
    background: #28a745;
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 3px;
    font-size: 0.7rem;
    font-weight: bold;
}

.loading-spinner {
    display: inline-block;
    width: 1rem;
    height: 1rem;
    border: 2px solid #f3f3f3;
    border-top: 2px solid #007bff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

@media (max-width: 768px) {
    .images-grid {
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    }

    .image-thumbnail {
        height: 120px;
    }

    .upload-form {
        flex-direction: column;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('imageUploadForm');
    const imageInput = document.getElementById('imageInput');
    const uploadBtn = document.getElementById('uploadBtn');
    const uploadProgress = document.getElementById('uploadProgress');
    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');
    const uploadMessage = document.getElementById('uploadMessage');
    const imagesContainer = document.getElementById('imagesContainer');
    const productId = document.querySelector('input[name="product_id"]').value;

    // Load initial images
    loadImages();

    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        uploadImages();
    });

    function uploadImages() {
        const files = imageInput.files;

        if (files.length === 0) {
            showMessage('Please select at least one image', 'warning');
            return;
        }

        const formData = new FormData();
        formData.append('product_id', productId);

        for (let i = 0; i < files.length; i++) {
            formData.append('images[]', files[i]);
        }

        uploadBtn.disabled = true;
        uploadProgress.style.display = 'block';

        fetch('?action=admin_upload_images', {
            method: 'POST',
            body: formData,
        })
        .then(response => response.json())
        .then(data => {
            uploadBtn.disabled = false;
            uploadProgress.style.display = 'none';

            if (data.success) {
                showMessage(data.message, 'success');
                imageInput.value = '';
                loadImages();
            } else {
                showMessage(data.error || 'Upload failed', 'error');
            }

            if (data.errors && data.errors.length > 0) {
                showMessage('Some files failed: ' + data.errors.join(', '), 'warning');
            }
        })
        .catch(error => {
            uploadBtn.disabled = false;
            uploadProgress.style.display = 'none';
            showMessage('Upload error: ' + error.message, 'error');
        });
    }

    function loadImages() {
        fetch('?action=admin_get_product_images&product_id=' + productId)
            .then(response => response.json())
            .then(data => {
                if (data.images && data.images.length > 0) {
                    renderImages(data.images);
                } else {
                    imagesContainer.innerHTML = '<p class="text-muted">No images uploaded yet</p>';
                }
            })
            .catch(error => {
                showMessage('Failed to load images: ' + error.message, 'error');
            });
    }

    function renderImages(images) {
        imagesContainer.innerHTML = '';

        images.forEach((image, index) => {
            const card = document.createElement('div');
            card.className = 'image-card';
            card.draggable = true;
            card.dataset.imageId = image.id;

            const thumbnail = document.createElement('img');
            thumbnail.className = 'image-thumbnail';
            thumbnail.src = image.path.replace('.jpg', '_thumbnail.jpg');
            thumbnail.alt = image.name;

            const actions = document.createElement('div');
            actions.className = 'image-actions';

            const setPrimaryBtn = document.createElement('button');
            setPrimaryBtn.className = 'action-btn' + (image.is_primary ? ' disabled' : '');
            setPrimaryBtn.textContent = 'Set Primary';
            setPrimaryBtn.disabled = image.is_primary;
            setPrimaryBtn.addEventListener('click', () => setPrimary(image.id));

            const deleteBtn = document.createElement('button');
            deleteBtn.className = 'action-btn danger';
            deleteBtn.textContent = 'Delete';
            deleteBtn.addEventListener('click', () => deleteImage(image.id));

            actions.appendChild(setPrimaryBtn);
            actions.appendChild(deleteBtn);

            const info = document.createElement('div');
            info.className = 'image-info';
            info.textContent = image.name;

            card.appendChild(thumbnail);
            card.appendChild(actions);
            card.appendChild(info);

            if (image.is_primary) {
                const marker = document.createElement('div');
                marker.className = 'primary-marker';
                marker.textContent = 'PRIMARY';
                card.appendChild(marker);
            }

            card.addEventListener('dragstart', handleDragStart);
            card.addEventListener('dragover', handleDragOver);
            card.addEventListener('drop', handleDrop);
            card.addEventListener('dragend', handleDragEnd);

            imagesContainer.appendChild(card);
        });
    }

    let draggedElement = null;

    function handleDragStart(e) {
        draggedElement = this;
        this.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
    }

    function handleDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';

        if (this !== draggedElement) {
            this.style.opacity = '0.5';
        }
    }

    function handleDrop(e) {
        e.preventDefault();
        e.stopPropagation();

        if (this !== draggedElement) {
            const allCards = Array.from(imagesContainer.querySelectorAll('.image-card'));
            const draggedIndex = allCards.indexOf(draggedElement);
            const targetIndex = allCards.indexOf(this);

            if (draggedIndex < targetIndex) {
                this.parentNode.insertBefore(draggedElement, this.nextSibling);
            } else {
                this.parentNode.insertBefore(draggedElement, this);
            }

            saveImageOrder();
        }

        this.style.opacity = '1';
    }

    function handleDragEnd(e) {
        this.classList.remove('dragging');
        document.querySelectorAll('.image-card').forEach(card => {
            card.style.opacity = '1';
        });
    }

    function setPrimary(imageId) {
        fetch('?action=admin_set_primary_image', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ image_id: imageId, product_id: productId }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('Primary image updated', 'success');
                loadImages();
            } else {
                showMessage(data.error || 'Failed to update primary image', 'error');
            }
        })
        .catch(error => {
            showMessage('Error: ' + error.message, 'error');
        });
    }

    function deleteImage(imageId) {
        if (!confirm('Are you sure you want to delete this image?')) {
            return;
        }

        fetch('?action=admin_delete_image', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ image_id: imageId }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('Image deleted successfully', 'success');
                loadImages();
            } else {
                showMessage(data.error || 'Failed to delete image', 'error');
            }
        })
        .catch(error => {
            showMessage('Error: ' + error.message, 'error');
        });
    }

    function saveImageOrder() {
        const order = Array.from(imagesContainer.querySelectorAll('.image-card'))
            .map(card => parseInt(card.dataset.imageId));

        fetch('?action=admin_reorder_images', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ order: order, product_id: productId }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('Image order saved', 'success');
            } else {
                showMessage(data.error || 'Failed to save order', 'error');
                loadImages(); // Reload on error
            }
        })
        .catch(error => {
            showMessage('Error: ' + error.message, 'error');
            loadImages();
        });
    }

    function showMessage(message, type) {
        uploadMessage.textContent = message;
        uploadMessage.className = 'alert alert-' + type;
        uploadMessage.style.display = 'block';

        setTimeout(() => {
            uploadMessage.style.display = 'none';
        }, 5000);
    }
});
</script>
