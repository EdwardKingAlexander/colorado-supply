<script setup>
import { computed, nextTick, ref, watch } from 'vue'

// Product image gallery: primary + thumbnails, tap/click to zoom with
// keyboard (arrows, Escape) and swipe navigation. No images → part glyph.
const props = defineProps({
  images: { type: Array, default: () => [] },
  name: { type: String, required: true },
})

const active = ref(0)
const zoomOpen = ref(false)
const zoomDialog = ref(null)
const current = computed(() => props.images[active.value] ?? null)
const hasMany = computed(() => props.images.length > 1)

watch(() => props.images, () => { active.value = 0 })

const show = (index) => {
  const count = props.images.length
  active.value = ((index % count) + count) % count
}

const openZoom = async () => {
  zoomOpen.value = true
  await nextTick()
  zoomDialog.value?.showModal()
}

const closeZoom = () => {
  zoomDialog.value?.close()
  zoomOpen.value = false
}

const onZoomKey = (event) => {
  // Chrome may ignore Escape on a modal dialog without recent user
  // activation, so close it explicitly.
  if (event.key === 'Escape') {
    event.preventDefault()
    closeZoom()

    return
  }

  if (!hasMany.value) {
    return
  }

  if (event.key === 'ArrowRight') {
    show(active.value + 1)
  } else if (event.key === 'ArrowLeft') {
    show(active.value - 1)
  }
}

// Horizontal swipe of 40px or more changes image.
let touchStartX = null
const onTouchStart = (event) => { touchStartX = event.touches[0].clientX }
const onTouchEnd = (event) => {
  if (touchStartX === null || !hasMany.value) {
    return
  }

  const delta = event.changedTouches[0].clientX - touchStartX
  touchStartX = null

  if (Math.abs(delta) >= 40) {
    show(active.value + (delta < 0 ? 1 : -1))
  }
}
</script>

<template>
  <div class="space-y-3">
    <div
      class="relative overflow-hidden rounded-lg border border-st-line bg-white"
      style="aspect-ratio: 1 / 1"
      @touchstart.passive="onTouchStart"
      @touchend="onTouchEnd"
    >
      <button
        v-if="current"
        type="button"
        class="block h-full w-full cursor-zoom-in focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-st-action"
        :aria-label="`Zoom image: ${current.alt}`"
        @click="openZoom"
      >
        <img :src="current.url" :alt="current.alt" class="h-full w-full object-contain" decoding="async" width="800" height="800" />
      </button>
      <div v-else class="flex h-full w-full items-center justify-center bg-st-bg">
        <svg class="h-full w-full p-[22%] text-st-muted/60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" aria-hidden="true">
          <path stroke-linejoin="round" d="M12 2.75 20 7.25v9.5l-8 4.5-8-4.5v-9.5l8-4.5Z" />
          <path stroke-linejoin="round" d="m4 7.25 8 4.5 8-4.5M12 11.75v9.5" />
        </svg>
        <span class="sr-only">No image available for {{ name }}</span>
      </div>

      <p v-if="hasMany" class="st-num absolute bottom-2 right-2 rounded bg-black/60 px-2 py-0.5 text-xs text-white" aria-hidden="true">
        {{ active + 1 }} / {{ images.length }}
      </p>
    </div>

    <ul v-if="hasMany" class="flex gap-2 overflow-x-auto pb-1" aria-label="Product images">
      <li v-for="(image, index) in images" :key="image.url" class="shrink-0">
        <button
          type="button"
          class="h-16 w-16 overflow-hidden rounded-md border-2 bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action"
          :class="index === active ? 'border-st-action' : 'border-st-line hover:border-st-muted'"
          :aria-label="`Show image ${index + 1} of ${images.length}`"
          :aria-current="index === active ? 'true' : undefined"
          @click="show(index)"
        >
          <img :src="image.url" :alt="''" class="h-full w-full object-contain" loading="lazy" decoding="async" width="64" height="64" />
        </button>
      </li>
    </ul>

    <dialog
      v-if="zoomOpen"
      ref="zoomDialog"
      class="m-0 h-full max-h-none w-full max-w-none bg-black/90 p-0 backdrop:bg-black/80"
      :aria-label="`${name} image viewer`"
      @close="zoomOpen = false"
      @keydown="onZoomKey"
      @touchstart.passive="onTouchStart"
      @touchend="onTouchEnd"
    >
      <div class="flex h-full w-full flex-col">
        <div class="flex items-center justify-between px-4 py-3 text-white">
          <p class="st-num text-sm">{{ active + 1 }} / {{ images.length }}</p>
          <button
            type="button"
            class="inline-flex h-12 w-12 items-center justify-center rounded-md text-2xl hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
            aria-label="Close image viewer"
            @click="closeZoom"
          >
            &times;
          </button>
        </div>
        <div class="relative flex min-h-0 flex-1 items-center justify-center px-4 pb-6">
          <img v-if="current" :src="current.url" :alt="current.alt" class="max-h-full max-w-full object-contain" />
          <template v-if="hasMany">
            <button
              type="button"
              class="absolute left-2 top-1/2 inline-flex h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full bg-white/15 text-2xl text-white hover:bg-white/25 focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
              aria-label="Previous image"
              @click="show(active - 1)"
            >
              &lsaquo;
            </button>
            <button
              type="button"
              class="absolute right-2 top-1/2 inline-flex h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full bg-white/15 text-2xl text-white hover:bg-white/25 focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
              aria-label="Next image"
              @click="show(active + 1)"
            >
              &rsaquo;
            </button>
          </template>
        </div>
      </div>
    </dialog>
  </div>
</template>
