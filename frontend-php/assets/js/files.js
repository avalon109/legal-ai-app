// File Manager JavaScript

class FileManager {
    constructor() {
        // Get base URL using the same logic as in auth.js
        const path = window.location.pathname;
        const firstSegment = path.split('/')[1]; 
        this.baseUrl = firstSegment ? `/${firstSegment}/api` : '/api';
        
        // Show debug info
        console.log('FileManager initialized with baseUrl:', this.baseUrl);
        
        // File upload max size in bytes (10MB)
        this.maxFileSize = 10 * 1024 * 1024;
        
        // Initialize UI elements and state
        this.initializeElements();
        this.attachEventListeners();
        this.setupAuth();
    }
    
    initializeElements() {
        // Get DOM elements
        this.filesContent = document.getElementById('files-content');
        this.authRequiredBanner = document.getElementById('auth-required-banner');
        this.filesAlert = document.getElementById('files-alert');
        this.filesList = document.getElementById('files-list');
        this.noFilesMessage = document.getElementById('no-files-message');
        this.fileSearch = document.getElementById('file-search');
        this.uploadForm = document.getElementById('upload-form');
        this.fileUpload = document.getElementById('file-upload');
        this.fileDescription = document.getElementById('file-description');
        this.uploadAlert = document.getElementById('upload-alert');
        
        // Initialize filters
        this.filterButtons = document.querySelectorAll('[data-filter]');
        this.activeFilter = 'all';
    }
    
    attachEventListeners() {
        // Add event listener for file upload form
        if (this.uploadForm) {
            this.uploadForm.addEventListener('submit', (e) => this.handleFileUpload(e));
        }
        
        // Add event listener for file search
        if (this.fileSearch) {
            this.fileSearch.addEventListener('input', () => this.filterFiles());
        }
        
        // Add event listener to reset upload alert when modal is opened
        const uploadModal = document.getElementById('uploadModal');
        if (uploadModal) {
            uploadModal.addEventListener('show.bs.modal', () => {
                if (this.uploadAlert) {
                    this.uploadAlert.style.display = 'none';
                }
                if (this.uploadForm) {
                    this.uploadForm.reset();
                }
            });
        }
        
        // Add event listeners for filter buttons
        this.filterButtons.forEach(button => {
            button.addEventListener('click', () => {
                // Update active class
                this.filterButtons.forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
                
                // Update active filter
                this.activeFilter = button.getAttribute('data-filter');
                
                // Apply filter
                this.filterFiles();
            });
        });
    }
    
    setupAuth() {
        // Check if user is logged in
        if (!auth.isLoggedIn()) {
            // Show auth required banner
            if (this.authRequiredBanner) {
                this.authRequiredBanner.style.display = 'block';
            }
            
            // Hide files content
            if (this.filesContent) {
                this.filesContent.style.display = 'none';
            }
            
            return;
        }
        
        // User is logged in, hide auth banner and show content
        if (this.authRequiredBanner) {
            this.authRequiredBanner.style.display = 'none';
        }
        
        if (this.filesContent) {
            this.filesContent.style.display = 'block';
        }
        
        // Load files
        this.loadFiles();
    }
    
    // Helper to add auth headers to fetch requests
    async fetchWithAuth(url, options = {}) {
        try {
            return await auth.fetchWithAuth(url, options);
        } catch (error) {
            console.error('Fetch error:', error);
            throw error;
        }
    }
    
    // Load user's files
    async loadFiles() {
        try {
            this.showAlert('Loading your files...', 'info');
            
            const response = await this.fetchWithAuth(`${this.baseUrl}/files.php?action=list`);
            
            if (!response.ok) {
                throw new Error(`Server returned ${response.status}`);
            }
            
            const data = await response.json();
            
            console.log('Files loaded:', data);
            
            if (!data.success) {
                throw new Error(data.message || 'Failed to load files');
            }
            
            this.renderFiles(data.files);
            this.hideAlert();
        } catch (error) {
            console.error('Error loading files:', error);
            this.showAlert(`Error loading files: ${error.message}`, 'danger');
        }
    }
    
    // Render files in the table
    renderFiles(files) {
        // Clean up files list, but keep the no files message
        const rows = this.filesList.querySelectorAll('tr:not(#no-files-message)');
        rows.forEach(row => row.remove());
        
        // Show/hide no files message
        if (files.length === 0) {
            this.noFilesMessage.style.display = 'table-row';
            return;
        } else {
            this.noFilesMessage.style.display = 'none';
        }
        
        // Add files to table
        files.forEach(file => {
            this.addFileRow(file);
        });
        
        // Apply any active filter
        this.filterFiles();
    }
    
