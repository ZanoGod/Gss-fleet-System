<script setup lang="ts">
import { computed, onUnmounted, ref } from 'vue'
import RecordAction from '@/components/common/RecordAction.vue'

const status = ref('Ready — try a button')
const active = ref(true)
const billing = ref('Monthly')
const period = ref('Week')
const filterCount = ref(2)
const deleteOpen = ref(false)
const toastMessage = ref('')
let toastTimer: ReturnType<typeof setTimeout> | undefined

const filterLabel = computed(() => `${filterCount.value} active`)

function logAction(message: string) {
  status.value = message
}

function showToast(message: string) {
  toastMessage.value = message
  if (toastTimer) clearTimeout(toastTimer)
  toastTimer = setTimeout(() => (toastMessage.value = ''), 2600)
}

async function copySnippet(id: string) {
  const group = document.getElementById(id)
  if (!group) return
  try {
    await navigator.clipboard.writeText(group.innerHTML.trim())
    showToast('HTML snippet copied')
  } catch {
    showToast('Clipboard access is unavailable')
  }
}

function toggleActive() {
  active.value = !active.value
  logAction(`Switch toggled: ${active.value ? 'Active' : 'Inactive'}`)
}

function selectBilling(value: string) {
  billing.value = value
  logAction(`Selected option: ${value}`)
}

function selectPeriod(value: string) {
  period.value = value
  logAction(`Selected option: ${value}`)
}

function resetFilters() {
  filterCount.value = 0
  logAction('All filters reset')
}

function confirmDelete() {
  deleteOpen.value = false
  logAction('Item deleted')
  showToast('Item deleted successfully')
}

function handleKeydown(event: KeyboardEvent) {
  if (event.key === 'Escape') deleteOpen.value = false
}

window.addEventListener('keydown', handleKeydown)
onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown)
  if (toastTimer) clearTimeout(toastTimer)
})
</script>

