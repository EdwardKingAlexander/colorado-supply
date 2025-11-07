<script setup>
const categories = [
    { id: 1, name: 'Fasteners' },
    { id: 2, name: 'Plumbing' },
    { id: 3, name: 'Electrical' },
    { id: 4, name: 'Tools' },
    { id: 5, name: 'Safety Equipment' },
    { id: 6, name: 'Material Handling' },
    { id: 7, name: 'Adhesives & Sealants' },
    { id: 8, name: 'Pipe & Tubing' },
];
</script>

<template>
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Categories</h2>
        </div>
        <nav class="p-2">
            <ul class="space-y-1">
                <li v-for="category in categories" :key="category.id">
                    <a
                        href="#"
                        class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-gray-900 rounded transition-colors"
                    >
                        {{ category.name }}
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</template>