    // Add a single file row to the table
    addFileRow(file) {
        const row = document.createElement('tr');
        row.dataset.filename = file.name;
        row.dataset.filetype = this.getFileCategory(file.type);
        row.dataset.fileId = file.id;  // Store file ID in dataset for deletion
        
        // Actions cell with download and delete buttons
        const actionsCell = document.createElement('td');
        actionsCell.className = 'text-nowrap';
        
        // Download button
        const downloadBtn = document.createElement('a');
        downloadBtn.href = file.path;
        downloadBtn.className = 'btn btn-sm btn-outline-primary me-2';
        downloadBtn.download = file.name;
        downloadBtn.innerHTML = '<i class="bi bi-download"></i>';
        downloadBtn.title = 'Download';
        
        // Delete button
        const deleteBtn = document.createElement('button');
        deleteBtn.type = 'button';
        deleteBtn.className = 'btn btn-sm btn-outline-danger';
        deleteBtn.innerHTML = '<i class="bi bi-trash"></i>';
        deleteBtn.title = 'Delete';
        deleteBtn.addEventListener('click', () => this.confirmDeleteFile(file.name, file.id));
        
        actionsCell.appendChild(downloadBtn);
        actionsCell.appendChild(deleteBtn);
        
        // Filename cell with icon
        const nameCell = document.createElement('td');
        nameCell.innerHTML = `
            <div class="d-flex align-items-center">
                <span class="file-icon me-2">${this.getFileIcon(file.type)}</span>
                <span>${file.name}</span>
            </div>
        `;
        
        // Description cell
        const descriptionCell = document.createElement('td');
        descriptionCell.textContent = file.description || '-';
        
        // File type cell
        const typeCell = document.createElement('td');
        typeCell.textContent = file.type;
        
        // File size cell
        const sizeCell = document.createElement('td');
        sizeCell.textContent = this.formatFileSize(file.size);
        
        // Upload date cell
        const dateCell = document.createElement('td');
        dateCell.textContent = new Date(file.uploaded).toLocaleString();
        
        // Add cells to row
        row.appendChild(actionsCell);
        row.appendChild(nameCell);
        row.appendChild(descriptionCell);
        row.appendChild(typeCell);
        row.appendChild(sizeCell);
        row.appendChild(dateCell);
        
        // Add row to table
        this.filesList.appendChild(row);
    }
    
