@include('search.results')

<script type="application/json" id="suggestions-data">@json($suggestions)</script>

<template id="author-options">
    @include('search.author-options')
</template>
