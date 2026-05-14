<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Metafors</title>
        <meta
            name="description"
            content="A public collection of software metaphors with live filtering and a build-free Laravel frontend."
        >
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=fraunces:600,700|space-grotesk:400,500,700" rel="stylesheet">
        <link rel="stylesheet" href="{{ asset('css/metafors.css') }}">
        @livewireStyles
    </head>
    <body>
        <livewire:metafor-board />

        @livewireScripts
    </body>
</html>