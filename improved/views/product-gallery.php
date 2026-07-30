<?php
/**
 * Product Gallery Display Template
 *
 * Displays product images with lazy loading and lightbox functionality.
 */
?>
<div class="product-gallery">
    <div class="gallery-container">
        <!-- Main Image Display -->
        <?php if (!empty($primaryImage)): ?>
            <div class="main-image">
                <img
                    id="mainImage"
                    src="<?php echo htmlspecialchars($primaryImage['path']); ?>"
                    alt="<?php echo htmlspecialchars($primaryImage['name']); ?>"
                    loading="lazy"
                    class="main-product-image"
                >
            </div>
        <?php endif; ?>

        <!-- Thumbnail Gallery -->
        <?php if (!empty($images) && count($images) > 1): ?>
            <div class="thumbnails">
                <?php foreach ($images as $image): ?>
                    <div class="thumbnail-item" data-image-id="<?php echo $image['id']; ?>">
                        <img
                            src="<?php echo htmlspecialchars(str_replace('.jpg', '_thumbnail.jpg', $image['path'])); ?>"
                            alt="<?php echo htmlspecialchars($image['name']); ?>"
                            loading="lazy"
                            class="thumbnail"
                            data-full-url="<?php echo htmlspecialchars($image['path']); ?>"
                            title="Click to view"
                        >
                        <?php if ($image['is_primary']): ?>
                            <span class="primary-badge">Primary</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Lightbox -->
        <div id="lightbox" class="lightbox" style="display: none;">
            <span class="close">&times;</span>
            <img class="lightbox-content" id="lightboxImage" src="" alt="Full size image">
            <div class="caption" id="lightboxCaption"></div>
            <a class="prev">&#10094;</a>
            <a class="next">&#10095;</a>
        </div>
    </div>
</div>

<style>
.product-gallery {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-bottom: 2rem;
}

.gallery-container {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.main-image {
    background: #f5f5f5;
    border-radius: 8px;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    aspect-ratio: 4/3;
}

.main-product-image {
    width: 100%;
    height: 100%;
    object-fit: contain;
    max-width: 100%;
}

.thumbnails {
    display: flex;
    gap: 0.5rem;
    overflow-x: auto;
    padding: 0.5rem 0;
}

.thumbnail-item {
    flex-shrink: 0;
    cursor: pointer;
    position: relative;
    border: 2px solid transparent;
    border-radius: 4px;
    overflow: hidden;
    transition: border-color 0.3s ease;
}

.thumbnail-item:hover {
    border-color: #007bff;
}

.thumbnail-item.active {
    border-color: #0056b3;
}

.thumbnail {
    width: 120px;
    height: 120px;
    object-fit: cover;
    display: block;
}

.primary-badge {
    position: absolute;
    top: 0.25rem;
    right: 0.25rem;
    background: rgba(0, 123, 255, 0.9);
    color: white;
    font-size: 0.65rem;
    padding: 0.25rem 0.5rem;
    border-radius: 3px;
    font-weight: bold;
}

/* Lightbox Styles */
.lightbox {
    display: flex;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.9);
    justify-content: center;
    align-items: center;
}

.lightbox-content {
    max-width: 90%;
    max-height: 90%;
    object-fit: contain;
}

.close {
    position: absolute;
    top: 1rem;
    right: 2rem;
    color: white;
    font-size: 2rem;
    cursor: pointer;
    font-weight: bold;
    transition: color 0.3s ease;
}

.close:hover {
    color: #ccc;
}

.caption {
    position: absolute;
    bottom: 1rem;
    left: 50%;
    transform: translateX(-50%);
    color: white;
    text-align: center;
    font-size: 0.9rem;
}

.prev, .next {
    cursor: pointer;
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 2rem;
    height: 2rem;
    background-color: rgba(255, 255, 255, 0.3);
    color: white;
    font-weight: bold;
    font-size: 1.5rem;
    transition: background-color 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    user-select: none;
}

.prev:hover, .next:hover {
    background-color: rgba(255, 255, 255, 0.6);
}

.prev {
    left: 1rem;
}

.next {
    right: 1rem;
}

@media (max-width: 768px) {
    .thumbnail {
        width: 80px;
        height: 80px;
    }

    .lightbox-content {
        max-width: 95%;
        max-height: 95%;
    }

    .prev, .next {
        width: 1.5rem;
        height: 1.5rem;
        font-size: 1rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const thumbnails = document.querySelectorAll('.thumbnail-item');
    const mainImage = document.getElementById('mainImage');
    const lightbox = document.getElementById('lightbox');
    const lightboxImage = document.getElementById('lightboxImage');
    const lightboxCaption = document.getElementById('lightboxCaption');
    const closeBtn = document.querySelector('.close');
    const prevBtn = document.querySelector('.prev');
    const nextBtn = document.querySelector('.next');
    let currentImageIndex = 0;

    // Thumbnail click handlers
    thumbnails.forEach((thumb, index) => {
        thumb.addEventListener('click', function() {
            currentImageIndex = index;
            const fullUrl = this.querySelector('.thumbnail').dataset.fullUrl;
            const name = this.querySelector('.thumbnail').alt;

            mainImage.src = fullUrl;
            mainImage.alt = name;

            // Update active state
            thumbnails.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
        });
    });

    // Main image click to open lightbox
    if (mainImage) {
        mainImage.addEventListener('click', function() {
            openLightbox();
        });
    }

    // Lightbox functions
    function openLightbox() {
        const currentThumb = thumbnails[currentImageIndex];
        if (!currentThumb) return;

        const fullUrl = currentThumb.querySelector('.thumbnail').dataset.fullUrl;
        const name = currentThumb.querySelector('.thumbnail').alt;

        lightboxImage.src = fullUrl;
        lightboxCaption.textContent = name;
        lightbox.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeLightbox() {
        lightbox.style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    function nextImage() {
        currentImageIndex = (currentImageIndex + 1) % thumbnails.length;
        openLightbox();
    }

    function prevImage() {
        currentImageIndex = (currentImageIndex - 1 + thumbnails.length) % thumbnails.length;
        openLightbox();
    }

    // Event listeners
    if (closeBtn) closeBtn.addEventListener('click', closeLightbox);
    if (prevBtn) prevBtn.addEventListener('click', prevImage);
    if (nextBtn) nextBtn.addEventListener('click', nextImage);

    // Close on background click
    lightbox.addEventListener('click', function(e) {
        if (e.target === this) closeLightbox();
    });

    // Keyboard navigation
    document.addEventListener('keydown', function(e) {
        if (lightbox.style.display === 'flex') {
            if (e.key === 'ArrowLeft') prevImage();
            if (e.key === 'ArrowRight') nextImage();
            if (e.key === 'Escape') closeLightbox();
        }
    });
});
</script>
