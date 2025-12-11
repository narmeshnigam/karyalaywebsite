<?php
/**
 * Admin Lead Detail Page
 * Displays lead profile, message, notes, and timeline
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_helpers.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
require_once __DIR__ . '/../../includes/template_helpers.php';

use Karyalay\Models\Lead;

// Start secure session
startSecureSession();

// Require admin authentication and leads.view_details permission
require_admin();
require_permission('leads.view_details');

// Get lead ID from query parameter
$lead_id = $_GET['id'] ?? '';

if (empty($lead_id)) {
    header('Location: ' . get_app_base_url() . '/admin/leads.php');
    exit;
}

// Initialize models
$leadModel = new Lead();

// Fetch lead details
try {
    $lead = $leadModel->findById($lead_id);
    
    if (!$lead) {
        header('Location: ' . get_app_base_url() . '/admin/leads.php');
        exit;
    }
} catch (Exception $e) {
    error_log("Lead fetch error: " . $e->getMessage());
    header('Location: ' . get_app_base_url() . '/admin/leads.php');
    exit;
}

// Fetch all notes for this lead
try {
    $notes = $leadModel->getNotes($lead_id);
} catch (Exception $e) {
    error_log("Notes fetch error: " . $e->getMessage());
    $notes = [];
}

// Calculate statistics
$total_notes = count($notes);
$days_since_received = floor((time() - strtotime($lead['created_at'])) / 86400);

// Include admin header
include_admin_header('Lead Details');
?>

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <div class="breadcrumb">
            <a href="<?php echo get_app_base_url(); ?>/admin/leads.php" class="breadcrumb-link">Leads</a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?php echo htmlspecialchars($lead['name']); ?></span>
        </div>
        <h1 class="admin-page-title"><?php echo htmlspecialchars($lead['name']); ?></h1>
        <p class="admin-page-description">Lead received <?php echo date('F j, Y', strtotime($lead['created_at'])); ?></p>
    </div>
</div>

<!-- Lead Profile Card -->
<div class="admin-card">
    <div class="card-header">
        <h2 class="card-title">Lead Profile</h2>
    </div>
    <div class="card-body">
        <div class="customer-profile-grid">
            <div class="profile-field">
                <label class="profile-label">Full Name</label>
                <p class="profile-value"><?php echo htmlspecialchars($lead['name']); ?></p>
            </div>
            <div class="profile-field">
                <label class="profile-label">Email Address</label>
                <p class="profile-value">
                    <a href="mailto:<?php echo htmlspecialchars($lead['email']); ?>" class="profile-link">
                        <?php echo htmlspecialchars($lead['email']); ?>
                    </a>
                </p>
            </div>
            <div class="profile-field">
                <label class="profile-label">Phone Number</label>
                <p class="profile-value">
                    <?php if (!empty($lead['phone'])): ?>
                        <a href="tel:<?php echo htmlspecialchars($lead['phone']); ?>" class="profile-link">
                            <?php echo htmlspecialchars($lead['phone']); ?>
                        </a>
                    <?php else: ?>
                        <span class="text-muted">Not provided</span>
                    <?php endif; ?>
                </p>
            </div>
            <div class="profile-field">
                <label class="profile-label">Company</label>
                <p class="profile-value">
                    <?php 
                    $company = $lead['company'] ?? $lead['company_name'] ?? '';
                    if (!empty($company)): 
                    ?>
                        <?php echo htmlspecialchars($company); ?>
                    <?php else: ?>
                        <span class="text-muted">Not provided</span>
                    <?php endif; ?>
                </p>
            </div>
            <div class="profile-field">
                <label class="profile-label">Lead Status</label>
                <p class="profile-value">
                    <?php 
                    $status_config = [
                        'NEW' => 'info',
                        'CONTACTED' => 'warning',
                        'QUALIFIED' => 'warning',
                        'CONVERTED' => 'success',
                        'LOST' => 'danger'
                    ];
                    echo get_status_badge($lead['status'], $status_config);
                    ?>
                </p>
            </div>
            <div class="profile-field">
                <label class="profile-label">Source</label>
                <p class="profile-value">
                    <span class="badge badge-secondary">
                        <?php echo htmlspecialchars($lead['source'] ?? 'website'); ?>
                    </span>
                </p>
            </div>
            <div class="profile-field">
                <label class="profile-label">Date Received</label>
                <p class="profile-value"><?php echo date('F j, Y g:i A', strtotime($lead['created_at'])); ?></p>
            </div>
            <?php if ($lead['updated_at'] !== $lead['created_at']): ?>
                <div class="profile-field">
                    <label class="profile-label">Last Updated</label>
                    <p class="profile-value"><?php echo date('F j, Y g:i A', strtotime($lead['updated_at'])); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="stats-grid">
    <?php 
    render_admin_card(
        'Days Since Received',
        format_number($days_since_received),
        'Days since lead was captured',
        ''
    );
    
    render_admin_card(
        'Total Notes',
        format_number($total_notes),
        'Notes added to this lead',
        ''
    );
    
    render_admin_card(
        'Lead Status',
        $lead['status'],
        'Current lead status',
        '🎯'
    );
    ?>
</div>

<!-- Quick Actions Card -->
<div class="admin-card">
    <div class="card-header">
        <h2 class="card-title">Quick Actions</h2>
    </div>
    <div class="card-body">
        <div class="quick-actions-grid">
            <a href="mailto:<?php echo htmlspecialchars($lead['email']); ?>" class="quick-action-btn quick-action-email">
                <div class="quick-action-content">
                    <span class="quick-action-label">Send Email</span>
                    <span class="quick-action-value"><?php echo htmlspecialchars($lead['email']); ?></span>
                </div>
            </a>
            <?php if (!empty($lead['phone'])): ?>
                <a href="tel:<?php echo htmlspecialchars($lead['phone']); ?>" class="quick-action-btn quick-action-call">
                    <span class="quick-action-icon">📞</span>
                    <div class="quick-action-content">
                        <span class="quick-action-label">Call Lead</span>
                        <span class="quick-action-value"><?php echo htmlspecialchars($lead['phone']); ?></span>
                    </div>
                </a>
            <?php else: ?>
                <div class="quick-action-btn quick-action-disabled">
                    <span class="quick-action-icon">📞</span>
                    <div class="quick-action-content">
                        <span class="quick-action-label">Call Lead</span>
                        <span class="quick-action-value text-muted">No phone number</span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Status Management Section -->
<div class="admin-card">
    <div class="card-header">
        <h2 class="card-title">Status Management</h2>
    </div>
    <div class="card-body">
        <div class="status-management-section">
            <p class="section-description">Update the lead status to track progress through your sales pipeline.</p>
            <div class="status-buttons-container">
                <?php 
                $statuses = [
                    'NEW' => ['label' => 'New', 'desc' => 'Fresh lead, not yet contacted'],
                    'CONTACTED' => ['label' => 'Contacted', 'desc' => 'Initial contact made'],
                    'QUALIFIED' => ['label' => 'Qualified', 'desc' => 'Lead is sales-ready'],
                    'CONVERTED' => ['label' => 'Converted', 'desc' => 'Successfully converted'],
                    'LOST' => ['label' => 'Lost', 'desc' => 'Lead did not convert']
                ];
                foreach ($statuses as $status => $info): 
                ?>
                    <button type="button" 
                            class="status-update-btn <?php echo $lead['status'] === $status ? 'active' : ''; ?>"
                            onclick="updateStatus('<?php echo $status; ?>')"
                            <?php echo $lead['status'] === $status ? 'disabled' : ''; ?>>
                        <span class="status-btn-label"><?php echo $info['label']; ?></span>
                        <span class="status-btn-desc"><?php echo $info['desc']; ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Message Section -->
<?php if (!empty($lead['message'])): ?>
    <div class="admin-card">
        <div class="card-header">
            <h2 class="card-title">Message from Lead</h2>
        </div>
        <div class="card-body">
            <div class="message-display-box">
                <?php echo nl2br(htmlspecialchars($lead['message'])); ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Notes Section -->
<div class="admin-card">
    <div class="card-header">
        <h2 class="card-title">Notes (<?php echo count($notes); ?>)</h2>
    </div>
    
    <div class="card-body">
        <!-- Add Note Form -->
        <div class="add-note-section">
            <form id="addNoteForm" class="note-form-inline">
                <textarea id="noteText" class="note-textarea-input" placeholder="Add a note about this lead..." rows="3" required></textarea>
                <button type="submit" class="btn btn-primary btn-sm">Add Note</button>
            </form>
        </div>
        
        <!-- Notes List -->
        <?php if (empty($notes)): ?>
            <div class="empty-state-small" id="notesEmpty">
                <p class="empty-state-text">No notes yet. Add your first note above.</p>
            </div>
        <?php else: ?>
            <div class="notes-list-container" id="notesList">
                <?php foreach ($notes as $note): ?>
                    <div class="note-item-card" data-note-id="<?php echo htmlspecialchars($note['id']); ?>">
                        <div class="note-item-header">
                            <div class="note-author-section">
                                <span class="note-author-name"><?php echo htmlspecialchars($note['user_name'] ?? 'Admin'); ?></span>
                                <span class="note-timestamp"><?php echo get_relative_time($note['created_at']); ?></span>
                            </div>
                            <button type="button" class="note-delete-button" onclick="deleteNote('<?php echo htmlspecialchars($note['id']); ?>')" title="Delete note">
                                Delete
                            </button>
                        </div>
                        <div class="note-item-content">
                            <?php echo nl2br(htmlspecialchars($note['note'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.admin-page-header {
    margin-bottom: var(--spacing-6);
}

.admin-card {
    margin-bottom: var(--spacing-6);
}

.admin-card:last-child {
    margin-bottom: 0;
}

.breadcrumb {
    display: flex;
    align-items: center;
    gap: var(--spacing-2);
    margin-bottom: var(--spacing-3);
    font-size: var(--font-size-sm);
}

.breadcrumb-link {
    color: var(--color-primary);
    text-decoration: none;
}

.breadcrumb-link:hover {
    text-decoration: underline;
}

.breadcrumb-separator {
    color: var(--color-gray-400);
}

.breadcrumb-current {
    color: var(--color-gray-600);
}

.admin-page-title {
    font-size: var(--font-size-2xl);
    font-weight: var(--font-weight-bold);
    color: var(--color-gray-900);
    margin: 0 0 var(--spacing-2) 0;
}

.admin-page-description {
    font-size: var(--font-size-base);
    color: var(--color-gray-600);
    margin: 0;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--spacing-4) var(--spacing-5);
    border-bottom: 1px solid var(--color-gray-200);
}

.card-title {
    font-size: var(--font-size-lg);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-900);
    margin: 0;
}

.card-body {
    padding: var(--spacing-5);
}

.customer-profile-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: var(--spacing-6);
}

.profile-field {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-2);
}

.profile-label {
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-700);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.profile-value {
    font-size: var(--font-size-base);
    color: var(--color-gray-900);
    margin: 0;
}

.profile-link {
    color: var(--color-primary);
    text-decoration: none;
}

.profile-link:hover {
    text-decoration: underline;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: var(--spacing-4);
    margin-bottom: var(--spacing-6);
}

.status-management-section {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-4);
}

.section-description {
    color: var(--color-gray-600);
    font-size: var(--font-size-sm);
    margin: 0;
}

.status-buttons-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--spacing-3);
}

.status-update-btn {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    padding: var(--spacing-3);
    border: 2px solid var(--color-gray-300);
    border-radius: var(--radius-lg);
    background: var(--color-white);
    cursor: pointer;
    transition: all var(--transition-fast);
    text-align: left;
}

.status-update-btn:hover:not(:disabled) {
    border-color: var(--color-primary);
    background: var(--color-gray-50);
}

.status-update-btn.active {
    background: var(--color-primary);
    border-color: var(--color-primary);
    color: var(--color-white);
}

.status-update-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.status-btn-label {
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-semibold);
    margin-bottom: var(--spacing-1);
}

.status-btn-desc {
    font-size: var(--font-size-xs);
    opacity: 0.8;
}

.message-display-box {
    background: var(--color-gray-50);
    padding: var(--spacing-4);
    border-radius: var(--radius-lg);
    color: var(--color-gray-700);
    line-height: 1.6;
    border-left: 3px solid var(--color-primary);
}

.add-note-section {
    padding-bottom: var(--spacing-5);
    margin-bottom: var(--spacing-5);
    border-bottom: 1px solid var(--color-gray-200);
}

.note-form-inline {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-3);
}

.note-textarea-input {
    width: 100%;
    padding: var(--spacing-3);
    border: 1px solid var(--color-gray-300);
    border-radius: var(--radius-lg);
    font-size: var(--font-size-sm);
    font-family: inherit;
    resize: vertical;
}

.note-textarea-input:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.empty-state-small {
    padding: var(--spacing-8) var(--spacing-4);
    text-align: center;
}

.empty-state-text {
    color: var(--color-gray-500);
    font-style: italic;
    margin: 0;
}

.notes-list-container {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-4);
}

.note-item-card {
    background: var(--color-gray-50);
    border-radius: var(--radius-lg);
    padding: var(--spacing-4);
    border-left: 3px solid var(--color-primary);
}

.note-item-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: var(--spacing-3);
}

.note-author-section {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-1);
}

.note-author-name {
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-900);
    font-size: var(--font-size-sm);
}

.note-timestamp {
    color: var(--color-gray-500);
    font-size: var(--font-size-xs);
}

.note-delete-button {
    background: none;
    border: none;
    color: var(--color-red-600);
    cursor: pointer;
    font-size: var(--font-size-xs);
    font-weight: var(--font-weight-medium);
    padding: var(--spacing-1) var(--spacing-2);
    border-radius: var(--radius-md);
    transition: background var(--transition-fast);
}

.note-delete-button:hover {
    background: var(--color-red-50);
}

.note-item-content {
    color: var(--color-gray-700);
    font-size: var(--font-size-sm);
    line-height: 1.6;
}

.text-muted {
    color: var(--color-gray-500);
    font-style: italic;
}

.quick-actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: var(--spacing-4);
}

.quick-action-btn {
    display: flex;
    align-items: center;
    gap: var(--spacing-3);
    padding: var(--spacing-4);
    border: 2px solid var(--color-gray-200);
    border-radius: var(--radius-lg);
    background: var(--color-white);
    text-decoration: none;
    transition: all var(--transition-fast);
}

.quick-action-btn:hover:not(.quick-action-disabled) {
    border-color: var(--color-primary);
    background: var(--color-gray-50);
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.quick-action-email:hover {
    border-color: var(--color-primary);
}

.quick-action-call:hover {
    border-color: #10b981;
}

.quick-action-disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.quick-action-icon {
    font-size: var(--font-size-2xl);
    flex-shrink: 0;
}

.quick-action-content {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-1);
}

.quick-action-label {
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-900);
}

.quick-action-value {
    font-size: var(--font-size-xs);
    color: var(--color-gray-600);
}

@media (max-width: 768px) {
    .customer-profile-grid {
        grid-template-columns: 1fr;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .status-buttons-container {
        grid-template-columns: 1fr;
    }
    
    .quick-actions-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
const leadId = '<?php echo htmlspecialchars($lead_id); ?>';
const apiBase = '<?php echo get_app_base_url(); ?>/admin/api';

// Add Note
document.getElementById('addNoteForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const noteText = document.getElementById('noteText').value.trim();
    if (!noteText) return;
    
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Adding...';
    
    try {
        const response = await fetch(`${apiBase}/lead-notes.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ lead_id: leadId, note: noteText })
        });
        
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('noteText').value = '';
            
            const emptyState = document.getElementById('notesEmpty');
            if (emptyState) {
                emptyState.remove();
            }
            
            let notesList = document.getElementById('notesList');
            if (!notesList) {
                notesList = document.createElement('div');
                notesList.id = 'notesList';
                notesList.className = 'notes-list-container';
                this.parentElement.parentElement.appendChild(notesList);
            }
            
            const noteHtml = `
                <div class="note-item-card" data-note-id="${data.note.id}">
                    <div class="note-item-header">
                        <div class="note-author-section">
                            <span class="note-author-name">${escapeHtml(data.note.user_name || 'Admin')}</span>
                            <span class="note-timestamp">just now</span>
                        </div>
                        <button type="button" class="note-delete-button" onclick="deleteNote('${data.note.id}')" title="Delete note">Delete</button>
                    </div>
                    <div class="note-item-content">${escapeHtml(data.note.note).replace(/\n/g, '<br>')}</div>
                </div>
            `;
            notesList.insertAdjacentHTML('afterbegin', noteHtml);
            
            // Update count in header
            const cardTitle = document.querySelector('.card-title');
            if (cardTitle && cardTitle.textContent.includes('Notes')) {
                const currentCount = parseInt(cardTitle.textContent.match(/\d+/)?.[0] || 0);
                cardTitle.textContent = `Notes (${currentCount + 1})`;
            }
        } else {
            alert(data.error || 'Failed to add note');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred while adding the note');
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    }
});

// Delete Note
async function deleteNote(noteId) {
    if (!confirm('Are you sure you want to delete this note?')) return;
    
    try {
        const response = await fetch(`${apiBase}/lead-notes.php`, {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ note_id: noteId, lead_id: leadId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            const noteElement = document.querySelector(`[data-note-id="${noteId}"]`);
            if (noteElement) {
                noteElement.remove();
            }
            
            const notesList = document.getElementById('notesList');
            if (notesList && notesList.children.length === 0) {
                notesList.remove();
                const emptyState = document.createElement('div');
                emptyState.id = 'notesEmpty';
                emptyState.className = 'empty-state-small';
                emptyState.innerHTML = '<p class="empty-state-text">No notes yet. Add your first note above.</p>';
                document.querySelector('.add-note-section').parentElement.appendChild(emptyState);
            }
            
            // Update count in header
            const cardTitle = document.querySelector('.card-title');
            if (cardTitle && cardTitle.textContent.includes('Notes')) {
                const currentCount = parseInt(cardTitle.textContent.match(/\d+/)?.[0] || 0);
                cardTitle.textContent = `Notes (${Math.max(0, currentCount - 1)})`;
            }
        } else {
            alert(data.error || 'Failed to delete note');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred while deleting the note');
    }
}

// Update Status
async function updateStatus(newStatus) {
    if (!confirm(`Are you sure you want to change the status to ${newStatus}?`)) return;
    
    try {
        const response = await fetch(`${apiBase}/update-lead-status.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ lead_id: leadId, status: newStatus })
        });
        
        const data = await response.json();
        
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Failed to update status');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred while updating the status');
    }
}

// Helper
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php include_admin_footer(); ?>
