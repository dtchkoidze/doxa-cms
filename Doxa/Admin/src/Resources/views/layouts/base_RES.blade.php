<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Admin</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">

    {{-- VITE --}}
    {{ Vite::useBuildDirectory('build/admin')->useHotFile('admin-vite.hot') }}
    @vite(['src/Resources/assets/css/admin.css', 'src/Resources/assets/js/admin.js'])

</head>


<body class="bg-gray-100 dark:bg-gray-900">
    <div id="admin" class="flex flex-col h-screen">
        
        <header-vue></header-vue>

        <div class="flex flex-1">
            <aside class="flex-shrink-0 w-64 bg-white dark:bg-gray-900">
                <x-admin::menu />
            </aside>

            <main class="flex-1 p-6 overflow-y-auto">
                @yield('content')
            </main>
        </div>
    </div>
</body>


</html>
