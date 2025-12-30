<script setup>
import { onMounted, ref } from 'vue'
import axios from 'axios'

const favorites = ref([])
const loading = ref(true)
const errorMessage = ref('')
const pagination = ref({
  current_page: 1,
  last_page: 1,
  next_page_url: null,
  prev_page_url: null,
})
const toggling = ref(new Set())
const documents = ref({})
const documentsLoading = ref(new Set())
const documentsError = ref({})
const openDocsFor = ref(null)
const uploading = ref(new Set())
const insightsOpen = ref(null)
const insightsQuestion = ref('')
const insightsAnswer = ref({})
const insightsLoading = ref(new Set())
const insightsError = ref({})
const exporting = ref(false)
const exportError = ref('')

const baseUrl = '/api/v1/sam-opportunities/favorites'

const setPagination = (meta = {}) => {
  pagination.value = {
    current_page: meta.current_page ?? 1,
    last_page: meta.last_page ?? 1,
    next_page_url: meta.next_page_url ?? null,
    prev_page_url: meta.prev_page_url ?? null,
  }
}

const fetchFavorites = async (url = baseUrl) => {
  loading.value = true
  errorMessage.value = ''
  try {
    const { data } = await axios.get(url)
    favorites.value = data.data ?? []
    setPagination(data.meta ?? {})
  } catch (error) {
    errorMessage.value = 'Failed to load favorites.'
  } finally {
    loading.value = false
  }
}

const toggleFavorite = async (opp) => {
  if (toggling.value.has(opp.id)) return
  toggling.value.add(opp.id)
  errorMessage.value = ''

  const currentlyFavorite = opp.is_favorite ?? true
  const url = `/api/v1/sam-opportunities/${opp.id}/favorite`

  try {
    if (currentlyFavorite) {
      await axios.delete(url)
      favorites.value = favorites.value.filter((o) => o.id !== opp.id)
    } else {
      await axios.post(url)
      opp.is_favorite = true
    }
  } catch (error) {
    errorMessage.value = 'Unable to update favorite. Please try again.'
  } finally {
    toggling.value.delete(opp.id)
  }
}

const fetchDocuments = async (oppId) => {
  documentsLoading.value.add(oppId)
  documentsError.value[oppId] = ''
  try {
    const { data } = await axios.get(`/api/v1/sam-opportunities/${oppId}/documents`)
    documents.value[oppId] = data.data ?? []
  } catch (error) {
    documentsError.value[oppId] = 'Failed to load documents.'
  } finally {
    documentsLoading.value.delete(oppId)
  }
}

const toggleDocs = async (oppId) => {
  if (openDocsFor.value === oppId) {
    openDocsFor.value = null
    return
  }
  openDocsFor.value = oppId
  if (!documents.value[oppId]) {
    await fetchDocuments(oppId)
  }
}

const formatSize = (bytes) => {
  if (bytes === undefined || bytes === null) return 'N/A'
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}

