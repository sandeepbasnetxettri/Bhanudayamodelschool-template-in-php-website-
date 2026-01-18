// Gallery JavaScript
document.addEventListener('DOMContentLoaded', () => {
    loadGallery();
    setupFilters();
    setupModal();
});

async function loadGallery() {
    const galleryGrid = document.getElementById('galleryGrid');
    if (!galleryGrid) return;

    try {
        // Fetch gallery data from API
        const response = await fetch('../api/get-gallery-items.php');
        const result = await response.json();
        
        if (result.success && result.data && Array.isArray(result.data) && result.data.length > 0) {
            const galleryData = result.data;
            
            const html = galleryData.map(item => `
                <div class="gallery-item" data-category="${item.category}">
                    <img src="${item.image}" alt="${item.title}" 
                        onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22400%22 height=%22300%22%3E%3Crect fill=%22%23${getRandomColor()}%22 width=%22400%22 height=%22300%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 font-size=%2220%22 fill=%22white%22%3E${item.category}%3C/text%3E%3C/svg%3E'"
                        onclick="openModal(this, '${item.title}')">
                    <div class="gallery-overlay">
                        <h4>${item.title}</h4>
                        <p>${item.date}</p>
                    </div>
                </div>
            `).join('');

            galleryGrid.innerHTML = html;
        } else {
            console.log('API call failed or returned no data, using fallback');
            // Fallback to local gallery images if API fails
            loadLocalGalleryImages(galleryGrid);
        }
    } catch (error) {
        console.error('Error loading gallery:', error);
        // Fallback to local gallery images if API fails
        loadLocalGalleryImages(galleryGrid);
    }
}

// Function to get available local images from the Gallery folder
async function getAvailableLocalImages() {
    const availableImages = [];
    const maxExpectedImages = 100; // Check up to 100 images
    const extensions = ['.jpg', '.jpeg', '.png', '.gif', '.JPG', '.JPEG', '.PNG', '.GIF'];
    
    // Try different numbering patterns
    for (let i = 1; i <= maxExpectedImages; i++) {
        let found = false;
        
        // Check different extensions for each number
        for (const ext of extensions) {
            const imageUrl = `../Gallery/${i}${ext}`;
            
            // Test if image exists by trying to load it
            if (await imageExists(imageUrl)) {
                const categories = ['events', 'sports', 'facilities', 'activities', 'achievements'];
                const category = categories[i % categories.length];
                const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                const month = months[(i + 2) % 12];
                const day = ((i * 3) % 28) + 1;
                
                availableImages.push({
                    id: i,
                    title: `Gallery Image ${i}`,
                    category: category,
                    date: `${month} ${day}, 2024`,
                    image: imageUrl
                });
                found = true;
                break; // Found an image with this number, move to next number
            }
        }
        
        if (!found) {
            // Check if there are consecutive missing images to stop early
            // If we encounter several missing images in a row, we can stop
            if (i > 50) { // If we're past image 50 and haven't found this one
                // Check if recent images exist to determine if we should continue
                const nextFewExist = [];
                for (let j = i + 1; j <= Math.min(i + 5, maxExpectedImages); j++) {
                    for (const ext of extensions) {
                        if (await imageExists(`../Gallery/${j}${ext}`)) {
                            nextFewExist.push(true);
                            break;
                        }
                    }
                }
                if (nextFewExist.length === 0) {
                    break; // No upcoming images found, stop searching
                }
            }
        }
    }
    
    return availableImages;
}

// Helper function to check if an image exists
function imageExists(url) {
    return new Promise((resolve) => {
        const img = new Image();
        img.onload = () => resolve(true);
        img.onerror = () => resolve(false);
        img.src = url;
    });
}

function getDefaultGallery() {
    // This function will be called if async approach isn't suitable
    // For now, return a minimal set and rely on async loading
    return [];
}

function setupFilters() {
    const filterBtns = document.querySelectorAll('.filter-btn');
    
    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            // Remove active class from all buttons
            filterBtns.forEach(b => b.classList.remove('active'));
            
            // Add active class to clicked button
            btn.classList.add('active');
            
            // Get filter category
            const filter = btn.dataset.filter;
            
            // Filter gallery items
            const galleryItems = document.querySelectorAll('.gallery-item');
            galleryItems.forEach(item => {
                if (filter === 'all' || item.dataset.category === filter) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });
}

function setupModal() {
    const modal = document.getElementById('imageModal');
    const closeBtn = document.querySelector('.modal-close');
    
    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            modal.style.display = 'none';
        });
    }
    
    // Close modal when clicking outside the image
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });
    
    // Close modal with Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            modal.style.display = 'none';
        }
    });
}

