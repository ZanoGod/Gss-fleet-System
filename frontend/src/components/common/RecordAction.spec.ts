import { describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import { createMemoryHistory, createRouter } from 'vue-router'
import RecordAction from './RecordAction.vue'

describe('RecordAction', () => {
  it('emits the original click and exposes an accessible icon-only action', async () => {
    const onClick = vi.fn()
    const wrapper = mount(RecordAction, {
      props: { action: 'view', iconOnly: true, ariaLabel: 'View booking 42', onClick },
    })

    expect(wrapper.attributes('aria-label')).toBe('View booking 42')
    expect(wrapper.find('i').classes()).toContain('bi-eye')
    await wrapper.trigger('click')
    expect(onClick).toHaveBeenCalledOnce()
  })

  it('does not emit clicks while disabled or loading', async () => {
    const onClick = vi.fn()
    const wrapper = mount(RecordAction, {
      props: { action: 'delete', disabled: true, onClick },
    })
    await wrapper.trigger('click')
    expect(onClick).not.toHaveBeenCalled()

    await wrapper.setProps({ disabled: false, loading: true })
    await wrapper.trigger('click')
    expect(wrapper.attributes('aria-busy')).toBe('true')
    expect(onClick).not.toHaveBeenCalled()
  })

  it('keeps record action links navigable through Vue Router', async () => {
    const router = createRouter({
      history: createMemoryHistory(),
      routes: [
        { path: '/records', component: { template: '<div />' } },
        { path: '/records/42', component: { template: '<div />' } },
      ],
    })
    await router.push('/records')
    await router.isReady()
    const wrapper = mount(RecordAction, {
      props: { action: 'view', iconOnly: true, ariaLabel: 'View record 42', to: '/records/42' },
      global: { plugins: [router] },
    })

    expect(wrapper.element.tagName).toBe('A')
    await wrapper.trigger('click')
    await flushPromises()
    expect(router.currentRoute.value.path).toBe('/records/42')
  })
})