<template>
  <div class="button-system">
    <header class="system-header">
      <div class="system-heading">
        <div class="system-mark" aria-hidden="true"><i class="bi bi-cursor-fill"></i></div>
        <div>
          <h1>Button design system</h1>
          <p>Action patterns for everyday fleet operations</p>
        </div>
      </div>
      <div class="system-status" role="status" aria-live="polite">
        <span class="status-dot"></span>{{ status }}
      </div>
    </header>

    <main class="system-content">
      <section class="showcase-section">
        <div class="section-heading">
          <div><span class="section-kicker">Creation</span><h2>Add buttons</h2></div>
          <button class="copy-button" type="button" @click="copySnippet('add-group')">
            <i class="bi bi-copy" aria-hidden="true"></i> Copy HTML
          </button>
        </div>
        <div id="add-group" class="sample-row">
          <button class="demo-button primary-button" type="button" @click="logAction('New item added')">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>Add new item
          </button>
          <button class="demo-button soft-button" type="button" @click="logAction('Record created')">
            <i class="bi bi-plus-circle" aria-hidden="true"></i>Create record
          </button>
          <button class="demo-button dashed-button" type="button" @click="logAction('Block added')">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>Add block
          </button>
          <button class="demo-button fab-button" type="button" aria-label="Quick add" title="Quick add" @click="logAction('Quick add opened')">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
          </button>
        </div>
      </section>

      <section class="showcase-section">
        <div class="section-heading">
          <div><span class="section-kicker">State selection</span><h2>Switches and toggles</h2></div>
          <button class="copy-button" type="button" @click="copySnippet('switch-group')">
            <i class="bi bi-copy" aria-hidden="true"></i> Copy HTML
          </button>
        </div>
        <div id="switch-group" class="switch-grid">
          <div class="switch-sample">
            <span class="sample-label">Two-state toggle</span>
            <div class="switch-control">
              <button class="pill-switch" :class="{ 'is-on': active }" type="button" role="switch" :aria-checked="active" @click="toggleActive">
                <span class="switch-knob"></span>
              </button>
              <span class="switch-value">{{ active ? 'Active' : 'Inactive' }}</span>
            </div>
          </div>
          <div class="switch-sample">
            <span class="sample-label">Billing cycle</span>
            <div class="segmented" role="group" aria-label="Billing cycle">
              <button v-for="choice in ['Monthly', 'Yearly']" :key="choice" type="button" :aria-pressed="billing === choice" :class="{ selected: billing === choice }" @click="selectBilling(choice)">
                {{ choice }} <span v-if="choice === 'Yearly'" class="saving">−20%</span>
              </button>
            </div>
          </div>
          <div class="switch-sample">
            <span class="sample-label">Report period</span>
            <div class="segmented" role="group" aria-label="Report period">
              <button v-for="choice in ['Day', 'Week', 'Month']" :key="choice" type="button" :aria-pressed="period === choice" :class="{ selected: period === choice }" @click="selectPeriod(choice)">{{ choice }}</button>
            </div>
          </div>
        </div>
      </section>

      <section class="showcase-section">
        <div class="section-heading">
          <div><span class="section-kicker">Record actions</span><h2>View, edit, delete and back</h2></div>
          <button class="copy-button" type="button" @click="copySnippet('crud-group')">
            <i class="bi bi-copy" aria-hidden="true"></i> Copy HTML
          </button>
        </div>
        <div id="crud-group" class="action-list">
          <RecordAction action="back" @click="logAction('Navigated back')" />
          <span class="action-divider" aria-hidden="true"></span>
          <RecordAction action="view" @click="logAction('Viewing item details')" />
          <RecordAction action="edit" @click="logAction('Editing item')" />
          <RecordAction action="delete" @click="deleteOpen = true" />
          <div class="icon-action-list" aria-label="Icon-only actions">
            <RecordAction action="view" icon-only aria-label="View record" @click="logAction('Viewing item details')" />
            <RecordAction action="edit" icon-only aria-label="Edit record" @click="logAction('Editing item')" />
            <RecordAction action="delete" icon-only aria-label="Delete record" @click="deleteOpen = true" />
          </div>
        </div>
      </section>

      <section class="showcase-section">
        <div class="section-heading">
          <div><span class="section-kicker">List controls</span><h2>Filter and reset</h2></div>
          <button class="copy-button" type="button" @click="copySnippet('filter-group')">
            <i class="bi bi-copy" aria-hidden="true"></i> Copy HTML
          </button>
        </div>
        <div id="filter-group" class="filter-actions">
          <button class="demo-button filter-button" type="button" @click="filterCount += 1; logAction(`Filter applied (${filterCount} active)`) ">
            <i class="bi bi-funnel" aria-hidden="true"></i>Add filter
            <span v-if="filterCount" class="filter-badge">{{ filterLabel }}</span>
          </button>
          <span class="filter-divider" aria-hidden="true"></span>
          <button class="demo-button reset-quiet" type="button" @click="resetFilters"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>Reset all</button>
          <button class="demo-button reset-outline" type="button" @click="resetFilters"><i class="bi bi-x-lg" aria-hidden="true"></i>Clear filters</button>
        </div>
      </section>
    </main>

    <Transition name="toast">
      <div v-if="toastMessage" class="system-toast" role="status">
        <i class="bi bi-check-circle-fill" aria-hidden="true"></i>{{ toastMessage }}
      </div>
    </Transition>

    <Transition name="modal">
      <div v-if="deleteOpen" class="modal-backdrop" @click.self="deleteOpen = false">
        <section class="confirm-dialog" role="alertdialog" aria-modal="true" aria-labelledby="delete-title" aria-describedby="delete-description">
          <div class="confirm-icon"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i></div>
          <h2 id="delete-title">Confirm deletion</h2>
          <p id="delete-description">Are you sure you want to delete this item? This action cannot be undone.</p>
          <div class="confirm-actions">
            <button class="demo-button neutral-button" type="button" @click="deleteOpen = false">Cancel</button>
            <button class="demo-button delete-confirm" type="button" @click="confirmDelete">Yes, delete</button>
          </div>
        </section>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.button-system { --system-ink: var(--gss-text-body); --system-muted: var(--gss-text-muted); --system-line: var(--gss-border-default); --system-surface: var(--gss-surface-card-overlay); --system-wash: var(--gss-surface-card-header); color: var(--system-ink); }
