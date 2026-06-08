<?php
$pageTitle = 'User Management';
$activePage = 'users';
require 'header.php';
requirePermission('manage_users');
?>

<style>
    .form-card {
        background: var(--surface);
        border: 1px solid var(--border);
        padding: 24px;
        margin-bottom: 24px;
        border-radius: 8px;
    }

    .form-card h3 {
        font-size: 16px;
        font-weight: 600;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 1px solid var(--border);
        color: var(--text);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 20px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
    }

    .form-group label {
        font-size: 11px;
        font-family: var(--mono);
        color: var(--text-muted);
        text-transform: uppercase;
        margin-bottom: 6px;
    }

    .form-group input, .form-group select {
        padding: 10px 14px;
        border: 1px solid var(--border);
        border-radius: 6px;
        background: var(--bg);
        color: var(--text);
        font-size: 14px;
        outline: none;
        transition: border 0.2s;
    }

    .form-group input:focus, .form-group select:focus {
        border-color: var(--accent);
        background: var(--surface);
    }

    .btn-action {
        padding: 10px 20px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        border: none;
        transition: all 0.2s;
    }

    .btn-primary {
        background: var(--accent);
        color: #ffffff;
    }

    .btn-primary:hover {
        background: var(--accent-dim);
    }

    .btn-secondary {
        background: var(--surface2);
        color: var(--text);
        border: 1px solid var(--border);
    }

    .btn-secondary:hover {
        background: var(--border);
    }

    .btn-danger {
        background: var(--danger);
        color: #ffffff;
        padding: 6px 12px;
        font-size: 11px;
    }

    .btn-danger:hover {
        opacity: 0.9;
    }

    .btn-edit {
        background: var(--accent);
        color: #ffffff;
        padding: 6px 12px;
        font-size: 11px;
    }

    .btn-edit:hover {
        opacity: 0.9;
    }

    .alert {
        padding: 12px 16px;
        border-radius: 6px;
        font-size: 13px;
        margin-bottom: 20px;
        display: none;
    }

    .alert-success {
        background: rgba(0, 229, 160, 0.1);
        border: 1px solid var(--accent);
        color: var(--accent);
    }

    .alert-error {
        background: rgba(255, 79, 79, 0.1);
        border: 1px solid var(--danger);
        color: var(--danger);
    }
</style>

<div class="topbar">
    <div>
        <div class="breadcrumb"><span>/</span> system admin</div>
        <h2>User Accounts</h2>
    </div>
</div>