    // Filter files based on search and type filter
    filterFiles() {
        const searchTerm = this.fileSearch ? this.fileSearch.value.toLowerCase() : '';
        const filter = this.activeFilter;
        
        const rows = this.filesList.querySelectorAll('tr:not(#no-files-message)');
        let visibleCount = 0;
        
        rows.forEach(row => {
            const filename = row.dataset.filename.toLowerCase();
            const filetype = row.dataset.filetype;
            
            // Check if matches search
            const matchesSearch = filename.includes(searchTerm);
            
            // Check if matches filter
            const matchesFilter = filter === 'all' || filetype === filter;
            
            // Show/hide row
            if (matchesSearch && matchesFilter) {
                row.style.display = 'table-row';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        // Show/hide no files message
        if (visibleCount === 0) {
            this.noFilesMessage.style.display = 'table-row';
            this.noFilesMessage.querySelector('td').innerHTML = `
                <div class="text-muted">
                    <i class="bi bi-search" style="font-size: 2rem;"></i>
                    <p class="mt-2">No files match your search criteria.</p>
                </div>
            `;
        } else {
            this.noFilesMessage.style.display = 'none';
        }
    }
    
    // Confirm and delete a file
    async confirmDeleteFile(filename, fileId) {
        if (confirm(`Are you sure you want to delete ${filename}?`)) {
            await this.deleteFile(filename, fileId);
        }
    }
    
    // Delete a file
    async deleteFile(filename, fileId) {
        try {
            this.showAlert(`Deleting ${filename}...`, 'info');
            
            const response = await this.fetchWithAuth(
                `${this.baseUrl}/files.php?action=delete&file=${encodeURIComponent(filename)}&id=${encodeURIComponent(fileId)}`,
                { method: 'DELETE' }
            );
            
            if (!response.ok) {
                throw new Error(`Server returned ${response.status}`);
            }
            
            const data = await response.json();
            
            console.log('Delete response:', data);
            
            if (!data.success) {
                throw new Error(data.message || 'Failed to delete file');
            }
            
            this.showAlert(`${filename} deleted successfully.`, 'success');
            
            // Reload files
            this.loadFiles();
        } catch (error) {
            console.error('Error deleting file:', error);
            this.showAlert(`Error deleting file: ${error.message}`, 'danger');
        }
    }
    
    // Handle file upload form submission
    async handleFileUpload(e) {
        e.preventDefault();
        
        // Get file and description
        const file = this.fileUpload.files[0];
        const description = this.fileDescription.value;
        
        if (!file) {
            this.showUploadAlert('Please select a file to upload.', 'danger');
            return;
        }
        
        // Check file size
        if (file.size > this.maxFileSize) {
            this.showUploadAlert(`File too large. Maximum size is ${this.formatFileSize(this.maxFileSize)}.`, 'danger');
            return;
        }
        
        try {
            this.showUploadAlert('Uploading file...', 'info');
            
            // Create form data
            const formData = new FormData();
            formData.append('file', file);
            if (description) {
                formData.append('description', description);
            }
            
            console.log('Uploading file:', file.name, 'Size:', file.size, 'Type:', file.type);
            
            // Get auth token
            const token = localStorage.getItem('auth_token');
            const authHeader = token ? `Bearer ${token}` : '';
            console.log('Using direct fetch with FormData and auth header');
            
            // Use fetch directly instead of fetchWithAuth to ensure proper FormData handling
            const response = await fetch(
                `${this.baseUrl}/files.php?action=upload&debug=true`,
                {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Authorization': authHeader
                    }
                }
            );
            
            console.log('Upload response status:', response.status);
            
            // Try to get response as JSON or text
            let responseData;
            try {
                responseData = await response.json();
                console.log('Response data:', responseData);
            } catch (jsonError) {
                const textResponse = await response.text();
                console.log('Response text:', textResponse);
                throw new Error(`Failed to parse response: ${textResponse}`);
            }
            
            if (!response.ok) {
                throw new Error(`Server error: ${responseData.message || response.status}`);
            }
            
            if (!responseData.success) {
                throw new Error(responseData.message || 'Failed to upload file');
            }
            
            this.showUploadAlert(`${file.name} uploaded successfully.`, 'success');
            
            // Reset form
            this.uploadForm.reset();
            
            // Close modal
            const uploadModal = bootstrap.Modal.getInstance(document.getElementById('uploadModal'));
            if (uploadModal) {
                setTimeout(() => uploadModal.hide(), 1500);
            }
            
            // Reload files
            this.loadFiles();
        } catch (error) {
            console.error('Error uploading file:', error);
            this.showUploadAlert(`Error uploading file: ${error.message}`, 'danger');
        }
    }
    
    // Helper to show file list alert
    showAlert(message, type = 'info') {
        if (!this.filesAlert) return;
        
        this.filesAlert.className = `alert alert-${type}`;
        this.filesAlert.textContent = message;
        this.filesAlert.style.display = 'block';
    }
    
    // Helper to hide file list alert
    hideAlert() {
        if (!this.filesAlert) return;
        
        this.filesAlert.style.display = 'none';
    }
    
    // Helper to show upload alert
    showUploadAlert(message, type = 'info') {
        if (!this.uploadAlert) return;
        
        this.uploadAlert.className = `alert alert-${type}`;
        this.uploadAlert.textContent = message;
        this.uploadAlert.style.display = 'block';
    }
    
    // Helper to format file size
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    // Helper to get icon for file type
    getFileIcon(mimeType) {
        if (mimeType.startsWith('image/')) {
            return '<i class="bi bi-file-image text-primary"></i>';
        } else if (mimeType.startsWith('video/')) {
            return '<i class="bi bi-file-play text-danger"></i>';
        } else if (mimeType.startsWith('audio/')) {
            return '<i class="bi bi-file-music text-success"></i>';
        } else if (mimeType === 'application/pdf') {
            return '<i class="bi bi-file-pdf text-danger"></i>';
        } else if (mimeType.includes('word') || mimeType === 'application/msword') {
            return '<i class="bi bi-file-word text-primary"></i>';
        } else if (mimeType.includes('excel') || mimeType === 'application/vnd.ms-excel') {
            return '<i class="bi bi-file-excel text-success"></i>';
        } else if (mimeType.includes('powerpoint') || mimeType === 'application/vnd.ms-powerpoint') {
            return '<i class="bi bi-file-ppt text-warning"></i>';
        } else if (mimeType === 'application/zip' || mimeType === 'application/x-zip-compressed') {
            return '<i class="bi bi-file-zip text-danger"></i>';
        } else if (mimeType === 'text/plain') {
            return '<i class="bi bi-file-text text-secondary"></i>';
        } else if (mimeType === 'text/html' || mimeType === 'application/xhtml+xml') {
            return '<i class="bi bi-file-code text-info"></i>';
        } else {
            return '<i class="bi bi-file text-secondary"></i>';
        }
    }
    
    // Helper to categorize file types
    getFileCategory(mimeType) {
        if (mimeType.startsWith('image/')) {
            return 'images';
        } else if (mimeType.startsWith('video/')) {
            return 'videos';
        } else if (mimeType.startsWith('audio/')) {
            return 'audio';
        } else if (
            mimeType === 'application/pdf' ||
            mimeType.includes('word') ||
            mimeType === 'application/msword' ||
            mimeType === 'text/plain' ||
            mimeType === 'text/html'
        ) {
            return 'documents';
        } else {
            return 'other';
        }
    }
}

// Initialize file manager on page load
document.addEventListener('DOMContentLoaded', () => {
    // Wait a moment for auth to initialize
    setTimeout(() => {
        window.fileManager = new FileManager();
    }, 100);
}); 