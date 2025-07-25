// Initialize Bootstrap modals
const complaintModal = new bootstrap.Modal(document.getElementById('complaintModal'));
const statusModal = new bootstrap.Modal(document.getElementById('statusModal'));

// Check if user is logged in
if (!localStorage.getItem('user')) {
    window.location.href = 'index.html';
}

// Get user info from localStorage
const user = JSON.parse(localStorage.getItem('user'));
if (user.role !== 'admin') {
    window.location.href = 'index.html';
}

// Load statistics
async function loadStatistics() {
    try {
        const response = await fetch('get_statistics.php');
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('total-complaints').textContent = data.total;
            document.getElementById('open-complaints').textContent = data.open;
            document.getElementById('in-progress').textContent = data.in_progress;
            document.getElementById('resolved').textContent = data.resolved;
        }
    } catch (error) {
        console.error('Error loading statistics:', error);
    }
}

// Load complaints
async function loadComplaints(status = 'all') {
    try {
        const response = await fetch(`get_complaints.php?status=${status}`);
        const data = await response.json();
        
        if (data.success) {
            const tbody = document.getElementById('complaints-table-body');
            tbody.innerHTML = '';
            
            data.complaints.forEach(complaint => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${complaint.id}</td>
                    <td>${complaint.firstName} ${complaint.lastName}</td>
                    <td>${complaint.category}</td>
                    <td><span class="badge bg-${getStatusBadgeClass(complaint.status)}">${complaint.status}</span></td>
                    <td>${new Date(complaint.created_at).toLocaleDateString()}</td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="viewComplaint(${complaint.id})">
                            View
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }
    } catch (error) {
        console.error('Error loading complaints:', error);
    }
}

// Get status badge class
function getStatusBadgeClass(status) {
    switch (status.toLowerCase()) {
        case 'open':
            return 'warning';
        case 'in progress':
            return 'info';
        case 'resolved':
            return 'success';
        default:
            return 'secondary';
    }
}

// View complaint details
async function viewComplaint(id) {
    try {
        const response = await fetch(`get_complaint.php?id=${id}`);
        const data = await response.json();
        
        if (data.success) {
            const complaint = data.complaint;
            
            // Update modal content
            document.getElementById('modal-complaint-id').textContent = complaint.id;
            document.getElementById('modal-student-name').textContent = `${complaint.first_name} ${complaint.last_name}`;
            document.getElementById('modal-email').textContent = complaint.email || 'N/A';
            document.getElementById('modal-category').textContent = complaint.category;
            document.getElementById('modal-status').textContent = complaint.status;
            document.getElementById('modal-submitted').textContent = new Date(complaint.created_at).toLocaleString();
            document.getElementById('modal-updated').textContent = new Date(complaint.updated_at).toLocaleString();
            document.getElementById('modal-remarks').textContent = complaint.admin_remarks || 'No remarks';
            document.getElementById('modal-description').textContent = complaint.description;
            
            // Handle screenshot
            const screenshotDiv = document.getElementById('modal-screenshot');
            screenshotDiv.innerHTML = complaint.screenshot ? 
                `<img src="${complaint.screenshot}" class="img-fluid" alt="Complaint screenshot">` :
                'No screenshot available';
            
            complaintModal.show();
        }
    } catch (error) {
        console.error('Error viewing complaint:', error);
        alert('Failed to load complaint details');
    }
}

// Open status update modal
function openUpdateStatusModal() {
    statusModal.show();
}

// Update complaint status
async function updateStatus() {
    const complaintId = document.getElementById('modal-complaint-id').textContent;
    const newStatus = document.getElementById('status-select').value;
    const remarks = document.getElementById('remarks').value;

    try {
        const response = await fetch('update_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                id: complaintId,
                status: newStatus,
                remarks: remarks
            })
        });

        const result = await response.json();
        
        if (result.success) {
            statusModal.hide();
            complaintModal.hide();
            loadComplaints();
            loadStatistics();
            alert('Status updated successfully!');
        } else {
            alert('Failed to update status: ' + result.error);
        }
    } catch (error) {
        console.error('Error updating status:', error);
        alert('Failed to update status');
    }
}

// Filter complaints
function filterComplaints(status) {
    loadComplaints(status);
}

// Logout function
function logout() {
    // Clear localStorage
    localStorage.removeItem('user');
    
    // Redirect to login page
    window.location.href = 'index.html';
}

// Initialize user name display
document.addEventListener('DOMContentLoaded', () => {
    const user = JSON.parse(localStorage.getItem('user'));
    if (user) {
        document.getElementById('user-name').textContent = `Welcome, ${user.role === 'admin' ? 'Admin' : user.id}`;
    }
    loadStatistics();
    loadComplaints();
});