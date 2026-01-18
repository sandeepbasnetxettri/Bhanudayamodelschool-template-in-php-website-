// Notice Board JavaScript
document.addEventListener('DOMContentLoaded', () => {
    loadNoticeBoard();
    setupNoticeFilter();
});

async function loadNoticeBoard() {
    const noticeBoard = document.getElementById('noticeBoard');
    if (!noticeBoard) return;

    // Show loading indicator
    noticeBoard.innerHTML = '<p class="no-data">Loading notices...</p>';
    
    try {
        console.log('Fetching notices from API...');
        const response = await fetch('../api/get-notices.php');
        console.log('API response status:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('API response data:', data);
        
        if (data.success && data.notices) {
            const notices = data.notices;
            console.log(`Loaded ${notices.length} notices from API`);
            
            if (notices.length === 0) {
                noticeBoard.innerHTML = '<p class="no-data">No notices available at the moment.</p>';
                return;
            }
            
            const html = notices.map(notice => {
                const displayType = mapNoticeType(notice.type);
                const badgeColor = getBadgeColor(notice.type);
                const imageUrl = notice.image || 'images/news-default.jpg';
                return `
                    <div class="notice-item" data-type="${displayType}">
                        ${notice.image ? `<div class="notice-image">
                            <img src="${imageUrl}" alt="${notice.title}" onerror="this.src='images/news-default.jpg';this.onerror=null;">
                        </div>` : ''}
                        <div class="notice-header">
                            <div class="notice-title">${notice.title}</div>
                            <div class="notice-meta">
                                <span class="notice-badge" style="background: ${badgeColor};">${displayType}</span>
                                <span class="notice-date">${formatDate(notice.date)}</span>
                            </div>
                        </div>
                        <div class="notice-content">${notice.description}</div>
                    </div>
                `;
            }).join('');

            noticeBoard.innerHTML = html;
        } else {
            console.error('API response did not contain success or notices:', data);
            noticeBoard.innerHTML = '<p class="no-data">No notices available at the moment.</p>';
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
        searchInput.addEventListener('input', () => {
            applyFilters();
        });
    }
}

function applyFilters() {
    const activeFilter = document.querySelector('.notice-filter .filter-btn.active');
    const selectedType = activeFilter ? activeFilter.dataset.type : 'all';
    const searchTerm = document.getElementById('searchInput')?.value.toLowerCase() || '';
    
    const noticeItems = document.querySelectorAll('.notice-item');
    
    noticeItems.forEach(item => {
        const itemType = item.dataset.type;
        const titleText = item.querySelector('.notice-title')?.textContent.toLowerCase() || '';
        const contentText = item.querySelector('.notice-content')?.textContent.toLowerCase() || '';
        const dateText = item.querySelector('.notice-date')?.textContent.toLowerCase() || '';
        
        // Check type filter
        const typeMatch = selectedType === 'all' || itemType === selectedType;
        
        // Check search term
        const searchMatch = !searchTerm || 
                           titleText.includes(searchTerm) || 
                           contentText.includes(searchTerm) || 
                           dateText.includes(searchTerm);
        
        // Apply visibility based on both filters
        if (typeMatch && searchMatch) {
            item.classList.remove('hidden');
        } else {
            item.classList.add('hidden');
        }
    });
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


