<?php
require_once '../config.php';
require_login();
require_role(['Administrator']);

$page_title = 'Manajemen User';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';

// Filter Logic
$search = $_GET['q'] ?? '';
$role_filter = $_GET['role'] ?? '';

// Build Query
$sql = "SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (u.name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($role_filter) {
    $sql .= " AND u.role_id = ?";
    $params[] = $role_filter;
}

$sql .= " ORDER BY u.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Fetch Roles for Dropdown
$stmt_roles = $pdo->query("SELECT * FROM roles");
$roles = $stmt_roles->fetchAll();
?>

<div class="w-full lg:pl-64 bg-gray-50 dark:bg-slate-900 min-h-screen">
    <div class="p-4 sm:p-6 space-y-4 sm:space-y-6">
        
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 border-b border-gray-200 pb-4">
            <div>
                 <nav class="flex mb-2" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1 md:space-x-3">
                        <li class="inline-flex items-center">
                            <a href="dashboard.php" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                                <svg class="w-3 h-3 me-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="m19.707 9.293-2-2-7-7a1 1 0 0 0-1.414 0l-7 7-2 2a1 1 0 0 0 1.414 1.414L2 10.414V18a2 2 0 0 0 2 2h3a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1h3a2 2 0 0 0 2-2v-7.586l.293.293a1 1 0 0 0 1.414-1.414Z"/>
                                </svg>
                                Dashboard
                            </a>
                        </li>
                        <li aria-current="page">
                            <div class="flex items-center">
                                <svg class="w-3 h-3 text-gray-400 mx-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                                </svg>
                                <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">Manajemen User</span>
                            </div>
                        </li>
                    </ol>
                </nav>
                <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Manajemen User</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Kelola akun pengguna dan hak akses sistem.</p>
            </div>
            <button type="button" onclick="openModal('addUserModal')" class="py-2.5 px-4 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 shadow-sm transition-all">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                Tambah User
            </button>
        </div>

         <!-- Filter & Search Toolbar -->
         <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl p-4 shadow-sm">
            <form action="" method="GET" class="flex flex-col sm:flex-row gap-4">
                <div class="flex-1 relative">
                    <div class="absolute inset-y-0 start-0 flex items-center pointer-events-none z-20 ps-4">
                         <svg class="size-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    </div>
                    <input type="text" name="q" placeholder="Cari nama atau email..." value="<?= htmlspecialchars($search) ?>" class="py-2.5 ps-10 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white dark:placeholder-gray-500">
                </div>
                <div class="sm:w-48">
                    <select name="role" class="py-2.5 px-3 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white">
                        <option value="">Semua Role</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= $role['id'] ?>" <?= $role_filter == $role['id'] ? 'selected' : '' ?>><?= $role['role_name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="py-2.5 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-gray-200 shadow-sm hover:bg-[#F8FAFC] dark:hover:bg-slate-700 transition-colors">
                    Filter
                </button>
            </form>
        </div>

        <!-- Table -->
        <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                    <thead class="bg-[#F8FAFC] dark:bg-slate-900">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">No</th>
                            <th scope="col" class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Info User</th>
                            <th scope="col" class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Email</th>
                            <th scope="col" class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Role</th>
                            <th scope="col" class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Terdaftar</th>
                            <th scope="col" class="px-6 py-3 text-end text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                        <?php if (count($users) > 0): ?>
                            <?php $no = 1; ?>
                            <?php foreach ($users as $u): ?>
                            <tr class="hover:bg-[#F8FAFC]/50 dark:hover:bg-slate-700/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    <?= $no++ ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-x-3">
                                        <div class="flex-shrink-0 flex justify-center items-center size-[38px] bg-[#F8FAFC] dark:bg-slate-700 rounded-full text-gray-500 dark:text-gray-400 font-bold text-xs uppercase">
                                            <?= substr($u['name'], 0, 2) ?>
                                        </div>
                                        <div>
                                            <span class="block text-sm font-semibold text-gray-800 dark:text-white"><?= htmlspecialchars($u['name']) ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400"><?= htmlspecialchars($u['email']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span class="inline-flex items-center gap-x-1.5 py-1 px-3 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <?= htmlspecialchars($u['role_name']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-end text-sm font-medium">
                                    <button type="button" 
                                        class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-gray-200 shadow-sm hover:bg-gray-50 dark:hover:bg-slate-700 transition-all mr-2"
                                        onclick="openEditModal(<?= htmlspecialchars(json_encode($u)) ?>)">
                                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/></svg>
                                        Edit
                                    </button>
                                    
                                    <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                                    <button type="button" 
                                        onclick="openDeleteModal(<?= $u['id'] ?>, '<?= htmlspecialchars($u['name']) ?>')"
                                        class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-red-600 dark:text-red-500 shadow-sm hover:bg-gray-50 dark:hover:bg-slate-700 transition-all">
                                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                                        Hapus
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center">
                                    <div class="flex flex-col justify-center items-center">
                                        <div class="size-12 rounded-full bg-[#F8FAFC] dark:bg-slate-700 flex items-center justify-center mb-3">
                                            <svg class="size-6 text-gray-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                                        </div>
                                        <h3 class="text-gray-800 dark:text-white font-semibold">Tidak ada data user</h3>
                                        <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Coba sesuaikan filter pencarian Anda.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add User Modal (Standardized) -->
<div id="addUserModal" class="hidden fixed inset-0 z-[60] overflow-y-auto w-full h-full bg-black/50 backdrop-blur-sm flex p-4 transition-all">
    <div class="m-auto flex flex-col bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 shadow-xl rounded-xl pointer-events-auto sm:w-full md:max-w-lg w-full">
         <div class="flex justify-between items-center py-3 px-4 border-b border-gray-200 dark:border-slate-700">
            <h3 class="font-bold text-gray-800 dark:text-white">Tambah User Baru</h3>
            <button type="button" onclick="closeModal('addUserModal')" class="size-8 inline-flex justify-center items-center gap-x-2 rounded-lg border border-transparent bg-[#F8FAFC] dark:bg-slate-700 text-gray-800 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-slate-600 disabled:opacity-50 disabled:pointer-events-none">
                <span class="sr-only">Close</span>
                <svg class="flex-shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
        <div class="p-6 overflow-y-auto">
            <form action="../actions/user_actions.php" method="POST">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                <div class="grid gap-y-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Nama Lengkap</label>
                        <input type="text" name="name" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white" required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Email</label>
                        <input type="email" name="email" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white" required>
                    </div>
                     <div>
                        <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Password</label>
                        <input type="password" name="password" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white" required minlength="6">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Role</label>
                        <select name="role_id" class="py-3 px-4 pe-9 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white" required>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= $role['id'] ?>"><?= $role['role_name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end items-center gap-x-2 pt-2 border-t border-gray-200 dark:border-slate-700 mt-4">
                    <button type="button" onclick="closeModal('addUserModal')" class="py-2.5 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-white shadow-sm hover:bg-[#F8FAFC] dark:hover:bg-slate-700 disabled:opacity-50 disabled:pointer-events-none">
                        Batal
                    </button>
                    <button type="submit" class="py-2.5 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none">
                        Simpan User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal (Standardized) -->
<div id="editUserModal" class="hidden fixed inset-0 z-[60] overflow-y-auto w-full h-full bg-black/50 backdrop-blur-sm flex p-4 transition-all">
    <div class="m-auto flex flex-col bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 shadow-xl rounded-xl pointer-events-auto sm:w-full md:max-w-lg w-full">
         <div class="flex justify-between items-center py-3 px-4 border-b border-gray-200 dark:border-slate-700">
            <h3 class="font-bold text-gray-800 dark:text-white">Edit User</h3>
            <button type="button" onclick="closeModal('editUserModal')" class="size-8 inline-flex justify-center items-center gap-x-2 rounded-lg border border-transparent bg-[#F8FAFC] dark:bg-slate-700 text-gray-800 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-slate-600 disabled:opacity-50 disabled:pointer-events-none">
                <span class="sr-only">Close</span>
                <svg class="flex-shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
        <div class="p-6 overflow-y-auto">
            <form action="../actions/user_actions.php" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                <input type="hidden" name="user_id" id="edit_user_id">
                
                <div class="grid gap-y-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Nama Lengkap</label>
                        <input type="text" name="name" id="edit_name" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white" required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Email</label>
                        <input type="email" name="email" id="edit_email" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white" required>
                    </div>
                      <div>
                        <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Password Baru (Opsional)</label>
                        <input type="password" name="password" class="py-3 px-4 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white" placeholder="Isi hanya jika ingin mengganti password">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-gray-800 dark:text-white">Role</label>
                        <select name="role_id" id="edit_role_id" class="py-3 px-4 pe-9 block w-full border-gray-200 dark:border-slate-700 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 bg-[#F1F5F9] dark:bg-slate-900 dark:text-white" required>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= $role['id'] ?>"><?= $role['role_name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end items-center gap-x-2 pt-2 border-t border-gray-200 dark:border-slate-700 mt-4">
                    <button type="button" onclick="closeModal('editUserModal')" class="py-2.5 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-white shadow-sm hover:bg-[#F8FAFC] dark:hover:bg-slate-700 disabled:opacity-50 disabled:pointer-events-none">
                        Batal
                    </button>
                    <button type="submit" class="py-2.5 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal (Standardized) -->
<div id="deleteUserModal" class="hidden fixed inset-0 z-[60] overflow-y-auto w-full h-full bg-black/50 backdrop-blur-sm flex p-4 transition-all">
    <div class="m-auto flex flex-col bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 shadow-xl rounded-xl pointer-events-auto sm:w-full md:max-w-md w-full">
        <div class="p-6 text-center">
            <div class="mb-4 inline-flex justify-center items-center size-[62px] rounded-full border-4 border-red-50 bg-red-100 text-red-500">
                <svg class="flex-shrink-0 size-8" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </div>
            <h3 class="mb-2 text-2xl font-bold text-gray-800 dark:text-white">Hapus User?</h3>
            <p class="text-gray-500">
                Apakah Anda yakin ingin menghapus user <span id="delete_user_name" class="font-bold"></span>? Tindakan ini tidak dapat dibatalkan.
            </p>
            <form action="../actions/user_actions.php" method="POST" class="mt-6 flex justify-center gap-x-4">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                <input type="hidden" name="user_id" id="delete_user_id">
                
                <button type="button" onclick="closeModal('deleteUserModal')" class="py-2.5 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-800 dark:text-gray-200 shadow-sm hover:bg-[#F8FAFC] dark:hover:bg-slate-700">
                    Batal
                </button>
                <button type="submit" class="py-2.5 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-red-600 text-white hover:bg-red-700">
                    Hapus User
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    function openModal(modalId) {
        document.getElementById(modalId).classList.remove('hidden');
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
    }

    function openEditModal(user) {
        document.getElementById('edit_user_id').value = user.id;
        document.getElementById('edit_name').value = user.name;
        document.getElementById('edit_email').value = user.email;
        document.getElementById('edit_role_id').value = user.role_id;
        
        openModal('editUserModal');
    }

    function openDeleteModal(id, name) {
        document.getElementById('delete_user_id').value = id;
        document.getElementById('delete_user_name').innerText = name;
        
        openModal('deleteUserModal');
    }
</script>

<?php require_once '../includes/footer.php'; ?>
