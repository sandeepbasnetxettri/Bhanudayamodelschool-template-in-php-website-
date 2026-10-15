// Notice Board JavaScript
// Enhanced search functionality
let searchTimeout;

document.addEventListener('DOMContentLoaded', () => {
    loadNoticeBoard();
    setupNoticeFilter();
});

async function loadNoticeBoard(searchTerm = '') {
    const noticeBoard = document.getElementById('noticeBoard');
    if (!noticeBoard) return;

    // Show loading indicator
    noticeBoard.innerHTML = '<p class="no-data">Loading notices...</p>';
    
    try {
        let apiUrl = '../api/get-notices.php';
        if (searchTerm && searchTerm.trim() !== '') {
            apiUrl = `../api/search-notices.php?search=${encodeURIComponent(searchTerm)}`;
        }
        
        console.log(`Fetching notices from API: ${apiUrl}`);
        const response = await fetch(apiUrl);
        console.log('API response status:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('API response data:', data);
        
        if (data.success && data.notices) {
            const notices = data.notices;
            console.log(`Loaded ${notices.length} notices from API`);
            
            // Add search results counter if there's a search term
            if (searchTerm && searchTerm.trim() !== '') {
                const noticeCount = notices.length;
                const counterHtml = `
                    <div class="search-results-counter">
                        Found ${noticeCount} result${noticeCount !== 1 ? 's' : ''} for "${searchTerm}"
                    </div>`;
                
                noticeBoard.innerHTML = counterHtml;
                
                if (notices.length === 0) {
                    noticeBoard.innerHTML += `<p class="no-data">No notices match your search.</p>`;
                    return;
                }
            } else if (notices.length === 0) {
                noticeBoard.innerHTML = '<p class="no-data">No notices available at the moment.</p>';
                return;
            }
            
            const html = notices.map(notice => {
                const displayType = mapNoticeType(notice.type);
                const badgeColor = getBadgeColor(notice.type);
                const imageUrl = notice.image || 'images/news-default.jpg';
                return `
                    <div class="notice-item" data-type="${displayType}" data-title="${notice.title.toLowerCase()}" data-content="${notice.description.toLowerCase()}" data-date="${formatDate(notice.date).toLowerCase()}">
                        ${notice.image ? `<div class="notice-image">
                            <img src="${imageUrl}" alt="${notice.title}" onerror="this.src='images/news-default.jpg';this.onerror=null;">
                        </div>` : ''}
                        <div class="notice-header">
                            <div class="notice-title">${highlightSearchTerms(notice.title, searchTerm)}</div>
                            <div class="notice-meta">
                                <span class="notice-badge" style="background: ${badgeColor};">${displayType}</span>
                                <span class="notice-date">${formatDate(notice.date)}</span>
                            </div>
                        </div>
                        <div class="notice-content">${highlightSearchTerms(notice.description, searchTerm)}</div>
                    </div>
                `;
            }).join('');

            if (searchTerm && searchTerm.trim() !== '') {
                // Append notices to existing counter
                noticeBoard.innerHTML += html;
            } else {
                noticeBoard.innerHTML = html;
            }
        } else {
            console.error('API response did not contain success or notices:', data);
            const message = searchTerm ? 'No notices match your search.' : 'No notices available at the moment.';
            noticeBoard.innerHTML = `<p class="no-data">${message}</p>`;
        }
    } catch (error) {
        console.error('Error loading notices:', error);
        noticeBoard.innerHTML = '<p class="no-data">Error loading notices. Please try again later.</p>';
    }
}

function setupNoticeFilter() {
    const filterBtns = document.querySelectorAll('.notice-filter .filter-btn');
    const searchInput = document.getElementById('searchInput');
    
    // Filter by type functionality
    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            
            applyFilters();
        });
    });
    
    // Search functionality
    if (searchInput) {
        // Add search indicator
        searchInput.addEventListener('input', function() {
            if (this.value.trim() !== '') {
                this.parentElement.classList.add('search-active');
            } else {
                this.parentElement.classList.remove('search-active');
            }
        });
        
        // Debounced search to improve performance
        let searchTimeout;
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                applyFilters();
            }, 300); // 300ms delay
        });
        
        const clearButton = document.getElementById('clearSearch');
        
        // Clear search functionality
        if (clearButton) {
            clearButton.addEventListener('click', () => {
                searchInput.value = '';
                clearButton.style.display = 'none';
                searchInput.focus();
                applyFilters();
            });
        }
        
        // Add keyboard shortcuts
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                this.value = '';
                if (clearButton) clearButton.style.display = 'none';
                applyFilters();
            }
        });
        
        // Show/hide clear button based on input
        searchInput.addEventListener('input', () => {
            if (clearButton) {
                clearButton.style.display = searchInput.value ? 'block' : 'none';
            }
        });
        
        // Initialize clear button visibility
        if (clearButton) {
            clearButton.style.display = searchInput.value ? 'block' : 'none';
        }
        
        // Initialize clear button visibility on load
        if (clearButton && searchInput.value) {
            clearButton.style.display = 'block';
        }
    }
}


function getBadgeColor(type) {
    // Map database notice types to display types for badge coloring
    const displayType = mapNoticeType(type);
    const colors = {
        'Notice': '#3498db',
        'Event': '#9b59b6',
        'Achievement': '#27ae60',
        'Holiday': '#e74c3c'
    };
    return colors[displayType] || '#95a5a6';
}

function mapNoticeType(dbType) {
    // Map database notice types to display types for filtering
    const typeMap = {
        'academic': 'Notice',
        'administrative': 'Notice',
        'event': 'Event',
        'holiday': 'Holiday',
        'general': 'Notice',
        'achievement': 'Achievement'
    };
    return typeMap[dbType] || 'Notice';
}

function formatDate(dateString) {
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    } catch (e) {
        console.error('Error formatting date:', dateString, e);
        return dateString;
    }
}

function highlightSearchTerms(text, searchTerm) {
    if (!searchTerm || searchTerm.trim() === '') {
        return text;
    }
    
    // Escape special regex characters in the search term
    const escapedTerm = searchTerm.replace(/[.*+?^${}()|\[\]\\]/g, '\$&');
    const regex = new RegExp(`(${escapedTerm})`, 'gi');
    return text.replace(regex, '<mark class="search-highlight">$1</mark>');
}



// Update applyFilters function to work with search
function applyFilters() {
    const activeFilter = document.querySelector('.notice-filter .filter-btn.active');
    const selectedType = activeFilter ? activeFilter.dataset.type : 'all';
    const searchTerm = document.getElementById('searchInput')?.value.toLowerCase() || '';
    
    // If there's a search term, use the search API instead of client-side filtering
    if (searchTerm.trim() !== '') {
        loadNoticeBoard(searchTerm);
        return; // Exit since we're reloading notices via API
    }
    
    // Otherwise, use client-side filtering
    const noticeItems = document.querySelectorAll('.notice-item');
    
    noticeItems.forEach(item => {
        const itemType = item.dataset.type;
        
        // Check type filter
        const typeMatch = selectedType === 'all' || itemType === selectedType;
        
        // Apply visibility based on type filter only
        if (typeMatch) {
            item.classList.remove('hidden');
        } else {
            item.classList.add('hidden');
        }
    });
}


