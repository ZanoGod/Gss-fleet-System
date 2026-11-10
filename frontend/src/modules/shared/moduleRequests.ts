import { reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import api from '@/services/api'
import { getApiErrorMessage } from '@/services/apiErrors'
import { usePendingAction } from '@/components/common/usePendingAction'
import type { ResourceConfig, ResourceRow } from '@/components/common/resourceTypes'
import { toast } from '@/services/toast'

type Query = Record<string, string>
type Form = Record<string, string | number>
type Payload = Record<string, string | number | null>
type FieldErrors = Record<string, string[]>

export const displayValue = (value: unknown) =>
  value == null || value === '' ? '—' : String(value)

function usePageRequest() {
  const initial = ref(true)
  const loading = ref(false)
  const error = ref('')
  const { pending, run } = usePendingAction()
  let generation = 0

  async function request(task: (isCurrent: () => boolean) => Promise<void>) {
    const version = ++generation
    const isCurrent = () => version === generation
    loading.value = true
    if (!pending('reload')) error.value = ''
    try {
      await task(isCurrent)
      if (isCurrent()) error.value = ''
    } catch (cause) {
      if (isCurrent()) {
        error.value = getApiErrorMessage(cause)
        toast.error(error.value)
      }
    } finally {
      if (isCurrent()) {
        loading.value = false
        initial.value = false
      }
    }
  }

  return { initial, loading, error, pending, run, request }
}

function showRouteNotice(config: ResourceConfig, route: ReturnType<typeof useRoute>) {
  if (route.query.notice === 'saved') toast.success(`${config.singular} saved successfully.`)
  if (route.query.notice === 'deleted') toast.success(`${config.singular} deleted.`)
}

interface PageOptions {
  query?: () => Query
  prepare?: (isCurrent: () => boolean) => Promise<void>
}

export function useModuleList<Summary>(
  config: ResourceConfig,
  {
    query = () => ({}),
    prepare,
    readSummary,
    afterList,
  }: PageOptions & {
    readSummary: (data: unknown) => Summary
    afterList?: (isCurrent: () => boolean) => Promise<void>
  },
) {
  const route = useRoute()
  const router = useRouter()
  showRouteNotice(config, route)
  const rows = ref<ResourceRow[]>([])
  const summary = ref<Summary | null>(null)
  const total = ref(0)
  const page = ref(1)
  const filters = reactive<Query>({})
  const state = usePageRequest()
  const link = (path: string) => ({ path, query: query() })

  async function load(next = 1) {
    if (state.initial.value) {
      rows.value = []
      summary.value = null
    }
    await state.request(async (isCurrent) => {
      await prepare?.(isCurrent)
      if (!isCurrent()) return
      const params = { ...query(), ...filters, page: next, per_page: 10 }
      const [list, stats] = await Promise.all([
        api.get(config.endpoint, { params }),
        api.get(config.summaryEndpoint, { params }),
      ])
      if (!isCurrent()) return
      if (next > list.data.meta.last_page) {
        await load(Math.max(1, list.data.meta.last_page))
        return
      }
      rows.value = list.data.data
      total.value = list.data.meta.total
      page.value = list.data.meta.current_page
      await afterList?.(isCurrent)
      if (isCurrent()) summary.value = readSummary(stats.data.data)
    })
  }

  async function apply() {
    await router.replace({
      path: config.path,
      query: { ...query(), ...Object.fromEntries(Object.entries(filters).filter(([, v]) => v)) },
    })
    await load()
  }

  async function reset() {
    for (const key of Object.keys(filters)) filters[key] = ''
    await apply()
  }

  watch(
    () => [route.path, route.query],
    () => {
      state.initial.value = true
      for (const field of config.filters)
        filters[field.key] = String(route.query[field.key] || field.default || '')
      void load()
    },
    { immediate: true },
  )
  return { ...state, rows, summary, total, page, filters, link, load, apply, reset }
}

export function useModuleRecord(
  config: ResourceConfig,
  mode: () => 'detail' | 'create' | 'edit',
  {
    query = () => ({}),
    prepare,
    fields = () => config.fields,
    initializeForm,
    serialize,
    mapErrors,
  }: PageOptions & {
    fields?: () => ResourceConfig['fields']
    initializeForm?: (
      form: Form,
      row: ResourceRow | null,
      isCurrent: () => boolean,
    ) => Promise<void>
    serialize?: (payload: Payload, form: Form) => void
    mapErrors?: (errors: FieldErrors) => void
  } = {},
) {
  const route = useRoute()
  const router = useRouter()
  showRouteNotice(config, route)
  const record = ref<ResourceRow | null>(null)
  const form = reactive<Form>({})
  const errors = ref<FieldErrors>({})
  const saving = ref(false)
  const state = usePageRequest()
  const { error } = state
  const link = (path: string) => ({ path, query: query() })

  async function load() {
    if (state.initial.value) record.value = null
    errors.value = {}
    await state.request(async (isCurrent) => {
      await prepare?.(isCurrent)
      if (!isCurrent()) return
      const value: ResourceRow | null =
        mode() === 'create'
          ? null
          : (await api.get(`${config.endpoint}/${route.params.id}`, { params: query() })).data.data
      if (!isCurrent()) return
      record.value = value
      if (mode() === 'detail') return
      for (const key of Object.keys(form)) delete form[key]
      for (const field of fields())
        form[field.key] = (value?.[field.key] as string | number) ?? field.default ?? ''
      await initializeForm?.(form, value, isCurrent)
    })
  }
  async function save() {
    if (saving.value) return
    saving.value = true
    error.value = ''
    errors.value = {}
    try {
      const payload: Payload = Object.fromEntries(
        Object.entries(form).map(([k, v]) => [k, typeof v === 'string' ? v.trim() || null : v]),
      )
      serialize?.(payload, form)
      await api.request({
        method: mode() === 'edit' ? 'put' : 'post',
        url: `${config.endpoint}${mode() === 'edit' ? `/${route.params.id}` : ''}`,
        params: query(),
        data: payload,
      })
      toast.success(`${config.singular} saved successfully.`)
      await router.push({ path: config.path, query: query() })
    } catch (cause) {
      error.value = getApiErrorMessage(cause)
      toast.error(error.value)
      if (axios.isAxiosError(cause)) {
        errors.value = cause.response?.data?.errors || cause.response?.data?.error?.errors || {}
        mapErrors?.(errors.value)
      }
    } finally {
      saving.value = false
    }
  }
  async function remove() {
    if (saving.value) return
    saving.value = true
    error.value = ''
    try {
      await api.delete(`${config.endpoint}/${route.params.id}`, { params: query() })
      toast.success(`${config.singular} deleted.`)
      await router.push({ path: config.path, query: query() })
    } catch (cause) {
      error.value = getApiErrorMessage(cause)
      toast.error(error.value)
    } finally {
      saving.value = false
    }
  }

  watch(
    () => [route.path, route.query],
    () => {
      state.initial.value = true
      void load()
    },
    { immediate: true },
  )
  return { ...state, record, form, errors, saving, link, load, save, remove }
}