const uploadDocument = async (oppId, event) => {
  const file = event.target.files?.[0]
  if (!file) return
  uploading.value.add(oppId)
  documentsError.value[oppId] = ''

  try {
    const formData = new FormData()
    formData.append('file', file)
    const { data } = await axios.post(`/api/v1/sam-opportunities/${oppId}/documents`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    documents.value[oppId] = [data, ...(documents.value[oppId] || [])]
  } catch (error) {
    documentsError.value[oppId] = 'Upload failed. Please try again.'
  } finally {
    uploading.value.delete(oppId)
    event.target.value = ''
  }
}

const deleteDocument = async (oppId, docId) => {
  uploading.value.add(docId)
  documentsError.value[oppId] = ''
  try {
    await axios.delete(`/api/v1/sam-opportunities/${oppId}/documents/${docId}`)
    documents.value[oppId] = (documents.value[oppId] || []).filter((d) => d.id !== docId)
  } catch (error) {
    documentsError.value[oppId] = 'Delete failed. Please try again.'
  } finally {
    uploading.value.delete(docId)
  }
}

const askInsights = async (oppId) => {
  if (!insightsQuestion.value.trim()) {
    insightsError.value[oppId] = 'Please enter a question.'
    return
  }
  insightsLoading.value.add(oppId)
  insightsError.value[oppId] = ''
  try {
    const { data } = await axios.post(`/api/v1/sam-opportunities/${oppId}/rag-query`, {
      query: insightsQuestion.value,
    })
    insightsAnswer.value[oppId] = data
  } catch (error) {
    insightsError.value[oppId] = 'Unable to retrieve insights. Please try again.'
  } finally {
    insightsLoading.value.delete(oppId)
  }
}

const toggleInsights = (oppId) => {
  if (insightsOpen.value === oppId) {
    insightsOpen.value = null
    return
  }
  insightsOpen.value = oppId
  insightsError.value[oppId] = ''
  insightsAnswer.value[oppId] = null
  insightsQuestion.value = ''
}

const exportToExcel = async () => {
  exporting.value = true
  exportError.value = ''

  try {
    const response = await axios.post(
      '/api/v1/sam-opportunities/export',
      { favorites_only: true },
      { responseType: 'blob' }
    )

    // Create download link
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url

    // Extract filename from Content-Disposition header or use default
    const contentDisposition = response.headers['content-disposition']
    let filename = 'sam-opportunities-export.xlsx'
    if (contentDisposition) {
      const filenameMatch = contentDisposition.match(/filename="?(.+)"?/)
      if (filenameMatch && filenameMatch[1]) {
        filename = filenameMatch[1]
      }
    }

    link.setAttribute('download', filename)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (error) {
    // Handle different error types
    if (error.response) {
      if (error.response.status === 429) {
        exportError.value = 'Too many export requests. Please wait a few minutes and try again.'
      } else if (error.response.status === 500) {
        exportError.value = 'Export failed due to a server error. Please try again or contact support.'
      } else if (error.response.status === 422) {
        exportError.value = 'Invalid export parameters. Please refresh the page and try again.'
      } else {
        exportError.value = 'Failed to export favorites. Please try again.'
      }
    } else {
      exportError.value = 'Network error. Please check your connection and try again.'
    }
  } finally {
    exporting.value = false
  }
}

onMounted(() => {
  fetchFavorites()
})
</script>

<template>
  <div class="max-w-6xl mx-auto px-4 py-8 space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-semibold text-gray-900">SAM Opportunities</h1>
        <p class="text-gray-600">Favorited opportunities for your account.</p>
      </div>
      <div class="flex items-center gap-3">
        <button
          v-if="!loading && favorites.length > 0"
          type="button"
          class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
          :disabled="exporting"
          @click="exportToExcel"
        >
          <svg v-if="!exporting" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <svg v-else class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <span>{{ exporting ? 'Exporting...' : 'Export to Excel' }}</span>
        </button>
        <div v-if="loading" class="text-sm text-gray-500">Loading...</div>
      </div>
    </div>

    <div v-if="errorMessage" class="rounded-md bg-red-50 p-4 text-red-700 text-sm">
      {{ errorMessage }}
    </div>

    <div v-if="exportError" class="rounded-md bg-red-50 p-4 text-red-700 text-sm">
      {{ exportError }}
    </div>

    <div v-if="!loading && favorites.length === 0" class="rounded-lg border border-dashed border-gray-200 p-8 text-center text-gray-600">
      No favorited opportunities yet.
    </div>

    <div v-else-if="!loading" class="overflow-hidden rounded-lg border border-gray-200 shadow-sm">
      <table class="min-w-full divide-y divide-gray-200 bg-white">
        <thead class="bg-gray-50">
          <tr>
            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Title</th>
            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Agency</th>
            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Posted</th>
            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Due</th>
            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Link</th>
            <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-gray-700">Favorite</th>
            <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-gray-700">Documents</th>
            <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-gray-700">Insights</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <template v-for="opp in favorites" :key="opp.id">
            <tr class="hover:bg-gray-50">
              <td class="px-4 py-3 text-sm text-gray-900">
                <div class="font-medium">{{ opp.title || 'Untitled opportunity' }}</div>
                <div class="text-gray-500 text-xs" v-if="opp.naics_code || opp.psc_code">
                  <span v-if="opp.naics_code">NAICS: {{ opp.naics_code }}</span>
                  <span v-if="opp.naics_code && opp.psc_code" class="mx-1">•</span>
                  <span v-if="opp.psc_code">PSC: {{ opp.psc_code }}</span>
                </div>
              </td>
              <td class="px-4 py-3 text-sm text-gray-700">{{ opp.agency || '—' }}</td>
              <td class="px-4 py-3 text-sm text-gray-700">{{ opp.posted_date || '—' }}</td>
              <td class="px-4 py-3 text-sm text-gray-700">{{ opp.response_deadline || '—' }}</td>
              <td class="px-4 py-3 text-sm text-blue-600">
                <a v-if="opp.sam_url" :href="opp.sam_url" class="hover:underline" target="_blank" rel="noopener noreferrer">View</a>
                <span v-else class="text-gray-500">—</span>
              </td>
              <td class="px-4 py-3 text-right">
                <button
                  type="button"
                  class="inline-flex items-center rounded-full border px-3 py-1 text-sm font-medium transition"
                  :class="opp.is_favorite ? 'border-amber-400 text-amber-600 bg-amber-50' : 'border-gray-300 text-gray-700 hover:border-amber-300 hover:text-amber-600'"
                  :disabled="toggling.has(opp.id)"
                  @click="toggleFavorite(opp)"
                >
                  <svg class="h-4 w-4 mr-1" :class="opp.is_favorite ? 'fill-amber-500' : 'fill-none stroke-current'" viewBox="0 0 24 24" stroke-width="1.5">
                    <path
                      d="M12 17.27 18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"
                      :fill="opp.is_favorite ? 'currentColor' : 'none'"
                      :stroke="opp.is_favorite ? 'currentColor' : 'currentColor'"
                    />
                  </svg>
                  <span>{{ opp.is_favorite ? 'Favorited' : 'Favorite' }}</span>
                </button>
              </td>
              <td class="px-4 py-3 text-right">
                <button
                  type="button"
                  class="inline-flex items-center rounded-md border border-gray-300 px-3 py-1 text-sm text-gray-700 hover:border-blue-300 hover:text-blue-600 transition"
                  @click="toggleDocs(opp.id)"
                >
                  Manage Documents
                </button>
              </td>
              <td class="px-4 py-3 text-right">
                <button
                  type="button"
                  class="inline-flex items-center rounded-md border border-gray-300 px-3 py-1 text-sm text-gray-700 hover:border-green-300 hover:text-green-600 transition"
                  @click="toggleInsights(opp.id)"
                >
                  Insights
                </button>
              </td>
            </tr>

            <tr v-if="openDocsFor === opp.id" :key="`docs-${opp.id}`" class="bg-gray-50">
              <td colspan="8" class="px-4 py-4">
                <div class="flex items-start justify-between">
                  <div class="space-y-3 w-full">
                    <div class="flex items-center justify-between">
                      <div>
                        <h3 class="text-sm font-semibold text-gray-900">Documents</h3>
                        <p class="text-xs text-gray-600">Upload and manage documents for this opportunity.</p>
                      </div>
                      <div class="flex items-center gap-2">
                        <label class="inline-flex items-center rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-700 bg-white cursor-pointer hover:border-blue-300 hover:text-blue-600 transition">
                          <input type="file" class="hidden" :disabled="uploading.has(opp.id)" @change="(e) => uploadDocument(opp.id, e)" />
                          <span v-if="!uploading.has(opp.id)">Upload</span>
                          <span v-else>Uploading...</span>
                        </label>
                      </div>
                    </div>

                    <div v-if="documentsError[opp.id]" class="rounded-md bg-red-50 p-3 text-sm text-red-700">
                      {{ documentsError[opp.id] }}
                    </div>

                    <div v-if="documentsLoading.has(opp.id)" class="text-sm text-gray-600">Loading documents...</div>

                    <div v-else-if="(documents[opp.id] || []).length === 0" class="rounded-md border border-dashed border-gray-200 p-4 text-sm text-gray-600">
                      No documents yet for this opportunity.
                    </div>

                    <div v-else class="rounded-md border border-gray-200 bg-white divide-y divide-gray-100">
                      <div
                        v-for="doc in documents[opp.id]"
                        :key="doc.id"
                        class="flex items-center justify-between px-3 py-2"
                      >
                        <div>
                          <div class="text-sm text-gray-900 font-medium">{{ doc.original_filename }}</div>
                          <div class="text-xs text-gray-600">
                            {{ doc.mime_type || 'Unknown type' }} · {{ formatSize(doc.size_bytes) }} ·
                            <span v-if="doc.uploaded_at">Uploaded {{ new Date(doc.uploaded_at).toLocaleDateString() }}</span>
                          </div>
                        </div>
                        <div class="flex items-center gap-2">
                          <span class="text-xs text-gray-500" v-if="doc.uploaded_by">by {{ doc.uploaded_by.name }}</span>
                          <button
                            type="button"
                            class="text-sm text-red-600 hover:text-red-700 disabled:opacity-50"
                            :disabled="uploading.has(doc.id)"
                            @click="deleteDocument(opp.id, doc.id)"
                          >
                            Delete
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </td>
            </tr>

            <tr v-if="insightsOpen === opp.id" :key="`insights-${opp.id}`" class="bg-gray-50">
              <td colspan="8" class="px-4 py-4">
                <div class="space-y-3">
                  <div class="flex items-center justify-between">
                    <div>
                      <h3 class="text-sm font-semibold text-gray-900">Insights</h3>
                      <p class="text-xs text-gray-600">Ask a question about this opportunity or its documents.</p>
                    </div>
                  </div>

                  <div class="flex items-center gap-2">
                    <input
                      v-model="insightsQuestion"
                      type="text"
                      class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-400 focus:ring-2 focus:ring-blue-200"
                      placeholder="Ask a question..."
                    />
                    <button
                      type="button"
                      class="rounded-md bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                      :disabled="insightsLoading.has(opp.id)"
                      @click="askInsights(opp.id)"
                    >
                      <span v-if="!insightsLoading.has(opp.id)">Ask</span>
                      <span v-else>Loading...</span>
                    </button>
                  </div>

                  <div v-if="insightsError[opp.id]" class="rounded-md bg-red-50 p-3 text-sm text-red-700">
                    {{ insightsError[opp.id] }}
                  </div>

                  <div v-if="insightsLoading.has(opp.id)" class="text-sm text-gray-600">Retrieving insights...</div>

                  <div v-else-if="insightsAnswer[opp.id]" class="space-y-2">
                    <div class="rounded-md border border-gray-200 bg-white p-3">
                      <h4 class="text-sm font-semibold text-gray-900">Answer</h4>
                      <p class="text-sm text-gray-800 whitespace-pre-line">{{ insightsAnswer[opp.id].answer }}</p>
                    </div>
                    <div v-if="(insightsAnswer[opp.id].top_chunks || []).length" class="rounded-md border border-gray-200 bg-white p-3 space-y-2">
                      <h4 class="text-sm font-semibold text-gray-900">Supporting context</h4>
                      <ul class="space-y-1">
                        <li
                          v-for="ctx in insightsAnswer[opp.id].top_chunks"
                          :key="ctx.chunk_id"
                          class="text-sm text-gray-700"
                        >
                          <span class="text-xs text-gray-500">Doc {{ ctx.document_id }} · Score {{ ctx.score.toFixed(2) }}</span>
                          <div class="text-sm text-gray-800 truncate">{{ ctx.text }}</div>
                        </li>
                      </ul>
                    </div>
                  </div>

                  <div v-else class="text-sm text-gray-600">
                    Ask a question to see insights.
                  </div>
                </div>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