.system-header { display:flex; align-items:center; justify-content:space-between; gap:1.25rem; flex-wrap:wrap; padding:0.25rem 0 1.4rem; }
.system-heading { display:flex; align-items:center; gap:0.9rem; }
.system-mark { display:grid; place-items:center; width:46px; height:46px; flex:none; border-radius:15px; color:var(--gss-sidebar-active-text); background:linear-gradient(135deg,var(--gss-brand-primary),var(--gss-brand-secondary)); box-shadow:0 7px 18px var(--gss-shadow-tint-soft); }
.system-mark i { font-size:1.15rem; }
.system-heading h1 { margin:0; color:var(--gss-text-strong); font-size:clamp(1.45rem,2vw,1.85rem); letter-spacing:-.035em; }
.system-heading p { margin:.25rem 0 0; color:var(--system-muted); font-size:.92rem; }
.system-status { display:flex; align-items:center; gap:.55rem; min-height:37px; max-width:100%; padding:.45rem .85rem; border:1px solid var(--system-line); border-radius:999px; color:var(--gss-text-secondary); background:var(--system-surface); font-size:.8rem; font-weight:600; }
.status-dot { width:8px; height:8px; flex:none; border-radius:50%; background:#64865e; box-shadow:0 0 0 3px rgb(100 134 94 / 12%); }
.system-content { display:grid; gap:1rem; }
.showcase-section { overflow:hidden; border:1px solid var(--system-line); border-radius:18px; background:var(--system-surface); box-shadow:0 5px 18px var(--gss-shadow-tint-subtle); }
.section-heading { display:flex; justify-content:space-between; align-items:center; gap:1rem; padding:1rem 1.25rem; border-bottom:1px solid var(--system-line); background:color-mix(in srgb,var(--system-wash),transparent 38%); }
.section-heading h2 { margin:.16rem 0 0; color:var(--gss-text-strong); font-size:1.05rem; letter-spacing:-.02em; }
.section-kicker { color:var(--gss-text-secondary); font-size:.73rem; font-weight:700; }
.copy-button { display:inline-flex; align-items:center; gap:.4rem; flex:none; padding:.47rem .7rem; border:1px solid var(--system-line); border-radius:9px; background:var(--system-surface); color:var(--gss-text-secondary); font-family:inherit; font-size:.76rem; font-weight:600; transition:background-color .15s,border-color .15s; }
.copy-button:hover { background:var(--system-wash); border-color:var(--gss-button-outline-border); }
.sample-row,.action-list,.filter-actions { display:flex; align-items:center; flex-wrap:wrap; gap:.75rem; padding:1.3rem 1.4rem; }
.demo-button { display:inline-flex; align-items:center; justify-content:center; gap:.52rem; min-height:40px; padding:.55rem .85rem; border:1px solid transparent; border-radius:10px; font-family:inherit; font-size:.84rem; font-weight:600; white-space:nowrap; cursor:pointer; transition:background-color .15s,border-color .15s,color .15s,transform .15s,box-shadow .15s; }
.demo-button:active { transform:translateY(1px); }
.demo-button:focus-visible,.copy-button:focus-visible,.pill-switch:focus-visible,.segmented button:focus-visible { outline:3px solid color-mix(in srgb,var(--gss-brand-secondary),transparent 38%); outline-offset:2px; }
.primary-button { background:var(--gss-button-primary-bg); border-color:var(--gss-button-primary-border); color:var(--gss-button-default-text); box-shadow:0 3px 9px var(--gss-shadow-tint-soft); }
.primary-button:hover { background:var(--gss-button-primary-hover-bg); border-color:var(--gss-button-primary-hover-border); }
.soft-button { background:color-mix(in srgb,var(--gss-brand-primary),transparent 82%); color:var(--gss-text-strong); }
.soft-button:hover { background:color-mix(in srgb,var(--gss-brand-primary),transparent 70%); }
.dashed-button { background:transparent; border:1px dashed var(--gss-button-outline-border); color:var(--gss-text-secondary); }
.dashed-button:hover { border-color:var(--gss-button-outline-hover-border); background:var(--gss-button-outline-hover-bg); }
.fab-button { width:42px; height:42px; padding:0; border-radius:50%; background:var(--gss-text-strong); color:#fffaf2; box-shadow:0 4px 12px var(--gss-shadow-tint-medium); }
.fab-button i { font-size:1.1rem; }
.fab-button:hover { background:var(--gss-text-secondary); }
.switch-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:1rem; padding:1.3rem 1.4rem; }
.switch-sample { display:grid; align-content:start; gap:.65rem; min-width:0; }
.sample-label { color:var(--system-muted); font-size:.78rem; font-weight:600; }
.switch-control { display:flex; align-items:center; gap:.65rem; min-height:34px; }
.pill-switch { display:inline-flex; align-items:center; width:48px; height:28px; padding:3px; border:1px solid var(--gss-button-outline-border); border-radius:999px; background:var(--gss-surface-card-header); box-shadow:inset 0 1px 2px var(--gss-shadow-tint-subtle); cursor:pointer; transition:background-color .18s,border-color .18s,box-shadow .18s; }
.pill-switch.is-on { border-color:var(--gss-button-primary-border); background:var(--gss-button-primary-bg); box-shadow:inset 0 1px 0 rgb(255 255 255 / 55%); }
.switch-knob { width:20px; height:20px; border-radius:50%; background:var(--gss-surface-card); box-shadow:0 1px 3px var(--gss-shadow-tint-medium); transition:transform .18s cubic-bezier(.2,.8,.2,1); }
.pill-switch.is-on .switch-knob { transform:translateX(20px); }
.switch-value { color:var(--gss-text-secondary); font-size:.84rem; font-weight:600; }
.segmented { display:inline-flex; justify-self:start; gap:4px; max-width:100%; padding:5px; border:1px solid var(--gss-button-outline-border); border-radius:14px; background:color-mix(in srgb,var(--system-wash) 80%,transparent); box-shadow:inset 0 1px 0 rgb(255 255 255 / 78%),0 3px 10px var(--gss-shadow-tint-subtle); }
.segmented button { border:1px solid transparent; border-radius:10px; padding:.43rem .72rem; background:var(--system-surface); color:var(--gss-text-secondary); font-family:inherit; font-size:.75rem; font-weight:600; cursor:pointer; white-space:nowrap; transition:background-color .16s,border-color .16s,color .16s,box-shadow .16s,transform .16s; }
.segmented button:hover:not(.selected) { border-color:var(--gss-button-outline-hover-border); background:var(--gss-button-outline-hover-bg); }
.segmented button.selected { border-color:var(--gss-button-primary-border); background:var(--gss-button-primary-bg); color:var(--gss-button-default-text); box-shadow:inset 0 1px 0 rgb(255 255 255 / 50%),0 2px 6px var(--gss-shadow-tint-soft); }
.neutral-button { background:var(--system-surface); border-color:var(--system-line); color:var(--gss-text-secondary); }
.neutral-button:hover { background:var(--system-wash); }
.saving { color:#718b60; }
.action-divider,.filter-divider { width:1px; height:25px; margin:0 .2rem; background:var(--system-line); }
.icon-action-list { display:flex; gap:.45rem; margin-left:auto; }
.filter-button { background:var(--gss-button-outline-hover-bg); border-color:var(--gss-button-outline-border); color:var(--gss-text-secondary); }
.filter-button:hover { background:var(--gss-button-outline-active-bg); }
.filter-badge { margin-left:.2rem; padding:.12rem .4rem; border-radius:999px; background:var(--gss-surface-card); color:var(--gss-text-secondary); font-size:.68rem; }
.reset-quiet { background:transparent; color:var(--system-muted); }
.reset-quiet:hover { background:var(--system-wash); color:var(--gss-text-strong); }
.reset-outline { background:var(--system-surface); border-color:var(--system-line); color:var(--system-muted); }
.reset-outline:hover { background:var(--gss-status-danger-background); border-color:color-mix(in srgb,var(--gss-status-danger-text),transparent 70%); color:var(--gss-status-danger-text); }
.system-toast { position:fixed; z-index:1200; right:1.5rem; bottom:1.5rem; display:flex; align-items:center; gap:.6rem; max-width:calc(100vw - 2rem); padding:.8rem 1rem; border:1px solid color-mix(in srgb,var(--gss-text-strong),transparent 76%); border-radius:12px; background:var(--gss-text-strong); color:#fffaf3; box-shadow:0 12px 32px var(--gss-shadow-tint-medium); font-size:.85rem; font-weight:600; }
.system-toast i { color:var(--gss-brand-primary); }
.toast-enter-active,.toast-leave-active,.modal-enter-active,.modal-leave-active { transition:opacity .16s ease,transform .16s ease; }
.toast-enter-from,.toast-leave-to { opacity:0; transform:translateY(8px); }
.modal-backdrop { position:fixed; z-index:1190; inset:0; display:grid; place-items:center; padding:1rem; background:rgb(42 29 19 / 48%); backdrop-filter:blur(3px); }
.confirm-dialog { width:min(100%,410px); padding:1.5rem; border:1px solid var(--system-line); border-radius:18px; background:var(--gss-surface-card); box-shadow:0 22px 70px rgb(42 29 19 / 24%); }
.confirm-icon { display:grid; place-items:center; width:42px; height:42px; margin-bottom:1rem; border-radius:13px; background:var(--gss-status-danger-background); color:var(--gss-status-danger-text); font-size:1.15rem; }
.confirm-dialog h2 { margin:0; color:var(--gss-text-strong); font-size:1.1rem; }
.confirm-dialog p { margin:.45rem 0 1.3rem; color:var(--system-muted); font-size:.87rem; line-height:1.55; }
.confirm-actions { display:flex; justify-content:flex-end; gap:.6rem; }
.delete-confirm { background:var(--gss-status-danger-text); color:#fff; }
.delete-confirm:hover { filter:brightness(.92); }
.modal-enter-from,.modal-leave-to { opacity:0; }
@media (max-width:767px) { .system-header { align-items:flex-start; } .system-status { margin-left:3.55rem; } .switch-grid { grid-template-columns:1fr; gap:1.15rem; } .icon-action-list { width:100%; margin:0; padding-top:.75rem; border-top:1px solid var(--system-line); } .section-heading { padding:.9rem 1rem; } .sample-row,.action-list,.filter-actions { padding:1rem; } }
@media (prefers-reduced-motion:reduce) { .button-system *, .button-system *::before, .button-system *::after { transition-duration:.01ms !important; } }
</style>
