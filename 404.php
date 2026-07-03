<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Halaman Tidak Ditemukan - PCA Net</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 h-full flex flex-col items-center justify-center px-4 sm:px-6 lg:px-8 py-24">
    <div class="max-w-md w-full text-center space-y-8">
        <!-- 404 Illustration placeholder -->
        <div class="relative w-full max-w-sm mx-auto">
             <svg class="w-full h-auto text-blue-100 mb-8" viewBox="0 0 400 300" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect x="50" y="50" width="300" height="200" rx="10" fill="#DBEAFE"/>
                <path d="M120 120H280" stroke="#3B82F6" stroke-width="8" stroke-linecap="round"/>
                <path d="M120 160H220" stroke="#3B82F6" stroke-width="8" stroke-linecap="round"/>
                <circle cx="200" cy="150" r="40" fill="#EFF6FF" stroke="#3B82F6" stroke-width="4"/>
                <path d="M190 140L210 160" stroke="#3B82F6" stroke-width="4" stroke-linecap="round"/>
                <path d="M210 140L190 160" stroke="#3B82F6" stroke-width="4" stroke-linecap="round"/>
                <rect x="80" y="220" width="240" height="10" rx="5" fill="#BFDBFE"/>
                <!-- Ghost/Shadow -->
                <path d="M30 280H370" stroke="#E2E8F0" stroke-width="4" stroke-linecap="round"/>
            </svg>
            
             <h1 class="mt-2 text-9xl font-extrabold text-blue-600 tracking-tight sm:text-9xl">404</h1>
        </div>

        <div>
            <h2 class="mt-4 text-3xl font-extrabold text-gray-900 tracking-tight">Halaman Tidak Ditemukan</h2>
            <p class="mt-2 text-base text-gray-500">Maaf, kami tidak dapat menemukan halaman yang Anda cari. Mungkin telah dihapus atau URL-nya salah.</p>
        </div>

        <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center">
            <a href="dashboard.php" class="inline-flex justify-center items-center px-6 py-3 border border-transparent text-base font-medium rounded-xl shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all transform hover:-translate-y-0.5">
                <svg class="mr-2 -ml-1 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Kembali ke Dashboard
            </a>
            <a href="#" onclick="history.back()" class="inline-flex justify-center items-center px-6 py-3 border border-gray-300 shadow-sm text-base font-medium rounded-xl text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all">
                <svg class="mr-2 -ml-1 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali Sebelumnya
            </a>
        </div>
        
        <p class="mt-8 text-sm text-gray-400">&copy; <?= date('Y') ?> PCA Net.</p>
    </div>
</body>
</html>
