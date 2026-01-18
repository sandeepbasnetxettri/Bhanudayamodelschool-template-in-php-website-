# Gallery System Documentation

## Overview
The gallery system for Bhanudaya Secondary School has been enhanced to properly manage and display local images stored in the `/Gallery` folder. The system now supports both database-driven content and local file storage with proper fallback mechanisms.

## Features Added

### 1. Local Image Support
- The system now uses local images from the `/Gallery` folder (e.g., `Gallery/1.jpg`, `Gallery/2.jpg`, etc.)
- Images are automatically loaded from the local folder when available
- Proper fallback mechanisms exist if local images are unavailable

### 2. Enhanced JavaScript
- Updated `js/gallery.js` to properly handle local images
- Added better error handling for missing images
- Implemented dynamic image loading from local gallery folder
- Improved fallback functionality when API calls fail

### 3. Database Integration
- Gallery items can be managed through the database
- Supports publishing/unpublishing of gallery items
- Category management for organizing gallery content

### 4. Administration Tools
- `manage_gallery.php` - Admin interface for managing gallery items
- `check_gallery_table.php` - Utility to verify and populate gallery table
- `test_gallery.php` - Tool to test local gallery images
- `setup_gallery.html` - Centralized access to gallery tools

## File Structure

```
/Gallery
├── 1.jpg
├── 2.jpg
├── ...
├── 47.jpg
├── js/gallery.js              # Enhanced gallery JavaScript
├── api/get-gallery-items.php  # API endpoint for gallery items
├── pages/gallery.php          # Main gallery page
├── manage_gallery.php         # Admin gallery management
├── check_gallery_table.php    # Database utility
├── test_gallery.php           # Gallery testing tool
└── setup_gallery.html         # Gallery setup dashboard
```

## Setup Instructions

### Initial Setup
1. Place your gallery images in the `/Gallery` folder
2. Ensure images are named sequentially (1.jpg, 2.jpg, etc.) or with descriptive names
3. Access `setup_gallery.html` to initialize the system
4. Use `check_gallery_table.php` to create the database table and populate with local images

### Adding Images
- Simply place new images in the `/Gallery` folder
- Run `check_gallery_table.php` and use the "Add Local Gallery Images to Database" button
- Or use the admin interface `manage_gallery.php` to add images manually

### Managing Gallery Items
1. Log in as an admin user
2. Navigate to `manage_gallery.php`
3. Add, edit, or delete gallery items as needed
4. Toggle publish status for each item

## API Endpoints

### Get Gallery Items
- **URL**: `/api/get-gallery-items.php`
- **Method**: GET
- **Response**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Gallery Image 1",
      "category": "events",
      "date": "Jan 1, 2024",
      "image": "../Gallery/1.jpg",
      "description": "School gallery image 1"
    }
  ]
}
```

## Error Handling

The system includes robust error handling:
- If the API fails, local fallback images are displayed
- If local images are missing, SVG placeholders are shown
- Database errors are caught and handled gracefully
- Image loading errors trigger fallback mechanisms

## Security Considerations

- Gallery management is restricted to admin users only
- All database inputs are properly sanitized
- File uploads (when implemented) will be validated
- Session management ensures only authorized access

## Customization Options

### Changing Categories
The system supports these gallery categories:
- events
- sports
- facilities
- activities
- achievements
- staff
- students
- other

### Adding New Gallery Items
New gallery items can be added through:
1. Direct database insertion
2. Admin management interface
3. Automatic population from the Gallery folder

## Troubleshooting

### Gallery Not Loading
1. Check if the `/Gallery` folder exists and contains images
2. Verify the database table exists and has entries
3. Check browser console for JavaScript errors
4. Ensure proper file permissions on the Gallery folder

### Images Not Displaying
1. Confirm image paths are correct in the database
2. Check if images exist in the `/Gallery` folder
3. Verify file permissions allow web access
4. Look for broken image links in browser developer tools

### API Issues
1. Check if `api/get-gallery-items.php` returns valid JSON
2. Verify database connection in `config/dbconnection.php`
3. Ensure gallery table exists and has data
4. Check server logs for PHP errors

## Future Enhancements

- Bulk image upload functionality
- Image resizing and optimization
- Advanced filtering and search capabilities
- Gallery categorization improvements
- Image metadata support
- Responsive design enhancements