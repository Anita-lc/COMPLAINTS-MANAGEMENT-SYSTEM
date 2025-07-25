// Simple routing system
const pages = document.querySelectorAll('.page');
const navLinks = document.querySelectorAll('.nav-link');
const pageButtons = document.querySelectorAll('[data-page]');

function showPage(pageId) {
    // Hide all pages
    pages.forEach(page => page.classList.remove('active'));
    
    // Show selected page
    const targetPage = document.getElementById(pageId);
    if (targetPage) {
        targetPage.classList.add('active');
    } else {
        document.getElementById('not-found').classList.add('active');
    }

    // Update active nav link
    navLinks.forEach(link => link.classList.remove('active'));
    const activeLink = document.querySelector(`[data-page="${pageId}"]`);
    if (activeLink && activeLink.classList.contains('nav-link')) {
        activeLink.classList.add('active');
    }

    // Update URL hash
    window.history.pushState({}, '', `#${pageId}`);
}

// Handle navigation clicks
pageButtons.forEach(button => {
    button.addEventListener('click', (e) => {
        e.preventDefault();
        const pageId = button.getAttribute('data-page');
        showPage(pageId);
    });
});

// Handle browser back/forward buttons
window.addEventListener('popstate', () => {
    const hash = window.location.hash.substring(1);
    showPage(hash || 'home');
});

// Initialize page based on URL hash
const initialPage = window.location.hash.substring(1) || 'home';
showPage(initialPage);

document.addEventListener('DOMContentLoaded', () => {
    const complaintModal = new bootstrap.Modal(document.getElementById('complaintModal'));
    const complaintDetailsModal = new bootstrap.Modal(document.getElementById('complaintDetailsModal'));

    const fetchComplaints = async () => {
        try {
            const response = await fetch('get_complaints.php');
            const complaints = await response.json();
            const complaintsList = document.getElementById('complaints-list');
            complaintsList.innerHTML = ''; // Clear existing list

            if (complaints.length === 0) {
                complaintsList.innerHTML = '<tr><td colspan="5" class="text-center">No complaints found.</td></tr>';
                return;
            }

            complaints.forEach(c => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${c.id}</td>
                    <td>${c.category}</td>
                    <td>${new Date(c.created_at).toLocaleDateString()}</td>
                    <td><span class="badge bg-warning text-dark">${c.status}</span></td>
                    <td>
                        <button class="btn btn-sm btn-info view-details-btn" data-id="${c.id}">Details</button>
                    </td>
                `;
                complaintsList.appendChild(row);
            });
        } catch (error) {
            console.error('Failed to fetch complaints:', error);
        }
    };

    const fetchStatistics = async () => {
        try {
            const response = await fetch('get_statistics.php');
            const stats = await response.json();
            document.getElementById('open-count').textContent = stats.open || 0;
            document.getElementById('in-progress-count').textContent = stats.in_progress || 0;
            document.getElementById('resolved-count').textContent = stats.resolved || 0;
        } catch (error) {
            console.error('Failed to fetch statistics:', error);
        }
    };

    document.getElementById('new-complaint-btn').addEventListener('click', () => {
        complaintModal.show();
    });

    document.getElementById('submit-complaint-btn').addEventListener('click', async () => {
        const formData = new FormData();
        formData.append('category', document.getElementById('category').value);
        formData.append('description', document.getElementById('description').value);
        const attachment = document.getElementById('attachment').files[0];
        if (attachment) {
            formData.append('attachment', attachment);
        }

        try {
            const response = await fetch('submit_complaint.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                alert('Complaint submitted successfully!');
                complaintModal.hide();
                document.getElementById('complaint-form').reset();
                fetchComplaints();
                fetchStatistics();
            } else {
                alert(result.error || 'Failed to submit complaint.');
            }
        } catch (error) {
            console.error('Submission error:', error);
            alert('An error occurred during submission.');
        }
    });

    document.getElementById('complaints-list').addEventListener('click', async (e) => {
        if (e.target.classList.contains('view-details-btn')) {
            const complaintId = e.target.dataset.id;
            try {
                const response = await fetch(`get_complaints.php?id=${complaintId}`);
                const complaint = await response.json();
                const detailsBody = document.getElementById('complaint-details-body');
                detailsBody.innerHTML = `
                    <p><strong>ID:</strong> ${complaint.id}</p>
                    <p><strong>Category:</strong> ${complaint.category}</p>
                    <p><strong>Status:</strong> ${complaint.status}</p>
                    <p><strong>Submitted:</strong> ${new Date(complaint.created_at).toLocaleString()}</p>
                    <h6>Description</h6>
                    <p>${complaint.description}</p>
                    ${complaint.attachment ? `<h6>Attachment</h6><a href="uploads/${complaint.attachment}" target="_blank">View Attachment</a>` : ''}
                `;
                complaintDetailsModal.show();
            } catch (error) {
                console.error('Failed to fetch complaint details:', error);
            }
        }
    });

document.getElementById('logout-btn').addEventListener('click', function () {
    window.location.href = 'logout.php';
});

    });

    // Initial data load
    fetchComplaints();
    fetchStatistics();