function openModal(img, caption) {
    const modal = document.getElementById('imageModal');
    const modalImg = document.getElementById('modalImage');
    const modalCaption = document.getElementById('modalCaption');
    
    modal.style.display = 'block';
    modalImg.src = img.src;
    modalCaption.textContent = caption;
}

function getRandomColor() {
    const colors = ['3498db', '2ecc71', 'e74c3c', 'f39c12', '9b59b6', '1abc9c'];
    return colors[Math.floor(Math.random() * colors.length)];
}

// Function to load local gallery images
async function loadLocalGalleryImages(galleryGrid) {
    try {
        // Show loading indicator
        galleryGrid.innerHTML = '<p class="no-data">Loading local gallery images...</p>';
        
        // Get available local images
        const localImages = await getAvailableLocalImages();
        
        if (localImages && localImages.length > 0) {
            const html = localImages.map(item => `
                <div class="gallery-item" data-category="${item.category}">
                    <img src="${item.image}" alt="${item.title}" 
                        onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22400%22 height=%22300%22%3E%3Crect fill=%22%23${getRandomColor()}%22 width=%22400%22 height=%22300%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 font-size=%2220%22 fill=%22white%22%3E${item.category}%3C/text%3E%3C/svg%3E'"
                        onclick="openModal(this, '${item.title}')">
                    <div class="gallery-overlay">
                        <h4>${item.title}</h4>
                        <p>${item.date}</p>
                    </div>
                </div>
            `).join('');

            galleryGrid.innerHTML = html;
            
            // Reinitialize filters for the newly loaded content
            setupFilters();
        } else {
            galleryGrid.innerHTML = '<p class="no-data">No local gallery images found in the Gallery folder.</p>';
        }
    } catch (error) {
        console.error('Error loading local gallery images:', error);
        galleryGrid.innerHTML = '<p class="no-data">Failed to load local gallery images.</p>';
    }
}

// Add modal styles dynamically
const galleryStyles = `
    .no-data {
        text-align: center;
        padding: 60px 20px;
        color: var(--light-text);
        font-size: 18px;
    }

    .gallery-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 25px;
        margin-top: 30px;
    }

    .gallery-item {
        position: relative;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        cursor: pointer;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        height: 250px;
    }

    .gallery-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }

    .gallery-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .gallery-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
        color: white;
        padding: 20px 15px 15px;
        transform: translateY(0);
        transition: transform 0.3s ease;
    }

    .gallery-item:hover .gallery-overlay {
        transform: translateY(0);
    }

    .gallery-overlay h4 {
        margin: 0 0 5px 0;
        font-size: 16px;
        font-weight: 600;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .gallery-overlay p {
        margin: 0;
        font-size: 14px;
        opacity: 0.8;
    }
`;

const modalStyles = `
    .modal {
        display: none;
        position: fixed;
        z-index: 10000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.9);
    }

    .modal-content {
        margin: auto;
        display: block;
        max-width: 90%;
        max-height: 80%;
        animation: zoom 0.3s;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
    }

    @keyframes zoom {
        from {transform: translate(-50%, -50%) scale(0.5)}
        to {transform: translate(-50%, -50%) scale(1)}
    }

    .modal-close {
        position: absolute;
        top: 30px;
        right: 50px;
        color: #fff;
        font-size: 40px;
        font-weight: bold;
        cursor: pointer;
        z-index: 10001;
    }

    .modal-close:hover {
        color: #ccc;
    }

    .modal-caption {
        margin: auto;
        display: block;
        width: 80%;
        max-width: 700px;
        text-align: center;
        color: #ccc;
        padding: 20px;
        position: fixed;
        bottom: 50px;
        left: 50%;
        transform: translateX(-50%);
        font-size: 18px;
        background: rgba(0,0,0,0.7);
        border-radius: 10px;
    }

    @media (max-width: 768px) {
        .modal-content {
            max-width: 95%;
            max-height: 70%;
        }

        .modal-close {
            top: 10px;
            right: 20px;
            font-size: 30px;
        }

        .modal-caption {
            font-size: 14px;
            bottom: 20px;
            width: 90%;
        }
    }
`;

const galleryStyleSheet = document.createElement('style');
galleryStyleSheet.textContent = galleryStyles;
document.head.appendChild(galleryStyleSheet);

const modalStyleSheet = document.createElement('style');
modalStyleSheet.textContent = modalStyles;
document.head.appendChild(modalStyleSheet);
