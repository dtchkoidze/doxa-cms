<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>{{ projectTitle() }} | {{ $title }}</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">

    {{-- VITE --}}
    {{ Vite::useBuildDirectory('doxa/user')->useHotFile('doxa-user-vite.hot') }}
    @vite(['src/Resources/assets/css/user.css', 'src/Resources/assets/js/user.js'])

    <script>
        if (localStorage.getItem('dark-mode') === 'false' || !('dark-mode' in localStorage)) {
            document.querySelector('html').classList.remove('dark');
            document.querySelector('html').style.colorScheme = 'light';
        } else {
            document.querySelector('html').classList.add('dark');
            document.querySelector('html').style.colorScheme = 'dark';
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
        integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>

<body class=" text-gray-600 bg-gray-100 dark:bg-gray-900 dark:text-gray-400">

    {{-- <script>
        if (localStorage.getItem('sidebar-expanded') == 'true') {
            document.querySelector('body').classList.add('sidebar-expanded');
        } else {
            document.querySelector('body').classList.remove('sidebar-expanded');
        }
    </script>     --}}

    <div id="auth-app">
        @yield('wrapper')
    </div>

</body>

</html>
