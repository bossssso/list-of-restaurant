<template>
  <div class="min-h-screen bg-gray-100 dark:bg-gray-900 py-8 px-4 text-gray-800 dark:text-gray-100 transition">
    <div class="max-w-5xl mx-auto">
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold flex items-center gap-2">
          🍜 {{ $t('title') }}
        </h1>
        <div class="flex gap-2">
          <button @click="toggleLanguage" class="px-3 py-1 rounded bg-gray-200 dark:bg-gray-700">
            {{ locale === 'en' ? 'EN' : 'TH' }}
          </button>
          <button @click="toggleDarkMode" class="px-3 py-1 rounded bg-gray-200 dark:bg-gray-700">
            {{ isDark ? '🌙 Dark' : '☀️ Light' }}
          </button>
        </div>
      </div>

      <div class="flex flex-col sm:flex-row gap-4 mb-6">
        <input
          v-model="keyword"
          @keyup.enter="search"
          type="text"
          :placeholder="$t('placeholder')"
          class="flex-1 p-3 rounded-md border border-gray-300 dark:border-gray-700 shadow focus:outline-none focus:ring focus:ring-blue-300 dark:bg-gray-800"
        />
        <button
          @click="search"
          class="bg-blue-600 text-white px-5 py-3 rounded-md hover:bg-blue-700 transition"
        >
          {{ $t('search') }}
        </button>
      </div>

      <div
        id="map"
        class="w-full h-80 rounded-lg border shadow-sm mb-6"
      ></div>

      <div v-if="loading" class="text-center text-gray-500 dark:text-gray-400">Loading...</div>

      <div v-if="results.length" class="grid sm:grid-cols-2 md:grid-cols-3 gap-4">
        <div
          v-for="place in results"
          :key="place.place_id"
          class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow hover:shadow-md transition border dark:border-gray-700"
        >
          <h2 class="text-lg font-semibold mb-1">{{ place.name }}</h2>
          <p class="text-sm text-gray-600 dark:text-gray-400">{{ place.formatted_address }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { useI18n } from 'vue-i18n'

const keyword = ref('Bang Sue')
const results = ref([])
const loading = ref(false)

const { locale } = useI18n()
const toggleLanguage = () => {
  locale.value = locale.value === 'en' ? 'th' : 'en'
}

const isDark = ref(localStorage.theme === 'dark' || window.matchMedia('(prefers-color-scheme: dark)').matches)
const toggleDarkMode = () => {
  isDark.value = !isDark.value
  if (isDark.value) {
    document.documentElement.classList.add('dark')
    localStorage.theme = 'dark'
  } else {
    document.documentElement.classList.remove('dark')
    localStorage.theme = 'light'
  }
}

let map
let markers = []

const initMap = () => {
  map = new google.maps.Map(document.getElementById("map"), {
    center: { lat: 13.802, lng: 100.545 },
    zoom: 13,
  })
}

const clearMarkers = () => {
  markers.forEach(marker => marker.setMap(null))
  markers = []
}

const addMarkers = (places) => {
  clearMarkers()
  places.forEach(place => {
    if (place.geometry) {
      const marker = new google.maps.Marker({
        map,
        position: place.geometry.location,
        title: place.name
      })
      markers.push(marker)
    }
  })
  if (places.length > 0) {
    const bounds = new google.maps.LatLngBounds()
    places.forEach(p => bounds.extend(p.geometry.location))
    map.fitBounds(bounds)
  }
}

const search = async () => {
  loading.value = true
  try {
    const res = await axios.get(`/api/restaurants?keyword=${keyword.value}`)
    results.value = res.data.results
    addMarkers(results.value)
  } catch (err) {
    alert('Error while searching')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  if (isDark.value) {
    document.documentElement.classList.add('dark')
  }
  initMap()
  search()
})
</script>
