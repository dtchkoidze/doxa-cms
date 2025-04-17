<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta charset="utf-8">
    <title>Admin</title>
    <meta name="description" content="Admin">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    {{-- Theme Init ASAP --}}
    <script>
        (function() {
            const colorMode = localStorage.getItem('vueuse-color-scheme');
            const darkTheme = 'dark';
            if (colorMode === 'dark') {
                document.documentElement.classList.add(darkTheme);
            } else {
                document.documentElement.classList.remove(darkTheme);
            }
        })();
    </script>
    {{-- test --}}
    {{-- VITE --}}
    {{ Vite::useBuildDirectory('doxa/admin')->useHotFile('doxa-admin-vite.hot') }}
    @vite(['src/Resources/assets/css/admin.css', 'src/Resources/assets/js/admin.js'])

    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.6.2/tinymce.min.js" crossorigin="anonymous"
        referrerpolicy="no-referrer"></script>

    <script src="http://SortableJS.github.io/Sortable/Sortable.js"></script>

    {{-- <!-- CDNJS :: Sortable (https://cdnjs.com/) -->
    <script src="//cdn.jsdelivr.net/npm/sortablejs@1.8.4/Sortable.min.js"></script>
    <!-- CDNJS :: Vue.Draggable (https://cdnjs.com/) -->
    <script src="//cdnjs.cloudflare.com/ajax/libs/Vue.Draggable/2.20.0/vuedraggable.umd.min.js"></script> --}}

</head>

<body class="antialiased text-gray-600 bg-gray-100 font-inter dark:bg-gray-900 dark:text-gray-400 sidebar-expanded">
    <div id="admin">
        <div class="flex h-[100dvh] overflow-hidden">
            <Sidebar></Sidebar>
            <div class="relative flex flex-col flex-1 overflow-x-hidden overflow-y-auto">
                @php
                    $user = auth()->user();
                @endphp
                <header-vue></header-vue>
                <main class="grow">
                    <div class="w-full px-4 py-4 mx-auto sm:px-6 lg:px-4 ">
                        @yield('content')
                    </div>
                </main>
            </div>
        </div>
    </div>
</body>

</html>