<div class="content">
    <!-- Feedback Alerts -->
    <div id="alert-success" class="alert alert-success"></div>
    <div id="alert-error" class="alert alert-error"></div>

    <!-- User Form Card -->
    <div class="form-card">
        <h3 id="form-title">Create New User</h3>
        <form id="user-form" onsubmit="handleFormSubmit(event)">
            <input type="hidden" id="form-userid" name="user_id" value="">
            <div class="form-grid">
                <div class="form-group">
                    <label for="form-username">Username</label>
                    <input type="text" id="form-username" name="username" required placeholder="e.g. jsmith">
                </div>
                <div class="form-group">
                    <label for="form-password" id="password-label">Password</label>
                    <input type="password" id="form-password" name="password" required placeholder="Min 6 characters">
                </div>
                <div class="form-group">
                    <label for="form-role">Role</label>
                    <select id="form-role" name="role" required>
                        <option value="Admin">Admin (Full Access)</option>
                        <option value="Analyst">Analyst (Edit/Filter/Export)</option>
                        <option value="Viewer" selected>Viewer (Read-Only)</option>
                    </select>
                </div>
            </div>
            <div style="display:flex; gap:8px; justify-content:flex-end;">
                <button type="button" id="btn-cancel" class="btn-action btn-secondary" onclick="resetForm()" style="display:none;">Cancel</button>
                <button type="submit" id="btn-submit" class="btn-action btn-primary">Save User</button>
            </div>
        </form>
    </div>

    <!-- Users List Table -->
    <div class="table-wrap">
        <div class="table-header">
            <h3>Registered Portal Users</h3>
        </div>
        <table>
            <thead>
                <tr>
                    <th style="width: 80px;">ID</th>
                    <th>Username</th>
                    <th>Assigned Role</th>
                    <th>Created At</th>
                    <th style="width: 180px; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody id="users-tbody">
                <tr><td colspan="5" class="empty">Loading users...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
    let currentEditingId = null;

    function showAlert(type, message) {
        const successAlert = document.getElementById('alert-success');
        const errorAlert = document.getElementById('alert-error');
        
        successAlert.style.display = 'none';
        errorAlert.style.display = 'none';

        if (type === 'success') {
            successAlert.textContent = message;
            successAlert.style.display = 'block';
            setTimeout(() => { successAlert.style.display = 'none'; }, 4000);
        } else {
            errorAlert.textContent = message;
            errorAlert.style.display = 'block';
            setTimeout(() => { errorAlert.style.display = 'none'; }, 4000);
        }
    }

    function fetchUsers() {
        fetch('users_data.php?action=list')
            .then(res => res.json())
            .then(data => {
                const tbody = document.getElementById('users-tbody');
                if (!data.success) {
                    tbody.innerHTML = `<tr><td colspan="5" class="empty">${data.error}</td></tr>`;
                    return;
                }
                
                if (data.users.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="5" class="empty">No users registered.</td></tr>`;
                    return;
                }

                tbody.innerHTML = data.users.map(u => `
                    <tr>
                        <td class="mono">${u.UserID}</td>
                        <td style="font-weight:600;">${esc(u.Username)}</td>
                        <td>
                            <span class="badge ${u.Role === 'Admin' ? 'badge-red' : (u.Role === 'Analyst' ? 'badge-blue' : 'badge-gray')}">
                                ${u.Role}
                            </span>
                        </td>
                        <td class="mono">${u.CreatedAt}</td>
                        <td style="text-align: right; display: flex; gap: 8px; justify-content: flex-end;">
                            <button class="btn-action btn-edit" onclick="editUser(${u.UserID}, '${esc(u.Username)}', '${u.Role}')">Edit</button>
                            <button class="btn-action btn-danger" onclick="deleteUser(${u.UserID}, '${esc(u.Username)}')">Delete</button>
                        </td>
                    </tr>
                `).join('');
            });
    }

    function handleFormSubmit(e) {
        e.preventDefault();
        
        const userId = document.getElementById('form-userid').value;
        const username = document.getElementById('form-username').value.trim();
        const password = document.getElementById('form-password').value;
        const role = document.getElementById('form-role').value;

        const action = userId ? 'update' : 'create';
        const formData = new URLSearchParams();
        formData.append('action', action);
        if (userId) formData.append('user_id', userId);
        formData.append('username', username);
        formData.append('password', password);
        formData.append('role', role);

        fetch('users_data.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData.toString()
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showAlert('success', data.message);
                resetForm();
                fetchUsers();
            } else {
                showAlert('error', data.error);
            }
        });
    }

    function editUser(id, username, role) {
        currentEditingId = id;
        document.getElementById('form-title').textContent = `Edit User: ${username}`;
        document.getElementById('form-userid').value = id;
        document.getElementById('form-username').value = username;
        
        // When editing, password is optional
        const passwordInput = document.getElementById('form-password');
        passwordInput.required = false;
        passwordInput.placeholder = "Leave blank to keep current password";
        document.getElementById('password-label').textContent = "Password (Optional)";

        document.getElementById('form-role').value = role;
        document.getElementById('btn-cancel').style.display = 'inline-block';
        document.getElementById('btn-submit').textContent = "Update User";
        
        // Scroll to form
        document.getElementById('user-form').scrollIntoView({ behavior: 'smooth' });
    }

    function resetForm() {
        currentEditingId = null;
        document.getElementById('form-title').textContent = "Create New User";
        document.getElementById('form-userid').value = "";
        document.getElementById('user-form').reset();
        
        const passwordInput = document.getElementById('form-password');
        passwordInput.required = true;
        passwordInput.placeholder = "Min 6 characters";
        document.getElementById('password-label').textContent = "Password";

        document.getElementById('btn-cancel').style.display = 'none';
        document.getElementById('btn-submit').textContent = "Save User";
    }

    function deleteUser(id, username) {
        if (!confirm(`Are you sure you want to delete user "${username}"?`)) {
            return;
        }

        const formData = new URLSearchParams();
        formData.append('action', 'delete');
        formData.append('user_id', id);

        fetch('users_data.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData.toString()
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showAlert('success', data.message);
                fetchUsers();
            } else {
                showAlert('error', data.error);
            }
        });
    }

    function esc(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Init load
    fetchUsers();
</script>

<?php require 'footer.php'; ?>
